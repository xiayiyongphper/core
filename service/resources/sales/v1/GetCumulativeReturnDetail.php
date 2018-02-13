<?php
/**
 * Created by PhpStorm.
 * User: ZQY
 * Date: 2017/5/23
 * Time: 12:07
 */

namespace service\resources\sales\v1;

use common\models\CumulativeReturnActivity;
use service\components\Tools;
use service\message\sales\GetCumulativeReturnDetailRequest;
use service\message\sales\GetCumulativeReturnDetailResponse;
use service\resources\ResourceAbstract;

/**
 * 获取累计满返活动详情
 *
 * Class GetCumulativeReturnDetail
 * @author zqy
 * @package service\resources\sales\v1
 */
class GetCumulativeReturnDetail extends ResourceAbstract
{
    /**
     * @param string $data
     * @return GetCumulativeReturnDetailResponse
     * @throws \Exception
     */
    public function run($data)
    {
        /** @var GetCumulativeReturnDetailRequest $request */
        $request = self::request();
        $request->parseFromString($data);
        //　初始化
        $customer = $this->_initCustomer($request);
        $response = self::response();

        $respData = CumulativeReturnActivity::getActivityDetail($customer, $request->getType());
        if (!$respData) {
            return null;
        }

//        throw new \Exception(var_export($respData, 1), 111);

        $response->setFrom(Tools::pb_array_filter($respData));
        return $response;
    }

    /**
     * @return GetCumulativeReturnDetailRequest
     */
    public static function request()
    {
        return new GetCumulativeReturnDetailRequest();
    }

    /**
     * @return GetCumulativeReturnDetailResponse
     */
    public static function response()
    {
        return new GetCumulativeReturnDetailResponse();
    }

}