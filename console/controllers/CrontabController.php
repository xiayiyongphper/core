<?php

namespace console\controllers;




use common\models\SalesFlatOrder;
use console\models\ContractorStatisticsData;
use yii\console\Controller;


/**
 * Created by Jason.
 * Author: Jason Y. Wang
 * Date: 2016/2/1
 * Time: 14:40
 */

class CrontabController extends Controller
{
    /**
     * 最后30天，'活跃用户数(≥3批)','核心用户数(≥8批)'
     */
    public function actionActiveUsers(){
        $status = ['processing_receive','processing_shipping','pending_comment','processing','complete'];
        $test_customer = [
            1021,//林志光测试
            1206,//温则庆测试
            1208,//张海测试
            1215,//袁韩测试
            1245,//胡小鹏测试超市
            2299,//肖思涵测试号
            2376,//张一楠测试
            2476,//邓浩发测试
            1942,//LeoTest
            1650,//test
            2541,//ceshi
        ];

        $test_wholesaler = [
            2,//婷婷旗舰店
            4,//深圳
            5,//林志光
            12,//穆萨测试
            42,//婷婷供货商测试
            260,//赌博测试
        ];


        // 捞出30天内订单
        $date = date('Y-m-d',strtotime('-30 days'));
        $today = date('Y-m-d');
        $order_collection = SalesFlatOrder::find()
            ->select(['customer_id','created_at','contractor_id','city'])
            ->where(['>','created_at',$date])
            ->andWhere(['in','status',$status])
            ->andWhere(['not in','wholesaler_id',$test_wholesaler])
            ->andWhere(['not in','customer_id',$test_customer])
            ->asArray()->all();

        $dt = 60 * 30;
        $customer_order = [];

        // 循环检测
        foreach ($order_collection as $order) {
            $customerId = $order['customer_id'];
            $thisStamp = strtotime($order['created_at']);

            if(!isset($customer_order[$customerId])){
                $customer_order[$customerId] = $order;
                $customer_order[$customerId]['count'] = 1;
            }else{
                $lastStamp = $customer_order[$customerId]['created_at'];
                if($thisStamp - $lastStamp > $dt){
                    $customer_order[$customerId]['count']++;
                }
            }
            $customer_order[$customerId]['created_at'] = $thisStamp;
            $customer_order[$customerId]['city'] = $order['city'];
            $customer_order[$customerId]['contractor_id'] = $order['contractor_id'];
        }

        // 数据
        $dataArray = [];
        // 用户统计
        foreach ($customer_order as $customer) {
            $city = $customer['city'];
            $contractor_id = $customer['contractor_id'];
            if($customer['count']>=3){
                $active_users = isset($dataArray[$city][$contractor_id]['active_users'])?$dataArray[$city][$contractor_id]['active_users']:0;
                $dataArray[$city][$contractor_id]['active_users'] = $active_users + 1;
            }
            if($customer['count']>=8){
                $core_users = isset($dataArray[$city][$contractor_id]['core_users'])?$dataArray[$city][$contractor_id]['core_users']:0;
                $dataArray[$city][$contractor_id]['core_users'] = $core_users + 1;
            }
        }

        //写入数据库
        foreach ($dataArray as $city => $data){
            foreach ($data as $contractor_id => $item) {
                $dataStatistics = ContractorStatisticsData::find()
                    ->where(['contractor_id' => $contractor_id,'city' =>$city,'date' => $today])->one();
                if(!$dataStatistics){
                    $dataStatistics = new ContractorStatisticsData();
                }
                $dataStatistics->active_users = isset($item['active_users'])?$item['active_users']:0;
                $dataStatistics->core_users = isset($item['core_users'])?$item['core_users']:0;
                $dataStatistics->city = $city;
                $dataStatistics->date = $today;
                $dataStatistics->contractor_id = $contractor_id;
                $dataStatistics->save();
            }

        }
    }
}
