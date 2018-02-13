<?php
namespace service\resources\sales\v1;

use common\models\SalesFlatOrder;
use service\components\Tools;
use service\message\core\getWholesalerRequest;
use service\message\core\getWholesalerResponse;
use service\resources\ResourceAbstract;

/**
 * Class getWholesaler
 * @package service\resources\sales\v1
 * 获取获取下过订单的供应商
 */
class getWholesaler extends ResourceAbstract
{

    public function run($data)
    {
        /** @var getWholesalerRequest $request */
        $request = self::request();
        $request->parseFromString($data);
        $response = self::response();

        $customer_id = $request->getCustomerId();

        $time = date('Y-m-d H:i:s',strtotime('-1 months -8 hours'));

        $wholesaler_ids = SalesFlatOrder::find()->select('wholesaler_id')->where(['customer_id' => $customer_id])
            ->andWhere(['>','created_at',$time])
            ->andWhere(['state' => SalesFlatOrder::STATE_COMPLETE])
            ->orderBy('created_at desc')
            ->groupBy('wholesaler_id');

        $wholesaler_ids = $wholesaler_ids->asArray()->all();

        foreach ($wholesaler_ids as $wholesaler_id){
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
