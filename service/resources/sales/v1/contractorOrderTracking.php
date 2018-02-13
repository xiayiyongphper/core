<?php

namespace service\resources\sales\v1;

use common\models\SalesFlatOrder;
use common\models\SalesFlatOrderAddress;
use common\models\SalesOrderStatus;
use framework\components\Date;
use framework\components\ToolsAbstract;
use service\components\Proxy;
use service\components\Tools;
use service\message\common\Header;
use service\message\common\Order;
use service\message\common\SourceEnum;
use service\message\common\Store;
use service\message\contractor\OrderTrackingRequest;
use service\message\contractor\OrderTrackingResponse;
use service\message\merchant\getStoreDetailRequest;
use service\message\sales\DriverOrderDetailRequest;
use service\message\sales\OrderDetailBriefRequest;
use service\resources\Exception;
use service\resources\ResourceAbstract;


/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2016/1/21
 * Time: 15:09
 */
class contractorOrderTracking extends ResourceAbstract
{

    public function run($data)
    {
        //请求参数
        /** @var OrderTrackingRequest $request */
        $request = self::request();
        $request->parseFromString($data);
        $contractor_id = $request->getContractorId();
        $role = $request->getRole();
        $city_list = $request->getCityList();

        //时间范围
        //计算数据
        $date = new Date();
        $now = $date->date();
        $time_from = date('Y-m-d H:i:s', strtotime('-30 days', strtotime($now)));
        $time_to = date('Y-m-d H:i:s');

        $processing_count = SalesFlatOrder::find()->where(['between', 'created_at', $time_from, $time_to])
            ->andWhere(['status' => 'processing']);

        $processing_receive_count = SalesFlatOrder::find()->where(['between', 'created_at', $time_from, $time_to])
            ->andWhere(['status' => 'processing_receive']);

        if ($role == self::COMMON_CONTRACTOR) {
            $processing_count = $processing_count->andWhere(['contractor_id' => $contractor_id])->count();
            $processing_receive_count = $processing_receive_count->andWhere(['contractor_id' => $contractor_id])->count();
        } else {
            $processing_count = $processing_count->andWhere(['city' => $city_list])->count();
            $processing_receive_count = $processing_receive_count->andWhere(['city' => $city_list])->count();
        }

        //返回数据
        $responseData = [
            [
                'key' => '待商家接单',
                'value' => $processing_count ?: 0,
            ],
            [
                'key' => '商家已接单',
                'value' => $processing_receive_count ?: 0,
            ],
        ];
        //接口验证用户
        $response = self::response();
        $response->setFrom(Tools::pb_array_filter(['order_tracking' => $responseData]));
        return $response;
    }

    public static function request()
    {
        return new OrderTrackingRequest();
    }

    public static function response()
    {
        return new OrderTrackingResponse();
    }
}