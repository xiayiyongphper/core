<?php
namespace service\resources\sales\v1;

use common\models\SalesFlatOrder;
use common\models\SalesFlatOrderAddress;
use common\models\SalesOrderStatus;
use framework\components\ToolsAbstract;
use service\message\common\Order;
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
class driverOrderDetail extends ResourceAbstract
{
    const DETAIL_LEVEL_FEW = 1;
    const DETAIL_LEVEL_LITTLE = 2;
    const DETAIL_LEVEL_LESS = 3;
    const DETAIL_LEVEL_MORE = 4;
    const DETAIL_LEVEL_FULL = 5;

    public function run($data)
    {
        if ($this->isRemote()) {
            Exception::resourceNotFound();
        }
        /** @var \service\message\sales\DriverOrderDetailRequest $request */
        $request = self::request();
        $request->parseFromString($data);
        $response = self::response();
        if (!$request->getIncrementId() && !$request->getOrderId()) {
            Exception::orderNotExisted();
        }
        $responseData = $this->getFew($request);
        $response->setFrom(ToolsAbstract::pb_array_filter($responseData));
        return $response;
    }

    public static function request()
    {
        return new DriverOrderDetailRequest();
    }

    public static function response()
    {
        return new Order();
    }

    /**
     * @param \service\message\sales\DriverOrderDetailRequest $request
     * @return array
     */
    protected function getFew($request)
    {
        if ($request->getIncrementId()) {
            $order = $this->getOrder($request->getIncrementId());
        } elseif ($request->getOrderId()) {
            $order = $this->getOrder($request->getOrderId(), 'entity_id');
        } else {
            Exception::orderNotExisted();
        }

        $address = $this->getAddress($order['entity_id']);
        $date = ToolsAbstract::getDate();
        return [
            'order_id' => $order['entity_id'],
            'increment_id' => $order['increment_id'],
            'wholesaler_id' => $order['wholesaler_id'],
            'wholesaler_name' => $order['wholesaler_name'],
            'store_name' => $order['store_name'],
            'customer_id' => $order['customer_id'],
            'customer_name' => $order['store_name'],
            'comment' => $order['customer_note'],
            'status' => $order['status'],
            'state' => $order['state'],
            'status_label' => ToolsAbstract::arrayGetString($this->getStatus($order['status']), 'label', $order['status']),
            'payment_method' => $order['payment_method'],
            'created_at' => $date->date('Y-m-d H:i:s', $order['created_at']),
            'name' => $address['name'],
            'phone' => $address['phone'],
            'address' => $address['address'],
            'totals' => [
                'grand_total' => $order['grand_total'],
            ]
        ];

    }

    /**
     * @param $incrementId
     * @param string $field
     * @param bool $withItem
     * @param bool $asArray
     * @param bool $throwException
     * @return array|null|\yii\db\ActiveRecord
     * @throws \Exception
     */
    protected function getOrder($incrementId, $field = 'increment_id', $withItem = false, $asArray = true, $throwException = true)
    {
        $model = SalesFlatOrder::find();
        if ($withItem) {
            $model->joinWith('item');
        }
        $order = $model->where([$field => $incrementId])
            ->asArray($asArray)
            ->one();
        if ($throwException && !$order) {
            Exception::orderNotExisted();
        }
        return $order;
    }

    protected function getStatus($status, $asArray = true)
    {
        return SalesOrderStatus::find()->where(['status' => $status])->asArray($asArray)->one();
    }

    protected function getAddress($orderId, $asArray = true)
    {
        return SalesFlatOrderAddress::find()->where(['order_id' => $orderId])->asArray($asArray)->one();
    }
}