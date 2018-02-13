<?php
namespace service\resources\sales\v1;

use service\components\Proxy;
use service\components\Tools;
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
class orderReview extends ResourceAbstract
{
    const DEFAULT_PAGE_SIZE = 10;

    public function run($data)
    {
        /** @var OrderReviewRequest $request */
        $request = self::request();
        $request->parseFromString($data);

        $customer = $this->_initCustomer($request, true);
        $response = self::response();
        $storeProducts = $this->prepareForMultiCreate($request);
        $storeIds = array_keys($storeProducts);

        $quotes = array();
        //多店铺
        foreach ($storeProducts as $wholesalerId => $products) {
            $quote = new Quote();
            $quote->setCustomerId($request->getCustomerId());
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

            }

            if (count($storeIds) > 1) {
                $quote->setIsMultiStore(true);
            } else {
                $quote->setIsMultiStore(false);
            }

            $quote->collectTotals();
            $addressId = 0;
            $quote->setAddress('');
            $quote->setAddressId($addressId);
            $quote->setCustomerId($request->getCustomerId());


            $quotes[] = $quote;

        }

        $result = new VarienObject();
        $result->setGrandTotal(0);
        $result->setShippingAmount(0);
        $result->setDiscountAmount(0);
        $items = [];
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
            $result->setDiscountAmount($_quote->getDiscountAmount() + $result->getDiscountAmount());
            $items[] = $_quote->getStoreProductDetail();
        }
        $result->setItems($items);

		/* 钱包相关 */
		// 计算当前可用最高零钱数
		$balance_max_use = $customer->getBalance();// 用户钱包余额

		// 用户今天可用余额
		$todayAvailable = Tools::getBalanceDailyLimit($customer->getCustomerId());
		if($balance_max_use > $todayAvailable){
			$balance_max_use = $todayAvailable;
		}

		// 超订单金额使用则砍掉
		if($balance_max_use > $result->getGrandTotal() ){
			$balance_max_use = $result->getGrandTotal();
		}

		// 钱包余额优先抵扣grand_total的零钱,再抵扣整数块。2016年08月22日10:10:19 zgr
		if($balance_max_use>0) {
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
		if($balance == -1 || $balance>$balance_max_use || $balance<0){
			// 第一次进来，或者乱填使用额度，则设置为使用最大值
			$balance = $balance_max_use;
		}

		// 扣减钱包余额
		if($balance>0){
			$result->setGrandTotal($result->getGrandTotal() - $balance);
		}


        $responseData = [
        	'balance_max_use' => $balance_max_use,
        	'balance' => $balance,
            'grand_total' => $result->getGrandTotal(),
            'shipping_amount' => $result->getShippingAmount(),
            'discount_amount' => $result->getDiscountAmount(),
            'customer_id' => $request->getCustomerId(),
            'items' => $items,
        ];

        $response->setFrom(Tools::pb_array_filter($responseData));
        return $response;
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

    public static function request()
    {
        return new OrderReviewRequest();
    }

    public static function response()
    {
        return new OrderReviewResponse();
    }
}
