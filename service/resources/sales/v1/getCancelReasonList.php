<?php
namespace service\resources\sales\v1;

use service\components\Tools;
use service\message\common\KeyValueItem;
use service\message\sales\CancelReasonListResponse;
use service\message\sales\CancelReasonListRequest;
use service\resources\Exception;
use service\resources\ResourceAbstract;

class getCancelReasonList extends ResourceAbstract
{
    public function run($data)
    {
        /** @var CancelReasonListRequest $request */
        $request = self::request();
        $request->parseFromString($data);
        $response = self::response();
        $customerResponse = $this->_initCustomer($request);

        $response = self::response();
        $responseData = [
            'reasons' => [
                ['key' => 1,'value' => '我买错了，买多了'],
                ['key' => 2,'value' => '我的收货信息填错了'],
                ['key' => 3,'value' => '我不想要了'],
                ['key' => 4,'value' => '我没有使用优惠券'],
                ['key' => 5,'value' => '商家接单时间太长'],
                ['key' => 6,'value' => '商家通知我卖完了'],
                ['key' => 7,'value' => '商家通知我配送不了'],
                ['key' => 8,'value' => '商家联系不上'],
            ],
        ];
        $response->setFrom($responseData);
        return $response;
    }

    public static function request()
    {
        return new CancelReasonListRequest();
    }

    public static function response()
    {
        return new CancelReasonListResponse();
    }
}