<?php
/**
 * Created by PhpStorm.
 * User: ZQY
 * Date: 2017/4/21
 * Time: 17:57
 */
namespace service\resources\sales\v1;

use common\models\salesrule\Rule;
use common\models\salesrule\UserCoupon;
use framework\components\Date;
use service\components\Tools;
use service\message\core\CouponStatusRequest;
use service\message\core\CouponStatusResponse;
use service\resources\Exception;
use service\resources\ResourceAbstract;

/**
 * Class receiveCoupon
 * @package service\resources\sales\v1
 */
class couponStatus extends ResourceAbstract
{
    /**
     * @param string $data
     * @return CouponStatusResponse
     * @throws \Exception
     */
    public function run($data)
    {
        /** @var CouponStatusRequest $request */
        $request = self::request();
        $request->parseFromString($data);

        // 用户信息
        $customerResponse = $this->_initCustomer($request, true);
        $customerId = $customerResponse->getCustomerId();

        $ruleIds = $request->getRuleId();
        if (empty($ruleIds)) {
            throw new \Exception('参数错误', 103);
        }

        $couponData = [];
        foreach ($ruleIds as $ruleId) {
            try {
                $this->checkCanGet($ruleId, $customerId);
                $couponData[] = [
                    'rule_id' => $ruleId,
                    'state' => 0
                ];
            } catch (\Exception $e) {
                $couponData[] = [
                    'rule_id' => $ruleId,
                    'state' => 1,   // 暂定都返回1
                    'unavailable_reason' => $e->getMessage()
                ];
            }
        }

        $response = self::response();
        $response->setFrom(Tools::pb_array_filter([
            'coupon' => $couponData,
        ]));

        return $response;
    }

    /**
     * 检查可领取
     *
     * @param $ruleId
     * @param $customerId
     * @return bool 只返回true，其他都是异常
     * @throws \Exception
     */
    private function checkCanGet($ruleId, $customerId)
    {
        $date = new Date();
        $date = $date->date();

        /** @var Rule $rule */
        $ruleModel = Rule::getCouponRuleByRuleId($ruleId, Rule::RULE_COUPON_SHOW);
        if (!$ruleModel) {
            throw new \Exception('已结束', 101);
        }

        $userRule = UserCoupon::find()->where(['rule_id' => $ruleId])
            ->andWhere(['customer_id' => $customerId])
            ->andWhere(['state' => UserCoupon::USER_COUPON_UNUSED])
            ->andWhere(['>=', 'salesrule_user_coupon.expiration_date', $date])
            ->count();

        // 是否存在未使用的该优惠券
        if ($userRule > 0) {
            throw new \Exception('已领取', Exception::COUPON_USER_RECEIVED);
        }

        //先判断所有用户领取是否达到上线
        $totalCount = UserCoupon::find()->where(['rule_id' => $ruleId])->count();
        if ($totalCount >= $ruleModel->uses_per_coupon) {    // 可被领取次数超过限制
            throw new \Exception('已抢光', Exception::COUPON_RECEIVE_COUNT_OUT);
        }

        //在判断单个用户领取是否达到上线
        $userTotalCount = UserCoupon::find()->where(['rule_id' => $ruleId])
            ->andWhere(['customer_id' => $customerId])->count();
        if ($userTotalCount >= $ruleModel->uses_per_customer) {  // 用户领取是否达到上线
            throw new \Exception('已领完', Exception::COUPON_USER_RECEIVE_COUNT_OUT);
        }

        return true;
    }

    /**
     * @return CouponStatusRequest
     */
    public static function request()
    {
        return new CouponStatusRequest();
    }

    /**
     * @return CouponStatusResponse
     */
    public static function response()
    {
        return new CouponStatusResponse();
    }
}
