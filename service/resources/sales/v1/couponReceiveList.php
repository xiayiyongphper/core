<?php

namespace service\resources\sales\v1;

use common\models\salesrule\Rule;
use service\components\Tools;
use service\message\core\CouponReceiveListRequest;
use service\message\core\CouponReceiveListResponse;
use service\resources\ResourceAbstract;

class couponReceiveList extends ResourceAbstract
{
    public function run($data)
    {
        /** @var CouponReceiveListRequest $request */
        $request = self::request();
        $request->parseFromString($data);
        $response = self::response();
        //1、商品详情  2、 供应商首页  3、专题页面 4、购物车页面
        $location = $request->getLocation();
        $wholesaler_id = $request->getWholesalerId();
        $rule_id = $request->getRuleId();
        $coupons = [];

//        Tools::log($wholesaler_id,'wangyang.log');
//        Tools::log($rule_id,'wangyang.log');
//        Tools::log($location,'wangyang.log');

        //1、商品详情  2、 供应商首页  3、专题页面 4、购物车页面
        switch ($location) {
            case 1:
                $coupons = Rule::generateCoupons($rule_id, null, ['fromDetail' => true]);
                break;
            case 2:
                $coupons = Rule::generateCoupons(null, $wholesaler_id, ['sort' => ['max_discount_value' => SORT_DESC]]);
                break;
            case 3:
                $coupons = Rule::generateCoupons($rule_id, null, ['fromTopic' => true]);
                break;
            case 4:
                $coupons = Rule::generateCoupons(null, $wholesaler_id);
                break;
            default:
                break;
        }

        $response->setFrom(Tools::pb_array_filter(['coupon_receive' => $coupons]));
        return $response;
    }

    public static function request()
    {
        return new CouponReceiveListRequest();
    }

    public static function response()
    {
        return new CouponReceiveListResponse();
    }
}
