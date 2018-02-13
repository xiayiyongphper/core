<?php
namespace service\resources\sales\v1;

use common\models\SalesFlatOrder;
use service\components\Tools;
use service\message\contractor\StoresResponse;
use service\message\core\RecentlyOrderStoresRequest;
use service\resources\Exception;
use service\resources\ResourceAbstract;

/**
 * 获取最近N天下了M1至M2单的店铺
 * Class getRecentlyOrderStores
 * @package service\resources\sales\v1
 */
class getRecentlyOrderStores extends ResourceAbstract
{
    /**
     * @param string $data
     * @return StoresResponse
     */
    public function run($data)
    {
        /* @var $request RecentlyOrderStoresRequest */
        $request = self::request();
        $request->parseFromString($data);
        $response = self::response();
        $contractor_id = $request->getContractorId();
        $auth_token = $request->getAuthToken();

        $contractor = $this->_initContractor($contractor_id, $auth_token);
        if (!$contractor) {
            Exception::contractorInitError();
        }

        $city = $request->getCity();
        if (!$city) {
            Exception::contractorCityEmpty();
        }

        if (!is_numeric($request->getDay()) || $request->getDay() < 0) {
            throw new \Exception('参数错误', 201);
        }

        if (!is_numeric($request->getMin()) || $request->getMin() < 0) {
            throw new \Exception('最小下单数参数错误', 201);
        }

        if (!is_numeric($request->getMax()) || $request->getMax() < 0) {
            throw new \Exception('最大下单数参数错误', 201);
        }

        $datetime = date('Y-m-d 00:00:00', strtotime('-' . $request->getDay() . 'days +8HOURS'));
        $results = SalesFlatOrder::find()
            ->select('customer_id,count(1) AS total')->where([
                'and',
                ['city' => $request->getCity()],
                ['>=', 'created_at', $datetime],
            ])->groupBy('customer_id')
            ->having(['>=', 'total', $request->getMin()])
            ->andHaving(['<=', 'total', $request->getMax()])
//            ->createCommand()->getRawSql();
            ->asArray()->all();

        foreach ($results as $k => $result) {
            unset($results[$k]['total']);
        }

        $respData['stores'] = $results;

        $response->setFrom(Tools::pb_array_filter($respData));
        return $response;
    }

    /**
     * @return RecentlyOrderStoresRequest
     */
    public static function request()
    {
        return new RecentlyOrderStoresRequest();
    }

    /**
     * @return StoresResponse
     */
    public static function response()
    {
        return new StoresResponse();
    }
}