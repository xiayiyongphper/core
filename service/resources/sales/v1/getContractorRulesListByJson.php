<?php

namespace service\resources\sales\v1;

use common\models\salesrule\Rule;
use common\models\salesrule\UserCoupon;
use framework\components\ToolsAbstract;
use service\components\Tools;
use service\resources\ResourceAbstract;

class getContractorRulesListByJson extends ResourceAbstract
{
    public function run($data)
    {
        $date = ToolsAbstract::getDate();
        $now = $date->date('Y-m-d H:i:s');

        $request = json_decode($data, true);
        $couponArr = [];

        if (empty($request['ruleIds'])) {
            return $couponArr;
        }

        Tools::log($request, 'getContractorRulesListByJson.log');

        $wholesalerIds = $request['wholesalerIds'];
        $customerId = $request['customerId'];


        // 根据传过来的rule_id 查询出rule对象
        $coupons = Rule::find()
            ->where(['is_active' => 1])
            ->andWhere(['<=', 'from_date', $now])
            ->andWhere(['>=', 'to_date', $now])
            ->andWhere(['is_del' => 0])
            ->andWhere(['rule_id' => $request['ruleIds']])
            ->all();
        Tools::log($coupons, 'getContractorRulesListByJson.log');
        /** @var Rule $coupon */
        foreach ($coupons as $coupon) {
            //可以用的供应商  || 和 |1|2| 两种类型的供应商id
            $store_ids = array_filter(explode('|', $coupon->store_id));
            $wholesaler_ids_available = array_intersect($store_ids, $wholesalerIds);
            Tools::log($store_ids, 'getContractorRulesListByJson.log');
            Tools::log($wholesaler_ids_available, 'getContractorRulesListByJson.log');
            if (!empty($store_ids) && empty($wholesaler_ids_available)) {
                continue;
            }

            // 查询该优惠券全局领用数量 不能大于限制数量
            $userCouponHas = UserCoupon::find()->where(['rule_id' => $coupon->rule_id])->count();
            if ($userCouponHas >= $coupon->uses_per_coupon) {
                continue;
            }

            // 再查询下该用户是否达到领取上限 若是 则不返回该优惠券
            $userCouponHas = UserCoupon::find()->where(['customer_id' => $customerId, 'rule_id' => $coupon->rule_id])->count();
            if ($userCouponHas >= $coupon->uses_per_customer) {
                continue;
            }

            //判断是否有未使用的券  若是 则不返回该优惠券
            $userCouponHas = UserCoupon::find()->where(['customer_id' => $customerId, 'rule_id' => $coupon->rule_id, 'state' => 1])->count();
            if ($userCouponHas > 0) {
                continue;
            }
            $couponInfo = Rule::getCouponDetail($coupon);
            $couponInfo['frontnote'] = $coupon->frontnote;// 描述
            $couponArr[] = $couponInfo;
        }

        return $couponArr;
    }

    public static function request()
    {
        return true;
    }

    public static function response()
    {
        return true;
    }
}