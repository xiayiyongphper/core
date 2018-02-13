<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2017/4/19
 * Time: 16:58
 */

namespace service\resources\sales\v1;


use common\models\SalesFlatOrder;
use service\components\Tools;
use service\message\sales\OrderNumberRequest;
use service\message\sales\OrderNumberResponse;
use service\resources\ResourceAbstract;

class orderCountStatus extends ResourceAbstract
{
    public function run($data)
    {
        $request = self::request();
        $request->parseFromString($data);
        $response = self::response();
        $customerId = $request->getCustomerId();

        $conditions = ['customer_id' => $customerId];
        $cdsProcessing = ['status' => SalesFlatOrder::STATUS_PROCESSING];//待接单：待商家接单状态
        $cdsProcessingShipping = ['in', 'status',
            [SalesFlatOrder::STATUS_PROCESSING_RECEIVE, SalesFlatOrder::STATUS_PROCESSING_SHIPPING]];//待收货
        $cdsPendingComment = ['status' => SalesFlatOrder::STATUS_PENDING_COMMENT];//待评价：交易成功，待评价
        $cdsHolded = ['status' => SalesFlatOrder::STATUS_HOLDED];//待取消：申请取消订单（不包含已取消订单）

        $orderProcessing = SalesFlatOrder::find()->where($conditions)->andWhere($cdsProcessing)->count();
        $orderProcessingShipping = SalesFlatOrder::find()->where($conditions)->andWhere($cdsProcessingShipping)->count();
        $orderPendingComment = SalesFlatOrder::find()->where($conditions)->andWhere($cdsPendingComment)->count();
        $orderHolded = SalesFlatOrder::find()->where($conditions)->andWhere($cdsHolded)->count();

        $responseData = [
            'processing' => $orderProcessing,
            'processing_shipping' => $orderProcessingShipping,
            'pending_comment' => $orderPendingComment,
            'holded' => $orderHolded,
        ];

        $response->setFrom(Tools::pb_array_filter($responseData));
        return $response;
    }

    public static function request()
    {
        return new OrderNumberRequest();
    }

    public static function response()
    {
        return new OrderNumberResponse();
    }
}