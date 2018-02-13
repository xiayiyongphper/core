<?php

namespace common\models;

use common\models\salesrule\Rule;
use common\models\salesrule\UserCoupon;
use framework\components\ToolsAbstract;
use framework\db\ActiveRecord;
use service\message\customer\CustomerResponse;
use Yii;

/**
 * 累计满返活动
 *
 * @property integer $entity_id
 * @property integer $name 活动名称
 * @property integer $city 城市code
 * @property string $from_date 开始时间
 * @property string $to_date 结束时间
 * @property string $status 状态,2:删除，1:启用，0：禁用
 * @property string $detail 规则详情
 * @property string $description 描述（后台看的备注）
 * @property string $levels 优惠级别配置
 * @property string $created_at 创建时间
 * @property string $updated_at 更新时间
 */
class CumulativeReturnActivity extends ActiveRecord
{
    /**
     * 状态：已删除
     */
    const STATUS_DELETED = 2;
    /**
     * 状态：启用
     */
    const STATUS_ENABELD = 1;
    /**
     * 状态：禁用
     */
    const STATUS_DISABLED = 0;

    /**
     * 类型:我的
     */
    const TYPE_MINE = 1;
    /**
     * 类型:订单列表
     */
    const TYPE_ORDER_LIST = 2;
    /**
     * 类型:累计满返活动详情
     */
    const TYPE_DETAIL = 3;
    /**
     * 类型:首页
     */
    const TYPE_INDEX = 4;
    /**
     * 类型:首页
     */
    const TYPE_LAST_DETAIL = 5;

    /**
     * 状态：可以领取
     */
    const STATE_CAN_GET = 1;
    /**
     * 状态：不可领取
     */
    const STATE_CANNOT_GET = 2;
    /**
     * 状态：已领取
     */
    const STATE_AREADY_GOT = 3;

    /**
     * 状态：未结束
     */
    const STATUS_END = 1;
    /**
     * 状态：已结束
     */
    const STATUS_NOT_END = 0;

    /**
     * 活动结束后过期的天数
     * @integer
     * @since 2.6.6
     */
    const EXPIRED_DAYS_AFTER_ACTIVITY = 10;
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'cumulative_return_activity';
    }

    /**
     * @return \yii\db\Connection the database connection used by this AR class.
     */
    public static function getDb()
    {
        return Yii::$app->get('mainDb');
    }

    /**
     * 获取当前活动的用户下单总额
     *
     * @param CustomerResponse $customer
     * @param CumulativeReturnActivity $activity
     * @return double
     */
    public static function getCustomerOrderTotal(CustomerResponse $customer, CumulativeReturnActivity $activity)
    {
        /* 修复订单时间差8小时问题 */
        $toDateTime = date('Y-m-d H:i:s', strtotime('-8 hours', strtotime($activity->to_date)));
        $fromDateTime = date('Y-m-d H:i:s', strtotime('-8 hours', strtotime($activity->from_date)));
        $tenDaysDateTime = date('Y-m-d H:i:s', strtotime('+10 days', strtotime($toDateTime)));

        /* 统计的是下单和完成时间都在活动期间内的订单 */
        $orderTotal = SalesFlatOrder::find()->where([
            'customer_id' => $customer->getCustomerId(),
            'city' => $customer->getCity(),
            'state' => SalesFlatOrder::STATE_COMPLETE,
        ])->andWhere(['<=', 'created_at', $toDateTime])
            ->andWhere(['>=', 'created_at', $fromDateTime])
            ->andWhere(['<=', 'complete_at', $tenDaysDateTime])
            ->andWhere(['>=', 'complete_at', $fromDateTime])
            ->sum('receipt_total');

        return $orderTotal ? number_format($orderTotal, 2, '.', '') : 0;
    }

    /**
     * 獲取活動詳情
     *
     * @param CustomerResponse $customer
     * @param integer $type
     * @throws \Exception
     * @return array
     */
    public static function getActivityDetail(CustomerResponse $customer, $type)
    {
        /* 查询符合的活动 */
        $curTimestamp = ToolsAbstract::getDate()->timestamp();
        $curDateTime = date('Y-m-d H:i:s', $curTimestamp);
        $tenDaysBeforeDateTime = date('Y-m-d H:i:s', strtotime('-10 days', $curTimestamp));
        /** @var CumulativeReturnActivity $queryRes */
        $queryRes = CumulativeReturnActivity::find()->where([
            'city' => $customer->getCity(),
            'status' => CumulativeReturnActivity::STATUS_ENABELD
        ])->andWhere(['<=', 'from_date', $curDateTime])
            ->andWhere(['>=', 'to_date', $tenDaysBeforeDateTime])
            ->orderBy('from_date DESC') // 活动有重叠，取最新一个
            ->one();

        $ret = [];
        if (!$queryRes) {
            return null;
        } elseif ($type == self::TYPE_INDEX) { // 首页红点
            return ['levels' => [1]];
        }

        /* 当前订单总金额 */
        $curTotal = CumulativeReturnActivity::getCustomerOrderTotal($customer, $queryRes);
        $ret['current'] = $curTotal;

        /* 优惠级别 */
        $levels = json_decode($queryRes->levels, true);
        if (!$levels || !is_array($levels)) {
            return null;
        }
        $levelCount = count($levels);
        $levelCols = array_column($levels, 'level');
        array_multisort($levelCols, SORT_ASC, $levels); // 排序

        /* 优惠券Id列表 */
        $couponIds = [];    // 优惠券id数组，格式[id1, id2, id3, id4]
        $couponIdsStr = array_column($levels, 'coupons');
        $coupon2DIds = []; // 优惠券id二维数组，格式[[id1, id2], id3, id4]
        foreach ($couponIdsStr as $couponIdKey => $couponIdStr) {
            if ($tmpIds = explode(',', $couponIdStr)) {
                $tmpIds = array_filter($tmpIds);
                $coupon2DIds[$couponIdKey] = $tmpIds;
                $couponIds = array_merge($couponIds, $tmpIds);
            }
        }
        unset($couponIdStr, $couponIdKey, $ids);

        /* 获取优惠券列表 */
        $coupons = Rule::getCouponRulesByRuleIdsCouponType($couponIds, Rule::COUPON_TYPE_CUMULATIVE_RETURN);
        if (empty($coupons)) {  // 没有优惠券直接返回
            return null;
        }

        /* 当前金额的提示语 */
        $discount = 0.0;    // 当前级别的优惠券优惠金额
        for ($idx = $levelCount - 1; $idx >= 0; $idx--) {
            $level = $levels[$idx]['level'];
            if ($curTotal < $level) {   // 小于当前级别寻找下个级别
                continue;
            }

            $discount = self::getCouponDiscount($coupons, $coupon2DIds[$idx]);
            if ($idx == $levelCount - 1) { // 最后一个(最高级别)
                $ret['tips'] = "<font color='#81838E'>您已购满{$curTotal}元，返{$discount}元优惠券%s。</font>";
                // 详情不设置跳转
                if ($type != self::TYPE_DETAIL) {
                    $ret['url'] = 'lelaishop://cumulativeReturn/detail';
                    $ret['tips'] = sprintf($ret['tips'], '，查看详情');
                } else {
                    $ret['tips'] = sprintf($ret['tips'], '');
                }
            } else {
                $nextDiscount = self::getCouponDiscount($coupons, $coupon2DIds[$idx + 1]);
                $ret['tips'] = "<font color='#81838E'>您已购满{$curTotal}元，可返{$discount}元优惠券，再购"
                    . number_format(($levels[$idx + 1]['level'] - $curTotal), 2, '.', '')
                    . "元，即可领取{$nextDiscount}元优惠券。再去下单</font>";
                $ret['url'] = 'lelaishop://page/home';
            }
            break;
        }

        if ($idx == -1) { // 一个都没符合
            $nextDiscount = self::getCouponDiscount($coupons, $coupon2DIds[0]);
            $ret['tips'] = "<font color='#81838E'>您已购{$curTotal}元，再购买"
                . number_format(($levels[0]['level'] - $curTotal), 2, '.', '')
                . "元，即可领取{$nextDiscount}元优惠券。再去下单</font>";
            $ret['url'] = 'lelaishop://page/home';
        }

        /* levels设置，前面增加级别0 */
        $ret['levels'] = array_column($levels, 'level');
        array_unshift($ret['levels'], 0);

        /* 类型为优惠券详情或者活动结束10天内返回更多信息 */
        $canGetCouponNum = 0; // 可以领取的优惠券数量
        if ($type == self::TYPE_DETAIL || $queryRes->to_date < $curDateTime) {
            // banner
            if (false !== preg_match('/height=(\d+)/', $queryRes['banner'], $matches)) {
                $height = isset($matches[1]) ? $matches[1] : 210;
            }
            $height = $height ?: 210;
            $ret['banner'] = [
                'src' => $queryRes['banner'],
                'height' => $height
            ];
            // 详情
            $ret['detail'] = nl2br($queryRes['detail']);
            // 优惠券列表状态、可领取的优惠券金额
            $couponMaxDiscount = 0.0;   // 可领取的优惠券金额
            foreach ($coupons as $coupon) {
                if ($couponDetail = Rule::getCouponDetail($coupon, ['fromLeiJiManFanDetail' => 1])) {
                    if ($idx == -1) {
                        $couponDetail['state'] = self::STATE_CANNOT_GET;
                    } else {
                        /* 遍历所有小于等于当前级别的优惠券*/
                        $curIdx = $idx;
                        while($curIdx >= 0) {
                            if (in_array($coupon->rule_id, $coupon2DIds[$curIdx])) {  // 符合当前级别
                                if (UserCoupon::findOne([
                                    'customer_id' => $customer->getCustomerId(),
                                    'rule_id' => $coupon->rule_id
                                ])) {
                                    $couponDetail['state'] = self::STATE_AREADY_GOT;
                                } else {
                                    $canGetCouponNum++;
                                    $couponDetail['state'] = self::STATE_CAN_GET;
                                    $couponMaxDiscount += $coupon->max_discount_value;
                                }
                                break;
                            } else {
                                $couponDetail['state'] = self::STATE_CANNOT_GET;
                                --$curIdx;
                            }
                        }
                    }
                    $ret['coupon_list'][] = $couponDetail;
                }
            }
            // 可领取优惠券的优惠金额
            $ret['coupon_max_discount'] = number_format($couponMaxDiscount, 2, '.', '');
        }

        /* 如果活动结束，则把提示语做想要修改 */
        if ($queryRes->to_date < $curDateTime) {
            if ($canGetCouponNum > 0) {
                $ret['tips'] = sprintf('活动已结束，你有%d张未领券', $canGetCouponNum);
            } else if ($type == self::TYPE_ORDER_LIST) {
                return null;
            } else {
                $ret['tips'] = '活动已结束';
            }
            $ret['url'] = '';
        }

        return $ret;
    }

    /**
     * 獲取活動詳情
     *
     * @since 2.6.6
     * @param CustomerResponse $customer
     * @param integer $type
     * @throws \Exception
     * @return array
     */
    public static function getActivityDetail2(CustomerResponse $customer, $type)
    {
        $ret = [];
        $isLastActivity = false;
        $leftDays = 0;
        /* 如果现在没有进行着的活动或者类型为5，则获取上一个活动，都没有活动返回null */
        if ($type == self::TYPE_LAST_DETAIL || !$activity = self::getCurrentActivity($customer)) {
            /* 类型为5，获取上一个活动，但是要排除现在进行的活动 */
            if ($type == self::TYPE_LAST_DETAIL) {
                $activity = self::getCurrentActivity($customer);
            }
            if (!$activity = self::getLastActivity($customer, $activity ? $activity->entity_id : [])) {
                return null;
            }

            $isLastActivity = true;
            $leftDays = self::EXPIRED_DAYS_AFTER_ACTIVITY
                - ceil((ToolsAbstract::getDate()->timestamp() - strtotime($activity->to_date)) / 86400);
        }

        if ($type == self::TYPE_INDEX) {
            return ['levels' => [1]];   // 首页红点只需要返回levels（只要不为空即可）
        }

        /* 当前订单总金额 */
        $curTotal = CumulativeReturnActivity::getCustomerOrderTotal($customer, $activity);
        $ret['current'] = $curTotal;

        /* 优惠级别 */
        $levels = json_decode($activity->levels, true);
        if (!$levels || !is_array($levels)) {
            return null;
        }
        $levelCount = count($levels);
        $levelCols = array_column($levels, 'level');
        array_multisort($levelCols, SORT_ASC, $levels); // 排序

        /* 优惠券Id列表 */
        $couponIds = [];    // 优惠券id数组，格式[id1, id2, id3, id4]
        $couponIdsStr = array_column($levels, 'coupons');
        $coupon2DIds = []; // 优惠券id二维数组，格式[[id1, id2], id3, id4]
        foreach ($couponIdsStr as $couponIdKey => $couponIdStr) {
            if ($tmpIds = explode(',', $couponIdStr)) {
                $tmpIds = array_filter($tmpIds);
                $coupon2DIds[$couponIdKey] = $tmpIds;
                $couponIds = array_merge($couponIds, $tmpIds);
            }
        }
        unset($couponIdStr, $couponIdKey, $ids);

        /* 获取优惠券列表 */
        $coupons = Rule::getCouponRulesByRuleIdsCouponType($couponIds, Rule::COUPON_TYPE_CUMULATIVE_RETURN);
        if (empty($coupons)) {  // 没有优惠券直接返回
            return null;
        }

        /* 当前金额的提示语 */
        for ($idx = $levelCount - 1; $idx >= 0; $idx--) {
            $level = $levels[$idx]['level'];
            if ($curTotal < $level) {   // 小于当前级别寻找下个级别
                continue;
            }

            /* 如果是上期活动在订单列表和我的页面显示文案，活动详情不显示文案；如果是当前活动则显示想要的文案 */
            if ($isLastActivity) {
                if ($type == self::TYPE_ORDER_LIST || $type == self::TYPE_MINE) {
                    $ret['url'] = 'lelaishop://cumulativeReturn/detail';
                } else {
                    $ret['url'] = '';
                }
                if ($leftDays <= 0) {
                    $ret['tips'] = "<font color='#81838E'>有未领取优惠券，明天将过期，点击领取</font>";
                } else {
                    $ret['tips'] = "<font color='#81838E'>有未领取优惠券，为您保留{$leftDays}天，点击领取</font>";
                }
            } else {
                $discount = self::getCouponDiscount($coupons, $coupon2DIds[$idx]);
                if ($idx == count($levels) - 1) { // 最后一个(最高级别)
                    $ret['tips'] = "<font color='#81838E'>您已购满{$curTotal}元，返{$discount}元优惠券%s。</font>";
                    // 详情不设置跳转
                    if ($type != self::TYPE_DETAIL) {
                        $ret['url'] = 'lelaishop://cumulativeReturn/detail';
                        $ret['tips'] = sprintf($ret['tips'], '，查看详情');
                    } else {
                        $ret['tips'] = sprintf($ret['tips'], '');
                    }
                } else {
                    $nextDiscount = self::getCouponDiscount($coupons, $coupon2DIds[$idx + 1]);
                    $ret['tips'] = "<font color='#81838E'>您已购满{$curTotal}元，可返{$discount}元优惠券，再购"
                        . number_format(($levels[$idx + 1]['level'] - $curTotal), 2, '.', '')
                        . "元，即可领取{$nextDiscount}元优惠券。再去下单</font>";
                    $ret['url'] = 'lelaishop://page/home';
                }
            }
            break;
        }

        /* $idx为-1，则一个都没符合。*/
        if ($idx == -1) {
            if ($isLastActivity) {
                /* return null; // 如果是上期活动，则直接返回null。2.9后面又去掉了！！！ */
            } else {
                $nextDiscount = self::getCouponDiscount($coupons, $coupon2DIds[0]);
                $ret['tips'] = "<font color='#81838E'>您已购{$curTotal}元，再购买"
                    . number_format(($levels[0]['level'] - $curTotal), 2, '.', '')
                    . "元，即可领取{$nextDiscount}元优惠券。再去下单</font>";
                $ret['url'] = 'lelaishop://page/home';
            }
        }

        /* levels设置，前面增加级别0 */
        $ret['levels'] = array_column($levels, 'level');
        array_unshift($ret['levels'], 0);

        /* 类型为优惠券详情则返回更多信息 */
        $canGetCouponNum = 0; // 可以领取的优惠券数量
        if ($type == self::TYPE_DETAIL || $isLastActivity) {
            // banner
            if (false !== preg_match('/height=(\d+)/', $activity['banner'], $matches)) {
                $height = isset($matches[1]) ? $matches[1] : 210;
            }
            $height = $height ?: 210;
            $ret['banner'] = [
                'src' => $activity['banner'],
                'height' => $height
            ];
            // 详情
            $ret['detail'] = nl2br($activity['detail']);

            /* 优惠券列表状态、可领取的优惠券金额 */
            $couponMaxDiscount = 0.0;   // 可领取的优惠券金额
            foreach ($coupons as $coupon) {
                if ($couponDetail = Rule::getCouponDetail($coupon, ['fromLeiJiManFanDetail' => 1])) {
                    if ($idx == -1) {
                        $couponDetail['state'] = self::STATE_CANNOT_GET;
                    } else {
                        /* 遍历所有小于等于当前级别的优惠券*/
                        $curIdx = $idx;
                        while($curIdx >= 0) {
                            if (in_array($coupon->rule_id, $coupon2DIds[$curIdx])) {  // 符合当前级别
                                if (UserCoupon::findOne([
                                    'customer_id' => $customer->getCustomerId(),
                                    'rule_id' => $coupon->rule_id
                                ])) {
                                    $couponDetail['state'] = self::STATE_AREADY_GOT;
                                } else {
                                    $canGetCouponNum++;
                                    $couponDetail['state'] = self::STATE_CAN_GET;
                                    $couponMaxDiscount += $coupon->max_discount_value;
                                }
                                break;
                            } else {
                                $couponDetail['state'] = self::STATE_CANNOT_GET;
                                --$curIdx;
                            }
                        }
                    }
                    $ret['coupon_list'][] = $couponDetail;
                }
            }
            // 可领取优惠券的优惠金额
            $ret['coupon_max_discount'] = number_format($couponMaxDiscount, 2, '.', '');
            if (!$isLastActivity) {
                $ret['has_last_activity'] = self::getActivityDetail2($customer, self::TYPE_LAST_DETAIL) ? 1 : 0;
            }
        }

        /* 如果是上一个活动，则设置相应的提示语 */
        if ($isLastActivity) {
            $ret['status'] = self::STATUS_END;
            $ret['status_str'] = '已结束';
            if ($canGetCouponNum <= 0) {
                if ($type == self::TYPE_ORDER_LIST) {
                    return null;
                } else {
                    $ret['tips'] = '活动已结束';
                }
            } else {
                if ($leftDays <= 0) {
                    $ret['expired_tips'] = "活动有{$canGetCouponNum}张未领取优惠券，明天将过期";
                } else {
                    $ret['expired_tips'] = "活动有{$canGetCouponNum}张未领取优惠券，为您保留{$leftDays}天";
                }
            }
        }

        return $ret;
    }

    /**
     * 获取优惠券额度
     *
     * @param array $coupons
     * @param array $couponIds
     * @return double
     */
    private static function getCouponDiscount(array $coupons, array $couponIds)
    {
        $ret = 0.0;
        foreach ($couponIds as $couponId) {
            /** @var Rule $coupon */
            foreach ($coupons as $coupon) {
                if ($coupon->rule_id == $couponId) {
                    $ret += $coupon->max_discount_value;
                    break;
                }
            }
        }
        return number_format($ret, 2, '.', '');
    }

    /**
     * 获取当前时间处于开始时间和结束时间之间的活动
     *
     * @param CustomerResponse $customer
     * @return CumulativeReturnActivity|null
     */
    public static function getCurrentActivity(CustomerResponse $customer)
    {
        /** @var CustomerResponse $customer */
        $curTimestamp = ToolsAbstract::getDate()->timestamp();
        $curDateTime = date('Y-m-d H:i:s', $curTimestamp);
        /** @var CumulativeReturnActivity $activity */
        $activity = self::find()->where([
            'city' => $customer->getCity(),
            'status' => self::STATUS_ENABELD
        ])->andWhere(['<=', 'from_date', $curDateTime])
            ->andWhere(['>=', 'to_date', $curDateTime])
            ->one();

        return $activity;
    }

    /**
     * 获取多少天前没有结束的活动
     *
     * @param CustomerResponse $customer
     * @param integer|integer[] $excludeIds
     * @param integer $daysBefore 多少天前
     * @return CumulativeReturnActivity|null
     */
    public static function getLastActivity($customer, $excludeIds = [], $daysBefore = self::EXPIRED_DAYS_AFTER_ACTIVITY)
    {
        /** @var CustomerResponse $customer */
        $curTimestamp = ToolsAbstract::getDate()->timestamp();
        $curDateTime = date('Y-m-d H:i:s', $curTimestamp);
        $daysBeforeDateTime = date('Y-m-d H:i:s', strtotime("-{$daysBefore} days", $curTimestamp));
        /** @var CumulativeReturnActivity $activity */
        $activity = self::find()->where([
            'city' => $customer->getCity(),
            'status' => CumulativeReturnActivity::STATUS_ENABELD
        ])->andWhere(['<=', 'from_date', $curDateTime])
            ->andWhere(['>=', 'to_date', $daysBeforeDateTime])
            ->andWhere(['not in', 'entity_id', $excludeIds])
            ->orderBy('from_date DESC') // 活动有重叠，取最新一个
            ->one();

        return $activity;
    }
}
