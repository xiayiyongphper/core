<?php

namespace service\resources\sales\v1;

use common\models\SalesFlatOrder;
use service\components\Tools;
use service\message\core\getWholesalerRequest;
use service\message\core\getWholesalerResponse;
use service\resources\ResourceAbstract;

/**
 * Class getRecentlyBuyWholesalerIds
 * @package service\resources\sales\v1
 * 获取最近下过单的供应商
 */
class getRecentlyBuyWholesalerIds extends ResourceAbstract
{
    const WHOLESALER_NUM = 10;

    public function run($data)
    {
        /** @var getWholesalerRequest $request */
        $request = self::request();
        $request->parseFromString($data);
        $response = self::response();

        $customer_id = $request->getCustomerId();


        $query = SalesFlatOrder::find()->select('wholesaler_id')
            ->where(['customer_id' => $customer_id]);
        //当传了供货商ID列表，将使用该列表进行过滤。只有属于该列表的供货商才展示
        if ($request->getWholesalerIdsCount() > 0) {
            $query->andWhere(['in', 'wholesaler_id', $request->getWholesalerIds()]);
        }
        $query->andWhere(['not in', 'state', [SalesFlatOrder::STATE_CANCELED, SalesFlatOrder::STATE_CLOSED]])
            ->limit(self::WHOLESALER_NUM)
            ->orderBy('created_at desc')
            ->groupBy('wholesaler_id');
        Tools::log($query->createCommand()->getRawSql(),'getRecentlyBuyWholesalerIds.log');
        $wholesaler_ids = $query->asArray()->all();

        foreach ($wholesaler_ids as $wholesaler_id) {
            $response->appendWholesalerIds($wholesaler_id['wholesaler_id']);
        }

        return $response;
    }

    public static function request()
    {
        return new getWholesalerRequest();
    }

    public static function response()
    {
        return new getWholesalerResponse();
    }
}
