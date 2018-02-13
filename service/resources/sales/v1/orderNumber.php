<?php
/**
 * Created by Jason.
 * Author: Jason Y. Wang
 * Date: 2016/3/3
 * Time: 16:26
 */

namespace service\resources\sales\v1;

use common\models\SalesFlatOrder;
use service\components\Tools;
use service\message\sales\OrderNumberRequest;
use service\message\sales\OrderNumberResponse;
use service\resources\ResourceAbstract;

/**
 * Author: Jason Y. Wang
 * Class orderNumber
 * @package service\resources\sales\v1
 */
class orderNumber extends ResourceAbstract
{
    public function run($data){
        /** @var \service\message\sales\OrderNumberRequest $request */
        $request = self::request();
        $request->parseFromString($data);
        //$this->_initCustomer($request);
        $ordersAllCount = SalesFlatOrder::find()->where(['customer_id' => $request->getCustomerId()])->count();
        $response = new OrderNumberResponse();
        $response->setAll($ordersAllCount);

        $ordersReceivingCount = SalesFlatOrder::find()->where(['customer_id' => $request->getCustomerId()])
            ->andWhere(['state' => SalesFlatOrder::STATE_PROCESSING])->count();
        $response->setReceiving($ordersReceivingCount);

        $ordersRefundCount = SalesFlatOrder::find()->where(['customer_id' => $request->getCustomerId()])
            ->andWhere(['in','state',[SalesFlatOrder::STATE_CANCELED,
                SalesFlatOrder::STATE_CLOSED,
                SalesFlatOrder::STATE_REFUND]])->count();
        $response->setRefund($ordersRefundCount);

        $ordersCompleteCount = SalesFlatOrder::find()->where(['customer_id' => $request->getCustomerId()])
            ->andWhere(['state' => SalesFlatOrder::STATE_COMPLETE])->count();
        $response->setComplete($ordersCompleteCount);

        return $response;
    }

//    private function getOrders($condition){
//        $orders = new SalesFlatOrder();
//        $orders->find()->where($condition);
//    }

    public static function request()
    {
        return new OrderNumberRequest();
    }

    public static function response()
    {
        return new OrderNumberResponse();
    }

}
