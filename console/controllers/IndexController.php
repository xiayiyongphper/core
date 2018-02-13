<?php

namespace console\controllers;

use common\models\AvailableCity;
use common\models\SalesFlatOrder;
use console\models\ContractorStatisticsData;
use framework\components\ToolsAbstract;
use phpseclib\Crypt\RSA;
use service\components\Events;
use service\components\Proxy;
use service\message\contractor\ContractorHomeDataRequest;
use service\resources\sales\v1\contractorHomeData;
use Yii;
use yii\console\Controller;

/**
 * Site controller
 */
class IndexController extends Controller
{
    protected $customerId = 35;
    protected $authToken = '123456789';
    protected $wholesaler_id = 1;

    public function actionIndex()
    {
        $redis = ToolsAbstract::getRedis();
        $script = <<<SCRIPT
if redis.call("EXISTS", KEYS[1]) == 1 then
  local qty = tonumber(redis.call("GET", KEYS[1]))
  local num = tonumber(ARGV[1])
  if qty >= num then
    qty = redis.call("DECRBY",KEYS[1],num)
    return qty
  else
    return -1
  end
else
  return -2
end
SCRIPT;
        $script = <<<SCRIPT
--检查商品库存可用性，商品库存不足时返回对应的index位置
local function checkAvailability(_keys, _values)
    local flag = 0
    for k, v in pairs(_keys) do
        if redis.call("EXISTS", _keys[k]) == 1 then
            local qty = tonumber(redis.call("GET", _keys[k]))
            local num = tonumber(_values[k])
            if qty < num then
                flag = -k
            end
        else
            flag = -k
        end
        if flag < 0 then
            break
        end
    end
    return flag
end

--直接扣减库存
local function subtractInventory(_keys, _values)
    for k, v in pairs(_keys) do
        local num = tonumber(_values[k])
        redis.call("DECRBY", _keys[k], num)
    end
    return 1
end

local availability = checkAvailability(KEYS, ARGV)
if availability == 0 then
    subtractInventory(KEYS, ARGV)
    return 0
else
    return availability
end
SCRIPT;
//        $ret = $redis->eval($script, ['qty_441800_1', 'qty_441800_2', 'qty_441800_3', 5, 1, 10], 3);
        $ret = ToolsAbstract::subtractInventory(['qty_441800_1' => 5, 'qty_441800_2' => 1, 'qty_441800_3' => 1]);
//        $ret = $redis->eval($script, ['qty_441800_1', 3], 1);
        var_dump($ret);
    }

    public function actionIndex2()
    {
        echo __LINE__ . PHP_EOL;
        $rsa = new RSA();
        $frameworkDir = Yii::getAlias('@framework');
        echo $frameworkDir;
        $publicKey = file_get_contents($frameworkDir . DIRECTORY_SEPARATOR . 'env' . DIRECTORY_SEPARATOR . 'lelai_server_public_key.pem');
        $privateKey = file_get_contents($frameworkDir . DIRECTORY_SEPARATOR . 'env' . DIRECTORY_SEPARATOR . 'lelai_private_key.pem');
        $rsa->setEncryptionMode(RSA::ENCRYPTION_PKCS1);
        $plaintext = '123';
        $rsa->loadKey($privateKey);
        $ciphertext = $rsa->encrypt($plaintext);
        echo $ciphertext . PHP_EOL;
        $rsa->loadKey($publicKey);
        $text = $rsa->decrypt($ciphertext);
        echo $text . PHP_EOL;
    }


    /**
     * 确认签收订单
     * php yii index/order-pending-comment
     */
    public function actionOrderPendingComment()
    {
        $orderIds = [37682, 37683, 37697, 37712, 37727, 37730, 37734, 37736, 37737, 37745, 37746, 37752, 37758, 37763, 37770, 37771, 37780, 37811, 37830, 37831, 37836, 37844, 37846, 37848, 37859, 37864, 37890, 37905, 37944, 37945, 37952, 37953, 37957, 37965, 37997, 38026, 38028, 38029, 38031, 38061, 38067, 38070, 38098, 38103, 38115, 38119, 38132, 38134, 38141, 38173, 38174, 38177, 38178, 38180, 38191, 38228, 38242, 38246, 38247, 38255, 38267, 38289, 38290, 38303, 38316, 38323, 38329, 38403, 38428, 38446, 38484, 38489, 38514, 38522, 38565, 38567, 38568, 38584, 38585, 38598, 38615, 38630, 38654, 38693, 38701, 38721, 38727, 38734, 38745, 38763, 38771, 38781, 38794, 38806, 38807, 38822, 38823, 38825, 38827, 38833, 38847, 38860, 38861, 38862, 38886, 38887, 38890, 38891, 38898, 38909, 38916, 38917, 38923, 38950, 38957, 38958, 38978, 38984, 38985, 39014, 39045, 39047, 39055, 39095, 39102, 39124, 39142, 39148, 39166, 39191, 39198];
        $orders = SalesFlatOrder::findAll(['entity_id' => $orderIds]);
        foreach ($orders as $order) {
            // 订单完成通知customer,处理返现的问题
            $name = Events::EVENT_ORDER_PENDING_COMMENT;
            $eventName = Events::getCustomerEventName($name);
            $event = [
                'name' => $name,
                'data' => $order->toArray(),
            ];
            Proxy::sendMessage($eventName, $event);
        }

    }


    /**
     * 取消订单
     * php yii index/order-cancel
     */
    public function actionOrderCancel()
    {
        $orderIds = [39896, 39908, 41405];
        $orders = SalesFlatOrder::findAll(['entity_id' => $orderIds]);
        foreach ($orders as $order) {
            // 订单完成通知customer,处理返现的问题
            $name = Events::EVENT_ORDER_CANCEL;
            $eventName = Events::getCustomerEventName($name);
            $event = [
                'name' => $name,
                'data' => $order->toArray(),
            ];
            Proxy::sendMessage($eventName, $event);
        }

    }

    /**
     * 供应商同意取消订单
     * php yii index/order-agree-cancel
     * @deprecated
     * 该功能已经放到运营后台，这个方法已不能执行
     */
//    public function actionOrderAgreeCancel()
//    {
//        $orderIds = [39862, 40787, 41665, 42336];
//        $orders = SalesFlatOrder::findAll(['entity_id' => $orderIds]);
//        foreach ($orders as $order) {
//            // 供应商同意取消订单,发消息给customer退零钱
//            $name = 'order_agree_cancel';
//            $eventName = Events::getCustomerEventName($name);
//            $event = [
//                'name' => $name,
//                'data' => $order->toArray(),
//            ];
//            Proxy::sendMessage($eventName, $event);
//        }
//    }

    /**
     * 供应商拒单
     * php yii index/order-decline
     * @deprecated
     * 该功能已经放到运营后台，这个方法已不能执行
     */
//    public function actionOrderDecline()
//    {
//        $orderIds = [38975, 40879];
//        $orders = SalesFlatOrder::findAll(['entity_id' => $orderIds]);
//        foreach ($orders as $order) {
//            // 供应商拒单,发消息给customer退零钱
//            $name = Events::EVENT_ORDER_DECLINE;
//            $eventName = Events::getCustomerEventName($name);
//            $event = [
//                'name' => $name,
//                'data' => $order->toArray(),
//            ];
//            Proxy::sendMessage($eventName, $event);
//        }
//    }

    /**
     * 超市拒收
     * php yii index/order-reject
     */
    public function actionOrderReject()
    {
        $orderIds = [39368, 41567, 43402, 44134, 49799, 53276, 53696, 54576, 54911, 55790, 55804, 56126, 61078, 61489, 61802, 62012, 62367, 64151];
        $orders = SalesFlatOrder::findAll(['entity_id' => $orderIds]);
        foreach ($orders as $order) {
            // 超市拒收,发消息给customer退零钱
            $name = Events::EVENT_ORDER_REJECT;
            $eventName = Events::getCustomerEventName($name);
            $event = [
                'name' => $name,
                'data' => $order->toArray(),
            ];
            Proxy::sendMessage($eventName, $event);
        }
    }


    public function actionContractorHomeData()
    {
        $contractor_id = 6;
        $customer_ids = [
            1348, 1349, 1357, 1446, 1464, 1585, 1611, 1666, 1680, 1690, 1699, 1738, 1808, 1816, 1886, 1941, 1954, 1955, 1969, 1970, 1983, 1985, 2023, 2043, 2071, 2085, 2090, 2092, 2093, 2096, 2097, 2098, 2117, 2118, 2119, 2133, 2138, 2142, 2143, 2146, 2147, 2148, 2154, 2160
        ];
        $requestData = [
            'contractor_id' => $contractor_id,
            'store_ids' => $customer_ids,
        ];
        $request = new ContractorHomeDataRequest();
        $request->setFrom($requestData);

        $model = new contractorHomeData();
        $response = $model->run($request->serializeToString());

        print_r($response->toArray());

    }


    public function actionFlushContractorData()
    {
        $date = date('Y-m-d', strtotime('-31 days'));
        $data = [];
        while ($date <= date('Y-m-d')) {
            $startDate = date('Y-m-d 16:00:00', strtotime('-1 day', strtotime($date)));
            $endDate = date('Y-m-d 16:00:00', strtotime($date));
            $order_collection = SalesFlatOrder::find()->select(['entity_id', 'is_first_order', 'grand_total', 'contractor_id', 'city'])
                ->where(['between', 'created_at', $startDate, $endDate])
                ->andWhere(['customer_tag_id' => 1, 'merchant_type_id' => 1])
                ->all();
            /** @var SalesFlatOrder $order */
            foreach ($order_collection as $order) {
                if (isset($data[$date][$order->city][$order->contractor_id]['sales_total'])) {
                    $data[$date][$order->city][$order->contractor_id]['sales_total'] += $order->grand_total;
                } else {
                    $data[$date][$order->city][$order->contractor_id]['sales_total'] = $order->grand_total;
                }

                if ($order->is_first_order == 1) {
                    if (isset($data[$date][$order->city][$order->contractor_id]['first_total'])) {
                        $data[$date][$order->city][$order->contractor_id]['first_total'] += 1;
                    } else {
                        $data[$date][$order->city][$order->contractor_id]['first_total'] = 1;
                    }
                }

                if (isset($data[$date][$order->city][$order->contractor_id]['count'])) {
                    $data[$date][$order->city][$order->contractor_id]['count'] += 1;
                } else {
                    $data[$date][$order->city][$order->contractor_id]['count'] = 1;
                }
            }
            //print_r($startDate.'==='.$date.'==='.$endDate);
            $date = date('Y-m-d', strtotime('+1 day', strtotime($date)));
            //echo PHP_EOL;
        };

//        print_r($data);
//        exit();
        foreach ($data as $date => $item1) {
            foreach ($item1 as $city => $item2) {
                foreach ($item2 as $contractor_id => $item3) {
                    /** @var ContractorStatisticsData $contractor_statistics_data */
                    $contractor_statistics_data = ContractorStatisticsData::find()
                        ->where(['city' => $city, 'date' => $date, 'contractor_id' => $contractor_id])->one();
                    if (!$contractor_statistics_data) {
                        $contractor_statistics_data = new ContractorStatisticsData();
                        $contractor_statistics_data->city = $city;
                        $contractor_statistics_data->date = $date;
                        $contractor_statistics_data->contractor_id = $contractor_id;
                    }
                    $contractor_statistics_data->sales_total = isset($item3['sales_total']) ? $item3['sales_total'] : 0;
                    $contractor_statistics_data->first_users = isset($item3['first_total']) ? $item3['first_total'] : 0;
                    $contractor_statistics_data->orders_count = isset($item3['count']) ? $item3['count'] : 0;
                    $contractor_statistics_data->save();
                    if ($contractor_statistics_data->hasErrors()) {
                        print_r($contractor_statistics_data->errors);
                        exit();
                    }
                    echo '.';
                }
            }
        }

    }

    public function actionTest()
    {
        $city = AvailableCity::find()->select(['city_name', 'city_code', 'province_name', 'province_code'])->asArray()->all();

        print_r($city);
    }

    public function actionManageCrontab()
    {
        $redis = ToolsAbstract::getRedis();
        $timer_key = ToolsAbstract::getCrontabKey();

//        $data1  = array(
//            'type' => 2,    //类型：1一次执行【执行完删除】，2多次执行
//            'time' => "* * * * *",    //定时时间格式
//            'data'=>"{\"cmd\":\"crontab.test\",\"data\":\"test data\"}",
//        );
//
//        $data2  = array(
//            'type' => 2,    //类型：1一次执行【执行完删除】，2多次执行
//            'time' => "*/10 * * * *",    //定时时间格式
//            'data'=>"{\"cmd\":\"crontab.test1\",\"data\":\"test data\"}",
//        );
//
//        $data3  = array(
//            'type' => 2,    //类型：1一次执行【执行完删除】，2多次执行
//            'time' => "10 * * * *",    //定时时间格式
//            'data'=>"{\"cmd\":\"crontab.test2\",\"data\":\"test data\"}",
//        );

        $data4 = array(
            'type' => 2,    //类型：1一次执行【执行完删除】，2多次执行
            'time' => "* * * * * *",    //定时时间格式
            'data' => [
                'route' => "task.test",
                "params" => "test data",
            ]
        );


//        $redis->sAdd($timer_key, json_encode($data1, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
//        $redis->sAdd($timer_key, json_encode($data2, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
//        $redis->sAdd($timer_key, json_encode($data3, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        $redis->sAdd($timer_key, json_encode($data4));

        $list = $redis->sMembers($timer_key);
        echo print_r($list, true);
    }


}
