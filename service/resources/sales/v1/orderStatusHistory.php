<?php
namespace service\resources\sales\v1;

use common\models\SalesFlatOrder;
use common\models\SalesFlatOrderStatusHistory;
use framework\components\Date;
use service\components\Proxy;
use service\components\Tools;
use service\message\common\Order;
use service\message\sales\OrderStatusHistoryRequest;
use service\resources\Exception;
use service\resources\ResourceAbstract;

/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2016/1/21
 * Time: 15:09
 */
class orderStatusHistory extends ResourceAbstract
{
    public function run($data)
    {
        /** @var OrderStatusHistoryRequest $request */
        $request = self::request();
        $request->parseFromString($data);
        $customer = $this->_initCustomer($request);
        $response = new Order();
        if (!$request->getOrderId()) {
            Exception::orderNotExisted();
        }
        $order = SalesFlatOrder::find()->where(['entity_id' => $request->getOrderId()])
            ->joinWith('orderstatus')
            ->asArray()->one();
        if (!$order['entity_id']) {
            Exception::orderNotExisted();
        }
        $wholesaler = Proxy::getWholesaler($order['wholesaler_id'],$this->getTraceId(),$customer);
        $statusHistory = SalesFlatOrderStatusHistory::find()
            ->joinWith('orderstatus')
            ->where(['parent_id' => $request->getOrderId()])
            ->andWhere(['is_visible_to_customer' => 1])
            ->orderBy(['entity_id'=>SORT_DESC])
            ->asArray()
            ->all();
        if (isset($order['orderstatus']['label'])) {
            $orderStatusLabel = $order['orderstatus']['label'];
        } else {
            $orderStatusLabel = $order['status'];
        }
        $data = [
            'increment_id' => $order['increment_id'],
            'wholesaler_id' => $order['wholesaler_id'],
            'wholesaler_name' => $order['wholesaler_name'],
            'status' => $order['status'],
            'status_label' => $orderStatusLabel,
        ];
        if ($wholesaler->getPhone() && count($wholesaler->getPhone()) > 0) {
            $data['store_phone'] = $wholesaler->getPhone();
        }
        $data = array_filter($data);
        $items = array();
        $date = new Date();
        foreach ($statusHistory as $history) {
            if (isset($history['orderstatus']['label'])) {
                $historyStatusLabel = $history['orderstatus']['label'];
            } else {
                $historyStatusLabel = $history['status'];
            }
            $items[] = array_filter([
                'history_id' => $history['entity_id'],
                'status' => $historyStatusLabel,
                'comment' => $history['comment'],
                'created_at' => $date->date('Y-m-d H:i:s', $history['created_at']),
            ]);
        }
        if (count($items) > 0) {
            $data['history'] = $items;
        }
        //供应商支持三赔才展示
        if($wholesaler->getCompensationService() == 1){
            $data['tag'] = [
                'text' => '送货慢立即现金赔偿,点击查看',
                'icon' => 'http://assets.lelai.com/assets/secimgs/homepei.png',
                'url' => 'http://assets.lelai.com/assets/h5/security/'
            ];
        }

        //Tools::log($data,'wangyang.log');
        $response->setFrom(Tools::pb_array_filter($data));
        return $response;
    }

    public static function request()
    {
        return new OrderStatusHistoryRequest();
    }

    public static function response()
    {
        return new Order();
    }
}
