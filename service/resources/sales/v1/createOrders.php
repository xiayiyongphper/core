<?php
namespace service\resources\sales\v1;

use common\models\Region;
use common\models\SalesFlatOrder;
use common\models\SalesFlatOrderAddress;
use framework\components\Date;
use service\components\Proxy;
use service\components\Tools;
use service\components\Transaction;
use service\events\ServiceEvent;
use service\message\customer\CustomerResponse;
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
class createOrders extends ResourceAbstract
{
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

                $array[$wholesalerId][$item->getProductId()] = $item->toArray();
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

                $wholesaler = Proxy::getWholesaler($wholesalerId, $this->getTraceId(), $customer);

                if (!$wholesaler->getWholesalerId()) {
                    Exception::storeNotExisted();
                }
                if ($wholesaler->getStatus() != 1) {
                    Exception::storeOffline();
                }
                $quote->setWholesaler($wholesaler);
                $productIds = array_keys($products);
                $productResponse = Proxy::getProducts($customer, $productIds, $this->getTraceId());
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
                    $instance->checkRestrictDaily($buyRequest->getNum(), $customer, true);
                    $quote->addProduct($instance, $buyRequest);

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
                $quote->collectTotals();
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

                $time = $date->gmtDate();

                $uniqueIdClass = new UniqueOrderId(1,self::getWorkerId());
                $order->increment_id = $uniqueIdClass->nextId();
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
                $order->customer_tag_id = $customer->getCustomerTagId();
                $order->merchant_type_id = $wholesaler->getMerchantTypeId();
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
                $appliedRules = array_merge($appliedRules,$rules);
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
                $serviceEvent->setEventData(['store_products' => $storeProducts,'applied_rules' => $appliedRules]);
                $serviceEvent->setTraceId($this->getTraceId());
                $serviceEvent->setCustomer($customer);
                $this->trigger(ServiceEvent::SALES_ORDER_PLACE_AFTER, $serviceEvent);
            }
        }
        return $orders;
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
