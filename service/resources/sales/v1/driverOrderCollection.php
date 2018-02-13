<?php
namespace service\resources\sales\v1;

use common\models\SalesFlatOrder;
use framework\components\ToolsAbstract;
use framework\data\Pagination;
use service\message\sales\DriverOrderCollectionRequest;
use service\message\sales\OrderCollectionResponse;
use service\resources\Exception;
use service\resources\ResourceAbstract;
use yii\db\Expression;

/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2016/1/21
 * Time: 15:09
 */
class driverOrderCollection extends ResourceAbstract
{
    const MAX_PAGE_SIZE = 10;

    public function run($data)
    {
        if ($this->isRemote()) {
            Exception::resourceNotFound();
        }
        /** @var DriverOrderCollectionRequest $request */
        $request = self::request();
        $request->parseFromString($data);
        $response = self::response();
        $pageSize = self::MAX_PAGE_SIZE;

        $orderIds = $request->getOrderIds();
        $query = SalesFlatOrder::find();
        $orderTableName = SalesFlatOrder::tableName();
        if (count($orderIds) > 0) {
            $query->andWhere(["{$orderTableName}.entity_id" => $orderIds]);
            $order = implode(',',$orderIds);
            $order_by = [new Expression("FIELD (`{$orderTableName}`.`entity_id`,".$order.")")];
            $query->orderBy($order_by);
        }
        $fuzzy_increment_id = $request->getFuzzyIncrementId();
        if ($fuzzy_increment_id) {
            $query->andWhere(['like', 'increment_id', $fuzzy_increment_id]);
        }

        $wholesaler_id = $request->getWholesalerId();
        if ($wholesaler_id && count($wholesaler_id) > 0) {
            $query->andWhere(['wholesaler_id' => $wholesaler_id]);
        }

        $query->joinWith('orderstatus');
        $query->joinWith('orderaddress');

        $countQuery = clone $query;
        $pages = new Pagination(['totalCount' => $countQuery->count()]);
        $pages->setCurPage(1);
        $pages->setPageSize($pageSize);

        $orders = $query->offset($pages->getOffset())
            ->limit($pages->getLimit())
            ->all();
        $responseArray = [];
        $items = [];
        $date = ToolsAbstract::getDate();
        if ($pages->getTotalCount() == 0) {
            Exception::orderNotExisted();
        }
        foreach ($orders as $_order) {
            /** @var SalesFlatOrder $_order */
            if ($_order->orderstatus && $_order->orderstatus->label) {
                $statusLabel = $_order->orderstatus->label;
            } else {
                $statusLabel = $_order->status;
            }

            if ($_order->orderaddress) {
                $name = $_order->orderaddress->name;
                $phone = $_order->orderaddress->phone;
                $address = $_order->orderaddress->address;
            }
            $order = [
                'order_id' => $_order->getPrimaryKey(),
                'increment_id' => $_order->increment_id,
                'wholesaler_id' => $_order->wholesaler_id,
                'wholesaler_name' => $_order->wholesaler_name,
                'store_name' => $_order->store_name,
                'comment' => $_order->customer_note,
                'status' => $_order->status,
                'state' => $_order->state,
                'status_label' => $statusLabel,
                'payment_method' => $_order['payment_method'],
                'created_at' => $date->date('Y-m-d H:i:s', $_order->created_at),
                'name' => isset($name) ? $name : '',
                'phone' => isset($phone) ? $phone : '',
                'address' => isset($address) ? $address : '',
                'totals' => [
                    'grand_total' => $_order->grand_total,
                ]
            ];
            $items[] = $order;
        }

        if (count($items) > 0) {
            $responseArray['items'] = $items;
        }
        $response->setFrom(ToolsAbstract::pb_array_filter($responseArray));
        return $response;
    }

    public static function request()
    {
        return new DriverOrderCollectionRequest();
    }

    public static function response()
    {
        return new OrderCollectionResponse();
    }
}
