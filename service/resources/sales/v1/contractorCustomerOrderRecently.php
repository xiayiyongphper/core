<?php

namespace service\resources\sales\v1;

use common\models\SalesFlatOrder;
use framework\components\Date;
use framework\components\ToolsAbstract;
use framework\data\Pagination;
use service\components\ContractorPermission;
use service\components\Tools;
use service\message\contractor\OrderListRequest;
use service\message\sales\OrderCollectionResponse;
use service\resources\Exception;
use service\resources\ResourceAbstract;

/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2016/1/21
 * Time: 15:09
 * Since: 业务员1.7版本，超市近期订单接口
 */
class contractorCustomerOrderRecently extends ResourceAbstract
{

    public function run($data)
    {
        /** @var OrderListRequest $request */
        $request = self::request();
        $request->parseFromString($data);
        $contractor_id = $request->getContractorId();
        $auth_token = $request->getAuthToken();

        $contractor = $this->_initContractor($contractor_id, $auth_token);

        if (!$contractor) {
            Exception::contractorInitError();
        }

        if (!ContractorPermission::orderTrackingCollectionPermission($contractor->getRolePermission())) {
            Exception::contractorPermissionError();
        }

        $city = $request->getCity();

        if (!$city) {
            Exception::contractorCityEmpty();
        }

        $response = new OrderCollectionResponse();

        $keyword = $request->getKeyword();
        $wholesaler_ids = $request->getWholesalerId();
        $contractor_ids = $request->getContractorIds();
        $customer_ids = $request->getCustomerId();
        $status = $request->getStatus();
        $time_from = $request->getTimeFrom();
        $time_to = $request->getTimeTo();
        $time_lt = $request->getTimeLt();

        $date = new Date();

        if ($contractor->getRole() == self::COMMON_CONTRACTOR) {
            $conditions = ['contractor_id' => $contractor_id];
        } else {
            $conditions = ['city' => $city];

            //业务员查询
            if (count($contractor_ids)) {
                $conditions = ['and', ['in', 'contractor_id', $contractor_ids], $conditions];
            }
        }

        //时间查询
        if ($time_from) {
            //客户端只传年月日
            $time_from = date('Y-m-d 16:00:00', strtotime('-1 day', strtotime($time_from)));
            $conditions = ['and', ['>', 'created_at', $time_from], $conditions];
        }

        if ($time_to) {
            //客户端只传年月日
            $time_to = $time_to . ' 16:00:00';
            $conditions = ['and', ['<', 'created_at', $time_to], $conditions];
        }

        //关键字查询
        if ($keyword) {
            if (is_numeric($keyword)) {
                $conditions = ['and', ['like', 'increment_id', $keyword], $conditions];
            } else {
                $conditions = ['and', ['like', 'store_name', $keyword], $conditions];
            }
        }

        //供应商查询
        if (count($wholesaler_ids)) {
            $conditions = ['and', ['in', 'wholesaler_id', $wholesaler_ids], $conditions];
        }

        //超市查询
        if (count($customer_ids)) {
            $conditions = ['and', ['in', 'customer_id', $customer_ids], $conditions];
        }

        //状态查询
        if (count($status)) {
            $conditions = ['and', ['in', 'sales_flat_order.status', $status], $conditions];
        }

        $query = SalesFlatOrder::find()->where($conditions)->andWhere(['customer_tag_id' => [1,6,8],
            'merchant_type_id' =>  [1,6,8]])->orderBy('created_at desc');
        $query->joinWith('orderstatus');
        $query->limit(30);

        $amount = $query->sum('grand_total');
        //数量，最多30
        $totalCount = $query->count() >= 30 ? 30 : $query->count();

        $responseArray['order_count'] = $totalCount;
        $responseArray['order_amount'] = $amount;

        $pages = new Pagination(['totalCount' => $totalCount]);
        $pages->setCurPage(1);
        $pages->setPageSize(30);

        $pagination = [
            'total_count' => $pages->totalCount,
            'page' => $pages->getCurPage(),
            'last_page' => $pages->getLastPageNumber(),
            'page_size' => $pages->getPageSize(),
        ];

        Tools::log($pagination, 'contractorCustomerOrderRecently.log');

        $orders = $query->all();

        $responseArray['items'] = [];
        foreach ($orders as $_order) {
            /** @var SalesFlatOrder $_order */
            if ($_order->orderstatus && $_order->orderstatus->label) {
                $statusLabel = $_order->orderstatus->label;
            } else {
                $statusLabel = $_order->status;
            }

            $order = [
                'order_id' => $_order->getPrimaryKey(),
                'increment_id' => $_order->increment_id,
                'wholesaler_id' => $_order->wholesaler_id,
                'wholesaler_name' => $_order->wholesaler_name,
                'store_name' => $_order->store_name,
                'status' => $_order->status,
                'status_label' => $statusLabel,
                'created_at' => $date->date('Y-m-d H:i', $_order->created_at),
                'grand_total' => $_order->grand_total
            ];
            $responseArray['items'][] = $order;
        }
        $responseArray['pagination'] = $pagination;

        $response->setFrom(ToolsAbstract::pb_array_filter($responseArray));
        return $response;
    }

    public static function request()
    {
        return new OrderListRequest();
    }

    public static function response()
    {
        return new OrderCollectionResponse();
    }
}