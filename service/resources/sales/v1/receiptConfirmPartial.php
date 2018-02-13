<?php
namespace service\resources\sales\v1;

use common\models\SalesFlatOrder;

use service\message\common\Order;
use service\message\common\OrderAction;

use service\models\sales\Observer;
use service\models\sales\Quote;
use service\resources\Exception;
use service\resources\ResourceAbstract;

/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2016/1/21
 * Time: 15:09
 */
class receiptConfirmPartial extends ResourceAbstract
{
    public function run($data)
    {
        /** @var OrderAction $request */
        $request = self::request();
        $request->parseFromString($data);
        $response = new Order();
        $customerResponse = $this->_initCustomer($request);
        $customerId = $customerResponse->getCustomerId();
        $orderId = $request->getOrderId();
        if (!$customerId) {
            Exception::customerNotExisted();
        }
        /** @var SalesFlatOrder $order */
        $order = SalesFlatOrder::find()->where(['entity_id' => $orderId])->one();
        if (!$order || !$order->getPrimaryKey()) {
            Exception::orderNotExisted();
        }
        $products = $request->getProducts();
        if ($request->getPartial() && is_array($products) && count($products)) {
            $productIds = [];
            /** @var \service\message\common\Product $product */
            foreach ($products as $product) {
                $productIds[] = $product->getProductId();
            }
            $order->receiptConfirmPartial($productIds)->save();
            Observer::revertOrderPartialInventory($order);
        } else {
            $order->receiptConfirm()->save();
        }
        $responseData = [
            'status' => $order->status,
            'status_label' => $order->getStatusLabel(),
        ];
        $response->setFrom($responseData);
        return $response;
    }

    public static function request()
    {
        return new OrderAction();
    }

    public static function response()
    {
        return new Order();
    }
}