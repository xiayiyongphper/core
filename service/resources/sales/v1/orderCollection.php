<?php

namespace service\resources\sales\v1;

use common\models\SalesFlatOrder;
use framework\components\Date;
use framework\data\Pagination;
use service\components\Tools;
use service\message\sales\OrderCollectionRequest;
use service\message\sales\OrderCollectionResponse;
use service\resources\ResourceAbstract;

/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2016/1/21
 * Time: 15:09
 */
class orderCollection extends ResourceAbstract
{
    const DEFAULT_PAGE_SIZE = 10;

    public function run($data)
    {
        /** @var OrderCollectionRequest $request */
        $request = self::request();
        $request->parseFromString($data);
        //$this->_initCustomer($request);
        //接口验证用户
        $response = new OrderCollectionResponse();
        $pageSize = self::DEFAULT_PAGE_SIZE;
        $page = $request->getPage() ? $request->getPage() : 1;
        $customerId = $request->getCustomerId();

        $query = SalesFlatOrder::find();
        $orderTableName = SalesFlatOrder::tableName();
        $query->where(["{$orderTableName}.customer_id" => $customerId]);
        //$query->joinWith('item');
        $query->joinWith('orderstatus');
        switch ($request->getState()) {
            case 'receiving':
                /** 待收货 */
                $status = array(
//                    SalesFlatOrder::STATUS_PROCESSING,
                    SalesFlatOrder::STATUS_PROCESSING_RECEIVE,
                    SalesFlatOrder::STATUS_PROCESSING_SHIPPING,
                );
                break;
            case 'refund':
                /** 退款退货 */
                $status = array(
                    SalesFlatOrder::STATUS_CANCELED,
                    SalesFlatOrder::STATUS_REJECTED_CLOSED,
                    SalesFlatOrder::STATUS_CLOSED,
                    SalesFlatOrder::STATUS_WAITING_REFUND,
                    SalesFlatOrder::STATUS_REJECTED_WAITING_REFUND,
                );
                break;
            case 'complete':
                /** 交易成功 */
                $status = array(
                    SalesFlatOrder::STATUS_COMPLETE,
                );
                break;
            case 'processing':
                /** 待接单 */
                $status = array(
                    SalesFlatOrder::STATUS_PROCESSING,
                );
                break;
            case 'pending_comment':
                /** 待评价 */
                $status = array(
                    SalesFlatOrder::STATUS_PENDING_COMMENT,
                );
                break;
            case 'holded':
                /** 待取消 */
                $status = array(
                    SalesFlatOrder::STATUS_HOLDED,
                );
                break;
            case 'all':
                /** 全部 */
            default:
                $status = false;
        }
        if (is_array($status)) {
            $query->andWhere(["$orderTableName.status" => $status]);
        }

        if ($time = $request->getTime()) {
            $time = date("Y-m-d H:i:s");
            $query->andWhere(['<', "$orderTableName.created_at", $time]);
        }

        $countQuery = clone $query;

        $pages = new Pagination(['totalCount' => $countQuery->count()]);
        $pages->setCurPage($page);
        $pages->setPageSize($pageSize);

        $orders = $query->offset($pages->getOffset())
            ->limit($pages->getLimit())
            ->orderBy(["$orderTableName.entity_id" => SORT_DESC]);

        $sql = $orders->createCommand()->getRawSql();
        $start = microtime(true);
        Tools::log($customerId . ':' . microtime(true), 'orderCollection.log');
        $orders = $orders->all();
        $end = microtime(true);
        $time = $end - $start;
        if ($time > 2) {
            Tools::log($customerId . ':' . $sql, 'orderCollection.log');
            Tools::log($customerId . ':' . $time, 'orderCollection.log');
        }
        $responseArray = [
            'pagination' => [
                'total_count' => $pages->getTotalCount(),
                'page' => $pages->getCurPage(),
                'last_page' => $pages->getLastPageNumber(),
            ],
        ];
        $items = [];

        foreach ($orders as $_order) {
            /** @var SalesFlatOrder $_order */
            if ($_order->orderstatus && $_order->orderstatus->label) {
                $statusLabel = $_order->orderstatus->label;
            } else {
                $statusLabel = $_order->status;
            }
            $date = new Date();
            $order = [
                'order_id' => $_order->getPrimaryKey(),
                'status' => $_order->status,
                'status_label' => $statusLabel,
                'wholesaler_id' => $_order->wholesaler_id,
                'wholesaler_name' => $_order->wholesaler_name,
                'payment_method' => $_order->payment_method,
                //'coupon_code'=>$_order['coupon_code'],
                'increment_id' => $_order->increment_id,
                'grand_total' => $_order->grand_total,
                'created_at' => $date->date('Y-m-d H:i:s', $_order->created_at),
                'totals' => [
                    'base_total' => $_order->subtotal,
                    'shipping_amount' => $_order->shipping_amount,
                    'discount_amount' => $_order->discount_amount,
                    'coupon_discount_amount' => $_order->coupon_discount_amount,
                    'grand_total' => $_order->grand_total,
                    'total_qty' => $_order->total_qty_ordered,
                    'balance' => $_order->balance,
                    'receipt_total' => $_order->receipt_total,
                ]
            ];
            $orderItems = [];
            $total_qty_ordered = 0;
            foreach ($_order->getItemsCollection() as $_orderItem) {
                $orderItems[] = array_filter([
                    'item_id' => $_orderItem['item_id'],
                    'product_id' => $_orderItem['product_id'],
                    'name' => $_orderItem['name'],
                    'price' => round($_orderItem['price'], 2),
                    'qty' => $_orderItem['qty'],
                    'image' => $_orderItem['image'],
                    'type' => !empty($_orderItem['product_type']) ? $_orderItem['product_type'] : 0
                ]);
                $total_qty_ordered += $_orderItem['qty'];
            }
            $order['total_qty_ordered'] = $total_qty_ordered;
            if (count($orderItems) > 0) {
                $order['items'] = $orderItems;
            }
            $items[] = array_filter($order);
        }

        if (count($items) > 0) {
            $responseArray['items'] = $items;
        }

        $response->setFrom(Tools::pb_array_filter($responseArray));
        return $response;
    }

    public static function request()
    {
        return new OrderCollectionRequest();
    }

    public static function response()
    {
        return new OrderCollectionResponse();
    }
}
