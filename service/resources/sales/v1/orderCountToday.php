<?php
namespace service\resources\sales\v1;


use service\components\Tools;
use service\message\sales\OrderCollectionRequest;
use service\message\sales\OrderNumberResponse;
use service\resources\ResourceAbstract;

/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2016/1/21
 * Time: 15:09
 */
class orderCountToday extends ResourceAbstract
{

    public function run($data)
    {
        /** @var OrderCollectionRequest $request */
        $request = self::request();
        $request->parseFromString($data);
        //接口验证用户
        $response = new OrderNumberResponse();
        $customerId = $request->getCustomerId();
        $count = Tools::orderCountToday($customerId,$request->getWholesalerId());
        $response->setAll($count);
        return $response;
    }

    public static function request()
    {
        return new OrderCollectionRequest();
    }

    public static function response()
    {
        return new OrderNumberResponse();
    }
}
