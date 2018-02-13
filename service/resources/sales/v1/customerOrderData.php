<?php
namespace service\resources\sales\v1;

use common\models\SalesFlatOrder;
use framework\components\Date;
use service\components\Tools;
use service\message\contractor\StoreRecentlyOrderRequest;
use service\message\contractor\StoreRecentlyOrderResponse;
use service\resources\ResourceAbstract;

/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2016/8/23
 * Time: 15:09
 */
class customerOrderData extends ResourceAbstract
{

    public function run($data)
    {
        //请求参数
        /** @var StoreRecentlyOrderRequest $request */
        $request = self::request();
        $request->parseFromString($data);
        $customer_id = $request->getCustomerId();
        //计算数据
        $date = new Date();
        $now = $date->date();
        $time_from = date('Y-m-d H:i:s', strtotime('-7 days', strtotime($now)));
        $time_to = $now;
        $query = SalesFlatOrder::find()->where(['customer_id' => $customer_id])
            ->andWhere(['between', 'created_at', $time_from, $time_to])
            ->andWhere(['not in', 'state', ['canceled', 'closed']])
            ->orderBy('created_at desc');
        //7天内的总金额
        $grand_total_sum = $query->sum('grand_total');
        //7天内的总数量
        $count = $query->count();
        //近7天客单价
        $grand_total_average = round($query->average('grand_total'), 2);
        //最近有效下单时间
        /** @var SalesFlatOrder $recently_order */
        $recently_effective_order = SalesFlatOrder::find()->where(['customer_id' => $customer_id])
            ->andWhere(['not in', 'state', ['canceled', 'closed']])
            ->andWhere(['customer_tag_id' => 1, 'merchant_type_id' => 1])
            ->orderBy('created_at desc')->one();
        //最近下单
        /** @var SalesFlatOrder $recently_order */
        $recently_order = SalesFlatOrder::find()->where(['customer_id' => $customer_id])
            ->orderBy('created_at desc')->one();

        //接口验证用户
        $response = self::response();
        if ($recently_effective_order) {
            $time = floor((strtotime($now) - strtotime($recently_effective_order->created_at)) / 86400);
            if ($time == 0) {
                $effective_days_str = '今天';
            } else {
                $effective_days_str = $time . '天前';
            }
        } else {
            $effective_days_str = '未下单';
        }

        if ($recently_order) {
            $time = floor((strtotime($now) - strtotime($recently_order->created_at)) / 86400);
            if ($time == 0) {
                $days_str = '今天';
            } else {
                $days_str = $time . '天前';
            }
        } else {
            $days_str = '未下单';
        }

        //Tools::log($grand_total_sum, 'wangyang.log');
        //Tools::log($count, 'wangyang.log');
        //Tools::log($grand_total_average, 'wangyang.log');
        $responseData = [
            [
                'key' => '近7天有效订单总额',
                'value' => $grand_total_sum ?: 0
            ],
            [
                'key' => '近7天有效订单数',
                'value' => $count ?: 0
            ],
            [
                'key' => '近7天客单价',
                'value' => $grand_total_average ?: 0
            ],
            [
                'key' => '最近有效订单',
                'value' => $effective_days_str
            ],
            [
                'key' => '最近下单',
                'value' => $days_str
            ],
        ];

        $response->setFrom(['order_info' => $responseData]);

        //Tools::log($response, 'wangyang.log');
        return $response;
    }


    public static function request()
    {
        return new StoreRecentlyOrderRequest();
    }

    public static function response()
    {
        return new StoreRecentlyOrderResponse();
    }
}