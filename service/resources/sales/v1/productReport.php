<?php

namespace service\resources\sales\v1;

use common\models\SalesFlatOrder;
use framework\components\es\Console;
use framework\data\Pagination;
use service\message\common\KeyValueItem;
use service\message\sales\ProductReportRequest;
use service\message\sales\ProductReportResponse;
use service\resources\Exception;
use service\resources\ResourceAbstract;
use yii\db\Expression;

/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2016/1/21
 * Time: 15:09
 */
class productReport extends ResourceAbstract
{
    const PAGE_SIZE = 10;
    const MAX_PAGE_SIZE = 50;

    public function run($data)
    {
        /** @var \service\message\sales\ProductReportRequest $request */
        $request = self::request();
        $request->parseFromString($data);
        if ($this->isRemote()) {
            Exception::invalidRequestRoute();
        }
        $filters = $this->processKeyValueItem($request->getFilters());

        $columns = [
            'sales_flat_order_item.product_id',
            'frequency' => new Expression('COUNT(*)'),
            'sales_flat_order_item.wholesaler_id'
        ];
        $response = self::response();
        $items = SalesFlatOrder::find()->joinWith('item')->select($columns)->where('1=1');
        if (isset($filters['customer_id'])) {
            $items->andWhere(['sales_flat_order.customer_id' => $filters['customer_id']]);
        }

        if (isset($filters['time_range'])) {
            list($startTime, $endTime) = explode('TO', $filters['time_range']);
            $items->andWhere(['>=', 'sales_flat_order.created_at', $startTime])
                ->andWhere(['<=', 'sales_flat_order.created_at', $endTime]);
        }

        if (isset($filters['category_id'], $filters['category_level'])) {
            switch ($filters['category_level']) {
                case 2:
                    $categoryField = 'second_category_id';
                    break;
                case 3:
                    $categoryField = 'third_category_id';
                    break;
                case 1:
                default:
                    $categoryField = 'first_category_id';
                    break;
            }
            $items->andWhere(['sales_flat_order_item.' . $categoryField => $filters['category_id']]);
        }

        if (isset($filters['wholesaler_id'])) {
            $wholesalerIds = explode(',', $filters['wholesaler_id']);
            $items->andWhere(['in', 'sales_flat_order.wholesaler_id', $wholesalerIds]);
        }


        $items->groupBy('sales_flat_order_item.product_id')
            ->orderBy('frequency desc, sales_flat_order_item.item_id desc');
        Console::get()->log($items->createCommand()->getRawSql(), 'productReport.sql', ['productReport']);
        $totalCount = $items->count();
        $page = $request->getPagination()->getPage() ? $request->getPagination()->getPage() : 1;
        $pages = new Pagination(['totalCount' => $totalCount]);
        $pages->setCurPage($page);
        $pages->setPageSize(self::PAGE_SIZE);
        $responseData = [
            'pages' => [
                'total_count' => $pages->getTotalCount(),
                'page' => $pages->getCurPage(),
                'last_page' => $pages->getLastPageNumber(),
                'page_size' => $pages->getPageSize(),
            ],
            'group_size' => count($columns),
            'items' => []

        ];

        if ($page > $pages->getLastPageNumber()) {
            $page = $pages->getLastPageNumber();
        }
        if ($page <= 0) {
            $page = 1;
        }

        $items = $items->offset(($page - 1) * self::PAGE_SIZE)->limit(self::PAGE_SIZE)->createCommand()->queryAll();
        foreach ($items as $item) {
            foreach ($item as $key => $value) {
                $responseData['items'][] = [
                    'key' => $key,
                    'value' => $value
                ];
            }
        }
        $response->setFrom($responseData);
        return $response;
    }

    /**
     * @param $items
     * @return array|bool
     */
    protected function processKeyValueItem($items)
    {
        $obj = false;
        if (count($items) > 0) {
            $obj = [];
            /** @var KeyValueItem $item */
            foreach ($items as $item) {
                $obj[$item->getKey()] = $item->getValue();
            }
        }
        return $obj;
    }

    public static function request()
    {
        return new ProductReportRequest();
    }

    public static function response()
    {
        return new ProductReportResponse();
    }
}