<?php
namespace service\resources\sales\v1;

use common\models\salesrule\UserCoupon;
use framework\components\ToolsAbstract;
use service\components\Proxy;
use service\components\Tools;
use service\helpers\ProductHelper;
use service\message\customer\CustomerResponse;
use service\message\merchant\getProductBriefResponse;
use service\message\sales\OrderReviewRequest;
use service\message\sales\OrderReviewResponse;
use service\models\Product;
use service\models\sales\Quote;
use service\models\VarienObject;
use service\resources\Exception;
use service\resources\ResourceAbstract;

/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2016/1/21
 * Time: 15:09
 */
class orderReview1 extends ResourceAbstract
{
    /**
     * 活动不存在或已过期
     */
    const EX_ACTIVITY_NOT_FOUND = 66666;
    /**
     * 秒杀商品过期
     */
    const EX_SK_PRODUCT_EXPIRED = self::EX_ACTIVITY_NOT_FOUND + 1;

    const DEFAULT_PAGE_SIZE = 10;

    public function run($data)
    {
        /** @var OrderReviewRequest $request */
        $request = self::request();
        $request->parseFromString($data);

        $customer = $this->_initCustomer($request, true);
        $response = new OrderReviewResponse();
        $storeProducts = $this->prepareForMultiCreate($request);
        $storeIds = array_keys($storeProducts);

        $quotes = array();
        //多店铺
        foreach ($storeProducts as $wholesalerId => $products) {
            $quote = new Quote();
            $quote->setCustomerId($request->getCustomerId());
            $quote->setCouponId($request->getCouponId());
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

            foreach ($productResponse->getProductList() as $product) {
                /** @var \service\message\common\Product $product */
                $instance = new Product($product);
                $buyRequest = new VarienObject($products[$instance->getProductId()]);
                if (!$instance->isOnSale() || !$instance->checkQty($buyRequest->getNum())) {
                    $topicText = $instance->getQty() > 0 ? Exception::NEW_CATALOG_PRODUCT_SOLD_OUT_TEXT2 : Exception::NEW_CATALOG_PRODUCT_SOLD_OUT_TEXT1;
                    $productName = sprintf($topicText, $instance->getName());
                    throw new \Exception($productName, Exception::CATALOG_PRODUCT_SOLD_OUT);
                }

//                $instance->checkRestrictDaily($buyRequest->getNum(), $customer, true);
                // 如果限购，则查出来购买了多少，后面限购超出限购数按原价购买
                if ($product->getRestrictDaily() > 0) {
                    foreach (ProductHelper::seperateRestrictDailyProduct($customer, $instance, $buyRequest) as $sepItem) {
                        list($newInstance, $newBuyRequest) = $sepItem;
                        $quote->addProduct($newInstance, $newBuyRequest);
                    }
                } else {
                    $quote->addProduct($instance, $buyRequest);
                }
            }

            if (count($storeIds) > 1) {
                $quote->setIsMultiStore(true);
            } else {
                $quote->setIsMultiStore(false);
            }

            $quote->setCustomerId($request->getCustomerId());
            $quote->collectTotals(true);
            $addressId = 0;
            $quote->setAddress('');
            $quote->setAddressId($addressId);
            //$quote->setCustomerId($request->getCustomerId());
            $quotes[] = $quote;
        }

        $result = new VarienObject();
        $result->setGrandTotal(0);
        $result->setSubtotal(0);
        $result->setOriginalAmount(0);
        $result->setShippingAmount(0);
        $result->setDiscountAmount(0);
        $result->setSpecialActDiscount(0);
        $result->setCouponDiscountAmount(0);
        $items = [];
        $tags = [];
        $availableCoupons = null;
        $unavailableCoupons = null;
        $couponId = 0;
        $couponText = UserCoupon::NOT_AVAILABLE_COUPON_TEXT;
        //多店铺
        foreach ($quotes as $_quote) {
            /* @var $_quote Quote */
            /*check mini trade amount */
            $_minTradeAmount = $_quote->getWholesaler()->getMinTradeAmount();

            $count = Tools::orderCountToday($customer->getCustomerId(), $_quote->getWholesaler()->getWholesalerId());
            if ($count == 0) {
                if ($_minTradeAmount > 0 && $_quote->getSubtotal() < $_minTradeAmount) {
                    Exception::notSatisfyMinTradeAmount($_minTradeAmount);
                }
            }
            $result->setGrandTotal($_quote->getGrandTotal() + $result->getGrandTotal());
            $result->setCustomerId($_quote->getCustomerId());
            $result->setShippingAmount($_quote->getShippingAmount() + $result->getShippingAmount());
            $result->setOriginalAmount($_quote->getOriginalAmount() + $result->getOriginalAmount());
            $result->setSpecialActDiscount($_quote->getSpecialActDiscount() + $result->getSpecialActDiscount());
            $result->setDiscountAmount($_quote->getDiscountAmount() + $result->getDiscountAmount());
            $result->setCouponDiscountAmount($_quote->getCouponDiscountAmount() + $result->getCouponDiscountAmount());
            $result->setSubtotal($_quote->getSubtotal() + $result->getSubtotal());
            $items[] = $_quote->getStoreProductDetail();
            $tags = $_quote->getTags();
            $availableCoupons = $_quote->getAvailableCoupons();
            $unavailableCoupons = $_quote->getUnavailableCoupons();
            $couponId = $_quote->getCouponId();

            if ($_quote->getGiftDiscount() === true) {
                $couponText = '已享赠品';
            }

            if ($_quote->getCouponDiscountAmount() > 0) {
                $couponText = sprintf('已减%s元', $_quote->getCouponDiscountAmount());
            }
        }

        if ($couponId == UserCoupon::NOT_USE_COUPON) {
            $couponText = UserCoupon::NOT_USE_COUPON_TEXT;
        }

        $result->setItems($items);

        /* 钱包相关 */
        // 计算当前可用最高零钱数
        $balance_max_use = $customer->getBalance();// 用户钱包余额

        // 用户今天可用余额
        $todayAvailable = Tools::getBalanceDailyLimit($customer->getCustomerId());
        if ($balance_max_use > $todayAvailable) {
            $balance_max_use = $todayAvailable;
        }

        // 超订单金额使用则砍掉
        if ($balance_max_use > $result->getGrandTotal()) {
            $balance_max_use = $result->getGrandTotal();
        }

        // 钱包余额优先抵扣grand_total的零钱,再抵扣整数块。2016年08月22日10:10:19 zgr
        if ($balance_max_use > 0) {
            $gt = $result->getGrandTotal();// 当前的订单总额
            $decimal = $gt - floor($gt);
            if ($balance_max_use >= $decimal) {
                $balance_max_use = floor($balance_max_use - $decimal) + $decimal;
            } else {
                $balance_max_use = 0;
            }
        }

        // 得到订单实际使用钱包金额
        $balance = $request->getBalance();
        if ($balance == -1 || $balance > $balance_max_use || $balance < 0) {
            // 第一次进来，或者乱填使用额度，则设置为使用最大值
            $balance = $balance_max_use;
        }

        // 扣减钱包余额
        if ($balance > 0) {
            $result->setGrandTotal($result->getGrandTotal() - $balance);
        }


        $allDiscountAmount = number_format($result->getOriginalAmount() - $result->getGrandTotal(), 2, '.', '');
        $responseData = [
            'balance_max_use' => $balance_max_use,
            'balance' => $balance,
            'grand_total' => $result->getGrandTotal(),  // FLOAT 订单确认页的实付金额
            'base_total' => $result->getOriginalAmount(), // FLOAT 确认订单页的商品总额
            'shipping_amount' => $result->getShippingAmount(),
            'discount_amount' => $result->getDiscountAmount(),
            'special_act_discount' => $result->getSpecialActDiscount(), // FLOAT，特价优惠
            'all_discount_amount' => $allDiscountAmount > 0 ? $allDiscountAmount : 0,   // FLOAT，累计节省总价
            'coupon_discount_amount' => $result->getCouponDiscountAmount(),
            'customer_id' => $request->getCustomerId(),
            'items' => $items,
            'available_coupons' => $availableCoupons,
            'unavailable_coupons' => $unavailableCoupons,
            'coupon_id' => $couponId,
            'coupon_text' => $couponText,
        ];

        if (count($tags) > 0) {
            // 去掉无用的信息
            foreach ($tags as $_k => $tag) {
                unset($tags[$_k]['type'], $tags[$_k]['id']);
            }
            $responseData['applied_rules'] = [
                'group_name' => '已享优惠',
                'tags' => $tags
            ];
        }

        $response->setFrom(Tools::pb_array_filter($responseData));
        return $response;
    }

    /**
     * @param CustomerResponse $customer
     * @param array $productIds
     * @throws \Exception
     * @return getProductBriefResponse
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
                            $msg = sprintf('哎呀，您的秒杀商品“%s”已被抢光了，赶快去秒杀其他商品', current($result));
                        } else {
                            $msg = sprintf('哎呀，您有%d个秒杀商品已被抢光了，赶快去秒杀其他商品', count($result));
                        }
                        throw new \Exception($msg, $e->getCode(), $e);
                    } elseif ($e->getCode() == self::EX_SK_PRODUCT_EXPIRED) {
                        if (count($result) == 1) {
                            $msg = sprintf('哎呀，您的秒杀商品“%s”因倒计时结束已被清空，赶快去秒杀其他商品', current($result));
                        } else {
                            $msg = sprintf('哎呀，您有%d个秒杀商品因倒计时结束已被清空，赶快去秒杀其他商品', count($result));
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
     * 获取生成订单的产品，进行店铺区分，以便拆分订单
     * @param OrderReviewRequest $request
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

    public static function request()
    {
        return new OrderReviewRequest();
    }

    public static function response()
    {
        return new OrderReviewResponse();
    }
}
