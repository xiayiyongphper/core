<?php

namespace service\resources\sales\v1;

use common\models\CumulativeReturnActivity;
use common\models\LeContractor;
use common\models\LeCustomer;
use common\models\salesrule\Rule;
use common\models\salesrule\UserCoupon;
use framework\components\Date;
use framework\models\dau\Contractor;
use service\components\Tools;
use service\message\core\ReceiveCouponRequest;
use service\resources\Exception;
use service\resources\ResourceAbstract;

/**
 * Class receiveCoupon
 * @package service\resources\sales\v1
 */
class receiveContractorCoupon extends ResourceAbstract
{

    public function run($data)
    {

        // 考虑一个券可以领取多次的情况,要先获取这个优惠券的信息
        // 考虑所有用户领取总数达到上线的情况
        // 考虑单个用户领取总数达到上线的情况

        // 一个规则可以领取多次，用过后才可以领
        // 领过后不能在领
        // 还没到期的规则，领了不能用，过几天在用   有效期还用原来的

        // 截止时间-有效期 > 0

        //领取后加入缓存


        /** @var ReceiveCouponRequest $request */
        $request = self::request();
        $request->parseFromString($data);

        //用户信息
        $customer = $this->_initCustomer($request, true);
        $customerId = $customer->getCustomerId();

        // 业务员删选 普通业务员只能查看自己的发放历史 其他的可以筛选业务员
        // 查询业务员的token
        $contractorInfo = LeContractor::find()->where(['entity_id' => $request->getContractorId()])->one();
        $contractor = $this->_initContractor($request->getContractorId(), $contractorInfo->auth_token);
        if ($contractor->getRole() == self::COMMON_CONTRACTOR) {
            $contractor_id = $contractor->getContractorId();
        } else {
            $contractor_id = $customer->getContractorId();
        }

        //数据
        $date = new Date();
        $date = $date->date();
        $rule_id = $request->getRuleId();
        $coupon_code = $request->getCoupon();

        $rule = '';
        if ($rule_id > 0) {
            //要领取的优惠券ID，手动领取
//            $couponTypes = [Rule::RULE_COUPON_SEND, Rule::COUPON_TYPE_CUMULATIVE_RETURN];
            /** @var Rule $rule */
            $rule = Rule::getCouponRuleByRuleId($rule_id, []);
        } else if (!empty($coupon_code)) {
            //要领取的优惠券码，优惠码兑换
            /** @var Rule $rule */
            $rule = Rule::getCouponRuleByCouponCode($coupon_code);
            if (!$rule) {
                Exception::couponNumberError();
            }
            $rule_id = $rule->rule_id;
        } else {
            Exception::invalidRequestRoute();
        }

        if (!$rule || $rule->to_date < $date) {
            //可能是优惠券配置错误导致，提示优惠券活动已结束
            Exception::couponExpire();
        }

        /* 累计满返活动要判断是否可以领取（比如活动金额是否达到） */
        if ($rule->coupon_type == Rule::COUPON_TYPE_CUMULATIVE_RETURN) {
            $isCanGet = false;

            /* 判断本期 */
            $actDetail = CumulativeReturnActivity::getActivityDetail2(
                $customer,
                CumulativeReturnActivity::TYPE_DETAIL
            );
            if (!empty($actDetail['coupon_list'])) {
                foreach ($actDetail['coupon_list'] as $couponDetail) {
                    if ($couponDetail['rule_id'] == $rule->rule_id
                        && $couponDetail['state'] == CumulativeReturnActivity::STATE_CAN_GET
                    ) {
                        $isCanGet = true;
                    }
                }
            }

            /* 判断上期 */
            if (!$isCanGet) {
                $actDetail = CumulativeReturnActivity::getActivityDetail2(
                    $customer,
                    CumulativeReturnActivity::TYPE_LAST_DETAIL
                );
                if (!empty($actDetail['coupon_list'])) {
                    foreach ($actDetail['coupon_list'] as $couponDetail) {
                        if ($couponDetail['rule_id'] == $rule->rule_id
                            && $couponDetail['state'] == CumulativeReturnActivity::STATE_CAN_GET
                        ) {
                            $isCanGet = true;
                        }
                    }
                }
            }

            if (!$isCanGet) {
                Exception::couponExpire();
            }
        }

        //领取缓存
        $redis = Tools::getRedis();
        $couponKey = UserCoupon::COUPON_KEY_PREFIX . $rule_id;
        $totalCount = $redis->hLen($couponKey);

        //先判断所有用户领取是否达到上线
        if ($totalCount >= $rule->uses_per_coupon) {
            Exception::couponReceiveOut();
        }
        //判断单个用户领取是否达到上线
        $userTotalCount = $redis->hGet($couponKey, $customerId);
        if ($userTotalCount > $rule->uses_per_customer) {
            Exception::couponUserReceiveOut();
        }

        //领取优惠券
        $result = Rule::getCoupon($rule, $customerId, UserCoupon::COUPON_SOURCE_RECEIVE, $contractor_id);
        if (!$result) {
            Exception::couponReceivedError();
        }
        //领取成功记录到redis
        $redis->hIncrBy($couponKey, $customerId, 1);
        return true;
    }

    public static function request()
    {
        return new ReceiveCouponRequest();
    }

    public static function response()
    {
        return true;
    }
}
