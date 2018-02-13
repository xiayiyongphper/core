<?php
/**
 * Created by PhpStorm.
 * User: ZQY
 * Date: 2017/4/25
 * Time: 11:40
 */
namespace service\resources\sales\v1;

use common\models\salesrule\Rule;
use common\models\salesrule\UserCoupon;
use framework\components\ToolsAbstract;
use service\components\Tools;
use service\message\common\SourceEnum;
use service\message\sales\getCustomerCouponNotifyRequest;
use service\message\sales\getCustomerCouponNotifyResponse;
use service\resources\ResourceAbstract;

/**
 * Class getCustomerCouponNotify
 * @package service\resources\sales\v1
 */
class getCustomerCouponNotify extends ResourceAbstract
{
    /**
     * 获取数量
     */
    const FETCT_NUM = 3;
    /**
     * 新優惠券
     */
    const TYPE_NEW = 1;
    /**
     * 無新優惠券
     */
    const TYPE_OLD = 2;

    /**
     * @param string $data
     * @return getCustomerCouponNotifyResponse
     */
    public function run($data)
    {

        /** @var getCustomerCouponNotifyRequest $request */
        $request = self::request();
        $request->parseFromString($data);

        if ($this->getSource() == SourceEnum::ANDROID_CONTRACTOR || $this->getSource() == SourceEnum::IOS_CONTRACTOR) {
            /* 来自业务员的请求，不验证用户 */
        } else {
            $customer = $this->_initCustomer($request);
        }

        $response = self::response();
        $allInfo = $this->getCouponInfo($request, null, false);
        if (!$allInfo) {   // 如果查询全部优惠券都沒有結果，則直接返回
            return $response;
        }

        /* 先獲取新優惠券，沒有再獲取舊優惠券 */
        $type = self::TYPE_NEW;
        if (!$couponInfo = $this->getCouponInfo($request, $type, true)) {
            $type = self::TYPE_OLD;
            $couponInfo = $this->getCouponInfo($request, $type, true);
        }

        $count = (int)$allInfo[0]->count;   // 總數取的是全部的
        $sum = (float)$couponInfo[0]->sum; // 總金額
        $couponList = [];

        /** @var UserCoupon $coupon */
        foreach ($couponInfo[1] as $coupon) {
            if (!$coupon || !$coupon->rule || !$coupon->rule->rule_id) { //优惠券查询不到对应的活动，跳过
                continue;
            }

            // 优惠券跳转
            $url = '';
            // 单品级跳商品详情
            if ($coupon->rule->type == 1) {
                $wholesaler_ids = explode('|', $coupon->rule->store_id);
                $wholesaler_ids = array_filter($wholesaler_ids);
                $wholesaler_id = array_shift($wholesaler_ids);
                $product_id = $coupon->rule->product_id;
                if ($wholesaler_id && $product_id) {
                    $url = "lelaishop://prod/info?wid={$wholesaler_id}&pid={$product_id}";
                }
            } elseif ($coupon->rule->type == 2) {   // 多品级跳优惠专题页
                $url = "lelaishop://topicV4/list?rid={$coupon->rule->rule_id}";
            } elseif ($coupon->rule->type == 3) {   // 订单级如果是单个商家则跳商家页
                $wholesaler_ids = array_filter(explode('|', $coupon->rule->store_id));
                if (count($wholesaler_ids) == 1) {
                    $wholesaler_id = array_shift($wholesaler_ids);
                    $url = "lelaishop://shop/info?wid={$wholesaler_id}";
                }
            }

            // 使用条件语句
            $use_condition = '';
            $rule_conditions = unserialize($coupon->rule->conditions_serialized);
            if (isset($rule_conditions['conditions']['0'])) {
                $condition = $rule_conditions['conditions']['0'];
                $action_levels = Rule::getCondition($condition['value'], $coupon->rule->discount_amount);
                $conditionInfo = Rule::getCouponConditionInfo($action_levels, $condition['attribute']);
                $use_condition = $conditionInfo['use_condition'];
            }

            $expiredDate = date('n月j日', strtotime($coupon->rule->from_date))
                . '至' . date('n月j日', strtotime($coupon->expiration_date)) . '有效';

            $couponList[] = [
                'entity_id' => $coupon->entity_id,
                'customer_id' => $coupon->customer_id,
                'state' => $coupon->state,
                'rule_id' => $coupon->rule_id,
                'expiration_date' => $expiredDate,
                'source' => $coupon->source,
                'created_at' => $coupon->created_at,
                'coupon_title' => $coupon->rule->coupon_title,
                'frontnote' => $coupon->rule->frontnote,
                'discount_type' => $coupon->rule->getDiscountType(),
                'discount' => $coupon->rule->getDiscountAmount(),
                'use_condition' => $use_condition,
                'is_soon_expire' => $coupon->isSoonExpire(),
                'url' => $url,
            ];
        }

        if ($type == self::TYPE_NEW) {
            $title = '<font color=#232326>恭喜您获得</font><font color=#D4372F>' . $sum
                . '元</font><font color=#232326>优惠券</font>';
        } else {
            $title = '<font color=#232326>您有</font><font color=#D4372F>' . $sum
                . '元</font><font color=#232326>优惠券未使用</font>';
        }

        //零钱
        $balance = $customer->getBalance();

        $responseData = Tools::pb_array_filter([
            'type' => $type,
            'title' => $title,
            'balance_tips' => '<font color=#232326>下单还可使用</font><font color=#D4372F>'.sprintf("%.2f",$balance).'元</font><font color=#232326>零钱</font>',
            'tips' => '<font color=#81838E>账户中共有</font><font color=#D4372F>' . $count
                . '</font><font color=#81838E>张优惠券，</font><font color=#0058A6>点击查看&gt;</font>',
            'coupon_list' => $couponList,
        ]);

        $response->setFrom($responseData);

        /* 新优惠券的话该用户的所有优惠券设置为已读 */
        if ($type == self::TYPE_NEW) {
            UserCoupon::updateAll(['is_read' => UserCoupon::READ], ['customer_id' => $request->getCustomerId()]);
        }

        return $response;
    }

    /**
     * 获取coupon信息
     *
     * @param getCustomerCouponNotifyRequest $request
     * @param bool $type 类型，null为所有
     * @param bool $isGetList 是否获取优惠券列表
     * @return array|null
     */
    private function getCouponInfo(getCustomerCouponNotifyRequest $request, $type = null, $isGetList = true)
    {
        $date = ToolsAbstract::getDate();
        $currentDate = $date->date('Y-m-d H:i:s');

        $couponQuery = UserCoupon::find()
            ->joinWith('rule')
            ->where(['salesrule_user_coupon.state' => UserCoupon::USER_COUPON_UNUSED])
            ->andWhere(['>=', 'salesrule_user_coupon.expiration_date', $currentDate])
            ->andWhere(['salesrule_user_coupon.customer_id' => $request->getCustomerId()]);

        if (!is_null($type)) {   // 如果类型不能为null
            $couponQuery->andWhere([
                'salesrule_user_coupon.is_read' => $type == self::TYPE_NEW ? UserCoupon::UNREAD : UserCoupon::READ
            ]);
        }

        // 统计
        /* @var UserCoupon $cntQueryModel */
        $cntQueryModel = $couponQuery->select('COUNT(*) as `count`,SUM(max_discount_value) as `sum`')->one();
        if (!$cntQueryModel || $cntQueryModel->count <= 0) {
            return null;
        }

        $couponModels = null;
        if ($isGetList) {
            if ($type == self::TYPE_NEW) {
                $couponQuery->orderBy('max_discount_value DESC');
            } else if ($type == self::TYPE_OLD) {
                $couponQuery->orderBy('salesrule_user_coupon.created_at DESC');
            }
            $couponModels = $couponQuery->select('*')->limit(self::FETCT_NUM)->all();   // 只获取前面N张优惠券
        }

        return [$cntQueryModel, $couponModels];
    }

    /**
     * @return getCustomerCouponNotifyRequest
     */
    public static function request()
    {
        return new getCustomerCouponNotifyRequest();
    }

    /**
     * @return getCustomerCouponNotifyResponse
     */
    public static function response()
    {
        return new getCustomerCouponNotifyResponse();
    }

}