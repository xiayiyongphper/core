<?php
/**
 * Created by PhpStorm.
 * Date: 2017/3/29
 * Time: 20:33
 */

namespace service\resources\sales\v1;


use common\models\SalesFlatOrder;
use framework\components\Date;
use service\components\Tools;
use service\message\contractor\ContractorAuthenticationRequest;
use service\message\contractor\ManageResponse;
use service\message\core\orderManageRequest;
use service\message\core\orderManageResponse;
use service\resources\Exception;
use service\resources\ResourceAbstract;

class orderManageEntry extends ResourceAbstract
{
    public function run($data)
    {
        $request = self::request();
        $request->parseFromString($data);
        $response = self::response();
        $contractor_id = $request->getContractorId();
        $auth_token = $request->getAuthToken();

        $contractor = $this->_initContractor($contractor_id, $auth_token);

        $city = $request->getCity();
        if (!$city) {
            Exception::contractorCityEmpty();
        }

        if ($contractor->getRole() == self::COMMON_CONTRACTOR) {
            $conditions = ['contractor_id' => $contractor_id];
        } else{
            $conditions = ['city' => $city];
        }

        $date = new Date();
        $now = $date->date();
        $month_time_from = date('Y-m-d H:i:s', strtotime('-30 days', strtotime($now)));

        $bjDate = $date->date('Y-m-d');
        $nowDate = date('Y-m-d 16:00:00', strtotime('-1 day', strtotime($now)));
        $cdsThirtyDaysAgo = ['>', 'created_at', $month_time_from];//30天内
        /*
                Tools::log($now, 'jun.log');
                Tools::log($month_time_from, 'jun.log');
                Tools::log($nowDate, 'jun.log');
                Tools::log($cdsThirtyDaysAgo, 'jun.log');
        */
        $cdsProcessing = ['status' => SalesFlatOrder::STATUS_PROCESSING];//待接单
        $cdsHolded = ['status' => SalesFlatOrder::STATUS_HOLDED];//申请取消
        $cdsRejClosed = ['status' => SalesFlatOrder::STATUS_CLOSED];//拒单

        $orderToday = SalesFlatOrder::find()->where(['and', $conditions, ['>', 'created_at', $nowDate]])
            ->andWhere(['customer_tag_id' => [1,6,8], 'merchant_type_id' =>  [1,6,8]])->count();
        $orderProcessing = SalesFlatOrder::find()->where(['and', $conditions, $cdsProcessing, $cdsThirtyDaysAgo])
            ->andWhere(['customer_tag_id' =>  [1,6,8], 'merchant_type_id' =>  [1,6,8]])->count();
        $orderHolded = SalesFlatOrder::find()->where(['and', $conditions, $cdsHolded, $cdsThirtyDaysAgo])
            ->andWhere(['customer_tag_id' =>  [1,6,8], 'merchant_type_id' =>  [1,6,8]])->count();
        $orderRejClosed = SalesFlatOrder::find()->where(['and', $conditions, $cdsRejClosed, $cdsThirtyDaysAgo])
            ->andWhere(['customer_tag_id' =>  [1,6,8], 'merchant_type_id' =>  [1,6,8]])->count();
        /*
                Tools::log(SalesFlatOrder::find()->where(['and', $conditions, $cdsRejClosed, $cdsThirtyDaysAgo])
                    ->andWhere(['customer_tag_id' => 1, 'merchant_type_id' => 1])->createCommand()->getRawSql(), 'jun.log');
        */
        $responseData = [
            'quick_entry' => [
                [
                    'name' => '今日订单',
                    'number' => $orderToday,
                    'schema' => 'lelaibd://order/list?cityId=' . $city . '&needBD=1&startDate=' . $bjDate . '&endDate=' . $bjDate
                ],
                [
                    'name' => '待商家接单',
                    'number' => $orderProcessing,
                    'schema' => 'lelaibd://order/list?cityId=' . $city . '&needBD=1&orderState=' . SalesFlatOrder::STATUS_PROCESSING
                ],
                [
                    'name' => '申请取消订单',
                    'number' => $orderHolded,
                    'schema' => 'lelaibd://order/list?cityId=' . $city . '&needBD=1&orderState=' . SalesFlatOrder::STATUS_HOLDED
                ],
                [
                    'name' => '供货商拒单',
                    'number' => $orderRejClosed,
                    'schema' => 'lelaibd://order/list?cityId=' . $city . '&needBD=1&orderState=' . SalesFlatOrder::STATUS_CLOSED
                ],
                [
                    'name' => '全部订单',
                    'sub_name' => '最近30天的订单',
                    'schema' => 'lelaibd://order/list?cityId=' . $city . '&needBD=1'
                ]
            ]
        ];

        $response->setFrom(Tools::pb_array_filter($responseData));
        return $response;
    }

    public static function request()
    {
        return new ContractorAuthenticationRequest();
    }

    public static function response()
    {
        return new ManageResponse();
    }
}