<?php
/**
 * Created by PhpStorm.
 * User: zgr0629
 * Date: 8/12/2016
 * Time: 5:48 PM
 */

namespace service\resources\sales\v1;

use common\models\salesrule\UserCoupon;
use framework\components\Date;
use service\components\Tools;
use service\message\sales\getCustomerCouponAvailableCountRequest;
use service\message\sales\getCustomerCouponAvailableCountResponse;
use service\resources\ResourceAbstract;

class getCustomerCouponAvailableCount extends ResourceAbstract
{
    public function run($data)
    {

        /** @var getCustomerCouponAvailableCountRequest $request */
        $request = self::request();
        $request->parseFromString($data);
        $this->_initCustomer($request);

        // 未过期优惠券数量
        $date = new Date();
        $coupon_available_count = UserCoupon::find()
            ->where(['customer_id' => $request->getCustomerId()])
            ->andWhere(['state' => UserCoupon::USER_COUPON_UNUSED])
            ->andWhere(['>', 'expiration_date', $date->date('Y-m-d H:i:s')])
            ->count();

        //即将到期的优惠券数量  72小时
        $expireSeconds = strtotime($date->date('Y-m-d H:i:s')) + (3600 * 72);
        $coupon_expiring_count = UserCoupon::find()
            ->where(['customer_id' => $request->getCustomerId()])
            ->andWhere(['state' => UserCoupon::USER_COUPON_UNUSED])
            ->andWhere(['>', 'expiration_date', $date->date('Y-m-d H:i:s')])
            ->andWhere(['<', 'expiration_date', date('Y-m-d H:i:s', $expireSeconds)]);
//        Tools::log($coupon_expiring_count->createCommand()->getRawSql(), 'jun.log');
        $coupon_expiring_count = $coupon_expiring_count->count();

        $response = self::response();
        $response->setFrom([
            'coupon_available_count' => $coupon_available_count,
            'coupon_expiring_count' => $coupon_expiring_count,
            'coupon_expiring_tips' => $coupon_expiring_count.'张即将过期',
        ]);
        return $response;
    }

//    private function getOrders($condition){
//        $orders = new SalesFlatOrder();
//        $orders->find()->where($condition);
//    }

    public static function request()
    {
        return new getCustomerCouponAvailableCountRequest();
    }

    public static function response()
    {
        return new getCustomerCouponAvailableCountResponse();
    }

}