<?php
namespace service\resources\sales\v1;

use common\models\Region;
use common\models\SalesFlatOrder;
use common\models\SalesFlatOrderAddress;
use common\models\SalesFlatOrderItem;
use framework\components\Date;
use framework\components\ToolsAbstract;
use service\components\Proxy;
use service\components\Tools;
use service\components\Transaction;
use service\events\ServiceEvent;
use service\helpers\ProductHelper;
use service\message\customer\CustomerResponse;
use service\message\merchant\getProductBriefResponse;
use service\message\sales\CreateOrdersRequest;
use service\message\sales\CreateOrdersResponse;
use service\models\payment\alipay\Express;
use service\models\payment\Method;
use service\models\payment\tenpay\Wechat;
use service\models\Product;
use service\models\sales\Quote;
use service\models\sales\quote\Convert;
use service\models\VarienObject;
use service\resources\Exception;
use service\resources\ResourceAbstract;
use service\models\UniqueOrderId;

/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2016/1/21
 * Time: 15:09
 */

/**
 * Class createOrders1
 * @package service\resources\sales\v1
 * @since from app version v2.4
 */
class createOrders1 extends ResourceAbstract
{
    /**
     * 活动不存在或已过期
     */
    const EX_ACTIVITY_NOT_FOUND = 66666;
    /**
     * 秒杀商品过期
     */
    const EX_SK_PRODUCT_EXPIRED = self::EX_ACTIVITY_NOT_FOUND + 1;

    /**
     * @var CustomerResponse
     */
    protected $_customer;
    const DEFAULT_PAGE_SIZE = 10;

    public function run($data)
    {
        /** @var CreateOrdersRequest $request */
        $request = self::request();
        $request->parseFromString($data);
        $response = self::response();
        $this->_customer = $this->_initCustomer($request, true);

        $orders = $this->createOrders($request);
        if (!is_array($orders) && $orders instanceof SalesFlatOrder) {
            $orders = array($orders);
        }
        if (is_array($orders) && count($orders) > 0) {
            $incrementIds = array();
            $grandTotals = array();
            $ids = array();
            $orderData = [];
            foreach ($orders as $order) {
                /** @var SalesFlatOrder $order */
                $incrementIds[] = $order->increment_id;
                $grandTotals[] = $order->grand_total;
                $ids[] = $order->getPrimaryKey();
                $customerId = $order->customer_id;
                $orderData[] = [
                    'order_id' => $order->getPrimaryKey(),
                    'increment_id' => $order->increment_id,
                    'grand_total' => $order->grand_total,
                    'payment_method' => $order->payment_method,
                ];
            }
            $payOrder = new SalesFlatOrder();
            /* @var $payOrder SalesFlatOrder */
            $payOrder->increment_id = implode('_', $incrementIds);
            $payOrder->grand_total = array_sum($grandTotals);
            $payOrder->setTraceid($customerId);
            $responseData = [];
            $responseData['order_id'] = $ids;
            $responseData['order'] = $orderData;
            switch ($request->getPaymentMethod()) {
                case Method::WECHAT://尚未实现
                    $payment = new Wechat();
                    /* @var $payment Wechat */
                    $return = $payment->setOrder($payOrder)->pay();
                    $responseData['wechat_pay'] = $return;
                    break;
                case Method::ALIPAY://尚未实现
                    $payment = new Express();
                    /* @var $payment Express */
                    $return = $payment->setOrder($payOrder)->pay();
                    $responseData['alipay_express'] = $return;
                    break;
                case Method::OFFLINE:
                    break;
                case Method::WALLET:
                    if (!Method::WALLET_SWITCH) {
                        Exception::paymentMethodNotSupported();
                    }
                    break;
                default:
                    Exception::paymentMethodNotSupported();
            }
            $response->setFrom(Tools::pb_array_filter($responseData));
        }
//        Tools::log($response, 'wangyang.txt');
        return $response;


    }

    /**
     * 获取生成订单的产品，进行店铺区分，以便拆分订单
     * @param CreateOrdersRequest $request
     * @return array
     * @throws Exception
     */
    protected function prepareForMultiCreate($request)
    {
        //prepare items
        $items = $request->getItems();
        $array = array();
        if (is_array($items) && count($items) > 0) {
            foreach ($items as $item) {
                /** @var \service\message\common\Product $item */
                $wholesalerId = $item->getWholesalerId();
                if (!$wholesalerId) {
                    Exception::storeNotExisted();
                }
                if (!isset($array[$wholesalerId])) {
                    $array[$wholesalerId] = array();
                }

                /* 修复iPhone 5等32位系统溢出的问题 */
                if ($item->getProductId() < 0) {
                    $corretId = $item->getProductId() + (1 << 32);
                    $item->setProductId($corretId);
                }

                if (isset($array[$wholesalerId][$item->getProductId()])) {
                    $newNum = $array[$wholesalerId][$item->getProductId()]['num'] + $item->getNum();
                    $array[$wholesalerId][$item->getProductId()]['num'] = $newNum;
                } else {
                    $array[$wholesalerId][$item->getProductId()] = $item->toArray();
                }
            }
            if (count(array_keys($array)) > 1) {
                Exception::multiStoreNotAllowed();
            }
        } else {
            Exception::emptyShoppingCart();
        }
        return $array;
    }

    /**
     * 创建订单
     * @param CreateOrdersRequest $request
     * @return array
     * @throws Exception
     */
    public function createOrders(CreateOrdersRequest $request)
    {
        if (!$request->getCustomerId()) {
            Exception::customerNotExisted();
        }

        if (!$request->getPaymentMethod()) {
            Exception::paymentMethodNotSupported();
        }

        $items = $this->prepareForMultiCreate($request);
        if (count($items) === 0) {
            Exception::emptyShoppingCart();
        }
        return $this->saveOrders($request, $items);
    }

    /**
     * 保存订单
     * @param CreateOrdersRequest $request
     * @param $storeProducts
     * @return array
     * @throws Exception
     * @throws \Exception
     * @throws bool
     */
    protected function saveOrders(CreateOrdersRequest $request, $storeProducts)
    {
        $customerId = $request->getCustomerId();
        $customer = $this->_customer;
        $requestAddress = $request->getAddress();
        $couponId = $request->getCouponId();
        $convert = new Convert();
        $date = new Date();

        $expireTime = null;
        if (is_array($storeProducts)) {
            $orders = [];
            $quotes = [];
            $appliedRules = [];
            $transaction = new Transaction();
            //$transaction = Mage::getModel('core/resource_transaction');
            $storeIds = array_keys($storeProducts);
            foreach ($storeProducts as $wholesalerId => $products) {
                $order = new SalesFlatOrder();
                //$order = Mage::getModel('sales/order');
                $quote = new Quote();
                $quote->setCustomerId($customerId);
                $quote->setCouponId($couponId);

                $wholesaler = Proxy::getWholesaler($wholesalerId, $this->getTraceId(), $customer);

                if (!$wholesaler->getWholesalerId()) {
                    Exception::storeNotExisted();
                }
                if ($wholesaler->getStatus() != 1) {
                    Exception::storeOffline();
                }
                $quote->setWholesaler($wholesaler);
                $productIds = array_keys($products);

                /* 从其他接口获取商品信息，是秒杀商品则要处理相应异常 */
                $productResponse = $this->getProductsByProxy($customer, $productIds);
                if (!$productResponse || !$productResponse->getProductList()) {
                    Exception::emptyShoppingCart();
                }

                foreach ($productResponse->getProductList() as $product) {
                    /** @var \service\message\common\Product $product */
                    $instance = new Product($product);
                    $buyRequest = new VarienObject($products[$instance->getProductId()]);
                    if (!$instance->isOnSale() || !$instance->checkQty($buyRequest->getNum())) {
                        $topicText = $instance->getQty() > 0 ? Exception::NEW_CATALOG_PRODUCT_SOLD_OUT_TEXT2 : Exception::NEW_CATALOG_PRODUCT_SOLD_OUT_TEXT1;
                        $productName = sprintf($topicText, $instance->getName());
                        throw new \Exception($productName, Exception::CATALOG_PRODUCT_SOLD_OUT);
                    }
//                    $instance->checkRestrictDaily($buyRequest->getNum(), $customer, true);
                    // 如果限购，则查出来购买了多少，后面限购超出限购数按原价购买
                    if ($product->getRestrictDaily() > 0) {
                        foreach (ProductHelper::seperateRestrictDailyProduct($customer, $instance, $buyRequest) as $sepItem) {
                            list($newInstance, $newBuyRequest) = $sepItem;
                            $quote->addProduct($newInstance, $newBuyRequest);
                        }
                    } else {
                        $quote->addProduct($instance, $buyRequest);
                    }
                    /* 由于下单时间用的是$storeProducts，而$storeProducts参数太少，需要增加一些必要的参数 */
                    $storeProducts[$wholesalerId][$product->getProductId()]['type'] = $instance->getType();
                }

                if (count($storeIds) > 1) {
                    $quote->setIsMultiStore(true);
                } else {
                    $quote->setIsMultiStore(false);
                }

                // 自提
                //$quote->setDeliveryMethod($request->getDeliveryMethod());
                //促销说明
                $off_money = 0;
                $rebates_calculate_lelai = 0;
                $commission = 0;
                $quote->collectTotals(true);
                ToolsAbstract::log($quote->getData());
                ToolsAbstract::log($quote->getPromotions());
                $subsidies_lelai = 0;
                $subsidies_wholesaler = 0;
                $rebatesWholesaler = 0;
                $rebates_lelai = 0;
                $rebates_wholesaler = 0;

                foreach ($quote->getItems() as $item) {
                    $rebates_wholesaler = $item->getRebatesWholesaler();//供应商对商品的返点百分比
                    $rebates_lelai = $item->getRebatesLelai();
                    $orderItem = $convert->itemToOrderItem($item);
                    $order->addItem($orderItem);
                    //计算优惠金额
                    $off_money += $orderItem->rebates_calculate;
                    $commission += $orderItem->commission;
                    $rebates_calculate_lelai += $orderItem->rebates_calculate_lelai;
                    $subsidies_lelai += $orderItem->subsidies_lelai;
                    $subsidies_wholesaler += $orderItem->subsidies_wholesaler;
                    $rebatesWholesaler = $rebatesWholesaler + $orderItem->rebates_calculate - $orderItem->rebates_calculate_lelai;
                }

                $promotions = [
                    [
                        'rebates_wholesaler' => $rebates_wholesaler ?: 0,
                        'rebates_lelai' => $rebates_lelai ?: 0,
                        //'off_money' => round($off_money, 2),
                        'off_money' => number_format($off_money, 2, null, ''),
                        'text' => '本单预计可返现¥' . number_format($off_money, 2, null, ''),
                        'description' => '确认收货后，返现会打入您的钱包账户中',
                    ],
                ];
                $additional_info = [];
                if (count($quote->getPromotions()) > 0) {
                    $additional_info['promotions'] = $quote->getPromotions();
                }

                if (count($quote->getTags()) > 0) {
                    $additional_info['applied_rules'] = [
                        'tags' => $quote->getTags()
                    ];
                }

                $time = $date->gmtDate();

                $uniqueIdClass = new UniqueOrderId(1, self::getWorkerId());
                $order->increment_id = $uniqueIdClass->nextId();
                Tools::log($uniqueIdClass->nextId(), 'uniqueId.log');
                //$order->increment_id = $this->getIncrementId();
                $order->wholesaler_id = $wholesalerId;
                $order->wholesaler_name = $wholesaler->getWholesalerName();
                $order->phone = $customer->getPhone();
                $order->coupon_id = $quote->getCouponId() > 0 ? $quote->getCouponId() : 0;
                $order->coupon_discount_amount = $quote->getCouponDiscountAmount();
                $order->applied_rule_ids = $quote->getAppliedRuleIds(true);
                $order->payment_method = $request->getPaymentMethod();
                $order->delivery_method = $quote->getDeliveryMethod();
                $order->created_at = $time;
                $order->updated_at = $time;
                $order->customer_id = $request->getCustomerId();
                $order->store_name = $customer->getStoreName();
                $order->remote_ip = $this->getRemoteIp();
                $order->total_item_count = $quote->getItemsCount();
                $order->total_qty_ordered = $quote->getItemsQty();
                $order->total_paid = 0;
                $order->total_due = 0;
                $order->customer_note = $request->getComment();
                $order->discount_amount = $quote->getDiscountAmount();
                $order->shipping_amount = $quote->getShippingAmount();
                $order->grand_total = $this->formatPrice($quote->getGrandTotal());
                $order->subtotal = $this->formatPrice($quote->getSubtotal());
                $order->province = $customer->getProvince() > 0 ? $customer->getProvince() : 0;
                $order->city = $customer->getCity() > 0 ? $customer->getCity() : 0;
                $order->district = $customer->getDistrict() > 0 ? $customer->getDistrict() : 0;
                $order->area_id = $customer->getAreaId();
                $order->promotions = isset($promotions) ? serialize($promotions) : '';
                $order->rebates = $off_money;// 新增订单返现金额字段单独存储
                $order->commission = $commission;
                $order->rebates_lelai = $rebates_calculate_lelai;
                $order->source = is_numeric($this->getSource()) ? $this->getSource() : 0;
                $order->source_version = (string)$this->getAppVersion();
                $order->device_id = (string)$this->getDeviceId();
                $order->contractor_id = $customer->getContractorId();
                $order->contractor = $customer->getContractor();
                $order->storekeeper = $customer->getStorekeeper();
                $order->rebates_wholesaler = $rebatesWholesaler;
                $order->subsidies_lelai = $subsidies_lelai;
                $order->subsidies_wholesaler = $subsidies_wholesaler;
                $order->is_first_order = $customer->getFirstOrderId() > 0 ? 2 : 1;
                $order->rule_apportion = $quote->getRuleApportion();
                $order->rule_apportion_lelai = $quote->getRuleApportionLelai();
                $order->rule_apportion_wholesaler = $quote->getRuleApportionWholesaler();
                $order->rule_apportion_products_coupon_lelai = $quote->getRuleApportionProductsCouponLelai();
                $order->rule_apportion_products_act_lelai = $quote->getRuleApportionProductsActLelai();
                $order->rule_apportion_order_coupon_lelai = $quote->getRuleApportionOrderCouponLelai();
                $order->rule_apportion_order_act_lelai = $quote->getRuleApportionOrderActLelai();
                $order->customer_tag_id = $customer->getCustomerTagId();
                $order->merchant_type_id = $wholesaler->getMerchantTypeId();
                $order->activity_id = $this->getSeckillActivityId($order);

                //已使用的优惠券
                $order->setCoupon($quote->getCoupon());

                if (count($additional_info) > 0) {
                    $order->additional_info = serialize($additional_info);
                }
                // 检查钱包余额
                $balance = $request->getBalance();

                //Tools::log($balance);
                if ($balance > 0) {
                    // 余额不足
                    $customer_balance = $customer->getBalance();
                    if ($balance > $customer_balance) {
                        Exception::balanceInsufficient();
                    }

                    // 用户今天可用余额
                    $todayAvailable = Tools::getBalanceDailyLimit($customer->getCustomerId());
                    if ($balance > $todayAvailable) {
                        Exception::balanceOverDailyLimit();
                    }

                    // 钱包使用额度超过订单总价
                    if ($balance > $order->grand_total) {
                        Exception::balanceOverGrandTotal();
                    }

                    // 至此可以使用钱包余额
                    $order->balance = $balance;
                    $order->grand_total -= $balance;

                }

                // 订单过期时间
                if ($expireTime == 1) {
                    $expireTime = $time + 30 * 24 * 3600;// 一个月
                    $order->expire_time = $expireTime;
                }

                $order->setQuote($quote);
                switch ($order->payment_method) {
                    case Method::WECHAT:
                    case Method::ALIPAY:
                        $order->setState(SalesFlatOrder::STATE_NEW, true);
                        break;
                    case Method::OFFLINE:
                        $order->setState(SalesFlatOrder::STATE_NEW, SalesFlatOrder::STATUS_PENDING);
                        $order->setState(SalesFlatOrder::STATE_PROCESSING, SalesFlatOrder::STATUS_PROCESSING);
                        break;
                    default:
                        Exception::paymentMethodNotSupported();
                }

                // 获取地区名字
                $districtCode = $customer->getDistrict();
                $regionModel = new Region();
                $district = $regionModel->findOne(['code' => $districtCode]);

                $orderAddress = new SalesFlatOrderAddress();
                $orderAddress->name = $requestAddress->getName();
                $orderAddress->phone = $requestAddress->getPhone();
                $districtName = $district ? $district->chinese_name : '';
                $orderAddress->address = $districtName . $customer->getAddress() . $customer->getDetailAddress();

                $order->setAddress($orderAddress);
                $transaction->addObject($order);
                $orders[] = $order;
                $quotes[] = $quote;
                $rules = $quote->getAppliedRules();
                $appliedRules = array_merge($appliedRules, $rules);
            }

            $event = new ServiceEvent();
            $event->setEventData($storeProducts);
            $event->setTraceId($this->getTraceId());
            $event->setCustomer($customer);
            $this->trigger(ServiceEvent::SALES_QUOTE_SUBMIT_BEFORE, $event);

            try {
                $transaction->save();
                $success = true;
            } catch (\Exception $e) {
                $this->trigger(ServiceEvent::SALES_QUOTE_SUBMIT_FAILURE, $event);
                throw $e;
            }
            if ($success) {
                $serviceEvent = new ServiceEvent();
                $serviceEvent->setEventData(['store_products' => $storeProducts, 'applied_rules' => $appliedRules]);
                $serviceEvent->setTraceId($this->getTraceId());
                $serviceEvent->setCustomer($customer);
                $this->trigger(ServiceEvent::SALES_ORDER_PLACE_AFTER, $serviceEvent);
            }
        }
        return $orders;
    }

    /**
     * @param CustomerResponse $customer
     * @param array $productIds
     * @throws \Exception
     * @return bool|getProductBriefResponse
     */
    private function getProductsByProxy($customer, $productIds)
    {
        try {
            $productResponse = Proxy::getProducts($customer, $productIds, $this->getTraceId());
            return $productResponse;
        } catch (\Exception $e) {
            /* 处理秒杀异常，其他直接返回 */
            switch ($e->getCode()) {
                case self::EX_SK_PRODUCT_EXPIRED:
                case self::EX_ACTIVITY_NOT_FOUND:
                    $result = json_decode($e->getMessage(), 1);
                    if (!$result) {
                        throw $e;
                    }
                    if ($e->getCode() == self::EX_ACTIVITY_NOT_FOUND) {
                        if (count($result) == 1) {
                            $msg = sprintf('您的秒杀商品“%s”已被抢光，请返回购物车重新提交订单', current($result));
                        } else {
                            $msg = sprintf('您有%d个秒杀商品已被抢光，请返回购物车重新提交订单', count($result));
                        }
                        throw new \Exception($msg, $e->getCode(), $e);
                    } elseif ($e->getCode() == self::EX_SK_PRODUCT_EXPIRED) {
                        if (count($result) == 1) {
                            $msg = sprintf('您的秒杀商品“%s”倒计时结束，请返回购物车重新提交订单', current($result));
                        } else {
                            $msg = sprintf('您有%d个秒杀商品倒计时结束，请返回购物车重新提交订单', count($result));
                        }
                        throw new \Exception($msg, $e->getCode(), $e);
                    }
                    break;
                default:
                    throw $e;
            }
        }
    }

    /**
     * 获取秒杀活动id
     *
     * @param SalesFlatOrder $order
     * @return int
     */
    private function getSeckillActivityId(SalesFlatOrder $order)
    {
        $ret = 0;
        if (!empty($order->getItems()) && is_array($order->getItems())) {
            /** @var SalesFlatOrderItem $item */
            foreach ($order->getItems() as $item) {
                if (!empty($item->activity_id) && $item->activity_id > 0) {
                    $ret = $item->activity_id;
                    break;
                }
            }
        }
        return $ret;
    }

    /**
     * 格式化价格
     * @param $price
     * @return float
     */
    protected function formatPrice($price)
    {
        return number_format($price, 2, null, '');
    }

    /**
     * @return string
     */
    protected function getIncrementId()
    {
        list($s1, $s2) = explode(' ', microtime());
        $millisecond = explode('.', $s1);
        $mill = substr($millisecond[1], 0, 5);
        return sprintf('%s%s', date('ymdHis', $s2), $mill);
    }

    public static function request()
    {
        return new CreateOrdersRequest();
    }

    public static function response()
    {
        return new CreateOrdersResponse();
    }
}
