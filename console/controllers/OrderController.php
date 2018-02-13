<?php

namespace console\controllers;

use common\models\SalesFlatOrder;
use yii\console\Controller;

/**
 * Site controller
 */
class OrderController extends Controller
{
    protected $customerId = 35;
    protected $authToken = '123456789';
    protected $wholesaler_id = 1;

    public function actionIndex()
    {

        $data = [
            [
                'source_date_str' => '170314',
                'source_date' => '2017-03-14',
                'dst_date_str' => '170312',
                'dst_date' => '2017-03-12',
                'increment_ids' => [
                    '17031403105705350',
                    '17031403195772537',
                    '17031403220857781',
                    '17031403250480966',
                    '17031403282209746',
                    '17031403311364251',
                    '17031403333984467',
                    '17031403361040216',
                    '17031403382971870',
                    '17031403413948293',
                    '17031403464897936',
                    '17031403492611921',
                    '17031403513441828',
                    '17031403533374654',
                ]
            ],
            [
                'source_date_str' => '170314',
                'source_date' => '2017-03-14',
                'dst_date_str' => '170313',
                'dst_date' => '2017-03-13',
                'increment_ids' => [
                    '17031403111875976',
                    '17031403201055331',
                    '17031403221896393',
                    '17031403245382788',
                    '17031403280634815',
                    '17031403313178117',
                    '17031403335256107',
                    '17031403362376956',
                    '17031403384767493',
                    '17031403444515355',
                    '17031403465845279',
                    '17031403493585978',
                    '17031403514366452',
                    '17031403534468153',
                ]
            ],
            [
                'source_date_str' => '170409',
                'source_date' => '2017-04-09',
                'dst_date_str' => '170409',
                'dst_date' => '2017-04-09',
                'increment_ids' => [
                    '17041002992152238',
                    '17041002335272636',
                    '17041002360495212',
                    '17041002373489583',
                    '17041002385740495',
                    '17041002424307140',
                    '17041002441692617',
                    '17041002452414533',
                    '17041002470229442',
                    '17041002482162452',
                    '17041002515560769',
                    '17041002530635184',
                    '17041002542608335',
                    '17041002571665452',
                    '17041002583648220',
                ]
            ],

        ];
        $sqlArray = [];
        foreach ($data as $item) {
            $incrementIds = $item['increment_ids'];
            $sourceDateStr = $item['source_date_str'];
            $sourceDate = $item['source_date'];
            $dstDateStr = $item['dst_date_str'];
            $dstDate = $item['dst_date'];
            $orders = SalesFlatOrder::find()
                ->addSelect(['increment_id', 'entity_id'])
                ->andWhere(['increment_id' => $incrementIds])
                ->asArray(true)
                ->all();
            foreach ($orders as $order) {
                $orderId = $order['entity_id'];
                $sqlArray[] = "UPDATE sales_flat_order SET `increment_id`=replace(`increment_id`,'$sourceDateStr','$dstDateStr'), `created_at` = replace(`created_at`,'$sourceDate','$dstDate'),`updated_at` = replace(`updated_at`,'$sourceDate','$dstDate') WHERE `entity_id`=$orderId;";
                $sqlArray[] = "UPDATE sales_flat_order_item SET `subsidies_wholesaler`=0.00,`created_at` = replace(`created_at`,'$sourceDate','$dstDate'),`updated_at` = replace(`updated_at`,'$sourceDate','$dstDate') WHERE `order_id`=$orderId;";
                $sqlArray[] = "UPDATE sales_flat_order_status_history SET `created_at` = replace(`created_at`,'$sourceDate','$dstDate') WHERE `parent_id`=$orderId;";
            }
        }
        echo implode(PHP_EOL, $sqlArray).PHP_EOL;
    }
}
