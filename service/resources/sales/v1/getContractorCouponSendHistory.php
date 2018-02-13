<?php
/**
 * Created by PhpStorm.
 * User: zgr0629
 * Date: 8/12/2016
 * Time: 5:48 PM
 */

namespace service\resources\sales\v1;

use common\models\ContractorCouponAmount;
use common\models\LeCustomer;
use common\models\SalesFlatOrder;
use common\models\salesrule\Rule;
use common\models\salesrule\UserCoupon;
use framework\components\ToolsAbstract;
use framework\data\Pagination;
use service\components\Tools;
use service\message\common\SourceEnum;
use service\message\sales\getContractorCouponHistoryRequest;
use service\message\sales\getContractorCouponHistoryResponse;
use service\message\sales\getCustomerCouponListRequest;
use service\resources\ResourceAbstract;

class getContractorCouponSendHistory extends ResourceAbstract
{
    const PAGE_SIZE = 10;
    const MAX_PAGE_SIZE = 50;

    public function run($data)
    {
        /** @var getCustomerCouponListRequest $request */
        $request = self::request();
        $request->parseFromString($data);

        if ($this->getSource() == SourceEnum::ANDROID_CONTRACTOR || $this->getSource() == SourceEnum::IOS_CONTRACTOR) {
            //来自业务员的请求，不验证用户
        } else {
            $this->_initCustomer($request);
        }
        // 业务员删选 普通业务员只能查看自己的发放历史 其他的可以筛选业务员
        $contractor_id = 0;
        $contractor = $this->_initContractor($request->getContractorId(), $request->getAuthToken());
        if ($contractor->getRole() == self::COMMON_CONTRACTOR) {
            $contractor_id = $contractor->getContractorId();
        } else {
            if ($request->getFilterContractorId() > 0) {
                $contractor_id = $request->getFilterContractorId();
            }
        }

        $couponsStat = UserCoupon::find()->where(['contractor_id' => $contractor_id]);

        $totalCount = $couponsStat->count();
        $has_used = $couponsStat->andWhere(['state' => 2])->count();
        $not_used = $totalCount - $has_used;

        $coupons = UserCoupon::find()
            ->joinWith('rule')
            ->where(['salesrule_user_coupon.contractor_id' => $contractor_id])
            ->orderBy('salesrule_user_coupon.created_at DESC');

        // 是否查询已使用或者未使用 2未使用 1已使用
        if ($request->getUsed() > 0) {
            // 未使用
            $coupons->andWhere(['salesrule_user_coupon.state' => $request->getUsed()]);
        }
        // 日期筛选
        if ($request->getDate()) {
            $coupons->andWhere(['between', 'salesrule_user_coupon.created_at', date('Y-m-01 00:00:00', strtotime($request->getDate())), date('Y-m-t 23:59:59', strtotime($request->getDate()))]);
        }

        //分页
        $page = $request->getPagination() && $request->getPagination()->getPage() ? $request->getPagination()->getPage() : 1;
        $page_size = $request->getPagination() && $request->getPagination()->getPageSize() ? $request->getPagination()->getPageSize() : self::PAGE_SIZE;
        if ($page_size <= 0) {
            $page_size = self::PAGE_SIZE;
        } elseif ($page_size > self::MAX_PAGE_SIZE) {
            $page_size = self::MAX_PAGE_SIZE;
        }

        $pages = new Pagination(['totalCount' => $totalCount]);
        $pages->setCurPage($page);
        $pages->setPageSize($page_size);

        $responsePage = [
            'total_count' => $pages->getTotalCount(),
            'page' => $pages->getCurPage(),
            'last_page' => $pages->getLastPageNumber(),
            'page_size' => $pages->getPageSize(),
        ];

        if ($page > $pages->getLastPageNumber()) {
            $page = $pages->getLastPageNumber();
        }
        if ($page <= 0) {
            $page = 1;
        }

        $coupons = $coupons->offset(($page - 1) * $page_size)->limit($page_size)->all();
        $coupon_list = [];
        /** @var UserCoupon $coupon */
        foreach ($coupons as $coupon) {
            if (!$coupon || !$coupon->rule || !$coupon->rule->rule_id) {
                //优惠券查询不到对应的活动，跳过
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
            } // 多品级跳优惠专题页
            elseif ($coupon->rule->type == 2) {
                $url = "lelaishop://topicV4/list?rid={$coupon->rule->rule_id}";
            } // 订单级如果是单个商家则跳商家页
            elseif ($coupon->rule->type == 3) {
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

            //有效期
            $validity_time = '';
            if ($coupon->rule->from_date) {
                $validity_time = date("m月d日 H:i", strtotime($coupon->rule->from_date));
            }
            $validity_time .= " 至 " . date("m月d日 H:i", strtotime($coupon->expiration_date)) . "有效";

            // 若是已经使用，则显示已经使用的订单的信息
            $order_coupon = '';
            if ($coupon->state == 2) {
                $orderInfo = SalesFlatOrder::findOne(['coupon_id' => $coupon->entity_id]);
                if ($orderInfo) {
                    $order_coupon = '下单时间:' . date('Y-m-d H:i:s', strtotime('+8 hours', strtotime($orderInfo->created_at))) . ',' . "\r\n" . '金额:￥' . $orderInfo->grand_total;
                }
            }

            // 查店铺的名字
            $customerInfo = LeCustomer::find()->where(['entity_id' => $coupon->customer_id])->one();

            // 当前时间
            $date = ToolsAbstract::getDate();
            $currentDate = $date->date('Y-m-d H:i:s');

            $coupon_list[] = [
                'entity_id' => $coupon->entity_id,
                'customer_id' => $coupon->customer_id,
                'customer_name' => $customerInfo ? $customerInfo->store_name : '',
                'state' => $coupon->state,
                'rule_id' => $coupon->rule_id,
                'expiration_date' => $coupon->expiration_date,
                'source' => $coupon->source,
                'created_at' => $coupon->created_at,
                'coupon_title' => $coupon->rule->coupon_title,
                'frontnote' => $coupon->rule->frontnote,
                'discount_type' => $coupon->rule->getDiscountType(),
                'discount' => $coupon->rule->getDiscountAmount(),
                'use_condition' => $use_condition,
                'is_soon_expire' => $coupon->isSoonExpire(),
                'is_expire' => (strtotime($coupon->expiration_date) < strtotime($currentDate)) ? 1 : 0,
                'url' => $url,
                'validity_time' => $validity_time,
                'order_coupon' => $order_coupon,
            ];

        }
        // 查询该业务员的发券额度是否已经用完
        // 查询业务员本月的额度
        $mothQuota = ContractorCouponAmount::find()->where(['contractor_id' => $contractor_id, 'month' => date('Y-m')])->one();
        $hasQuota = 0;// 业务员本月额度
        if ($mothQuota) {
            $hasQuota = $mothQuota->amount;
        }
        // 从redis中取出业务员已发放额度
        $redis = Tools::getRedis();
        $redisQuota = 0;
        if ($redis->exists('|' . $contractor_id . '|' . date('Y-m') . '|')) {
            $redisQuota = $redis->get('|' . $contractor_id . '|' . date('Y-m') . '|');
        }

        $information = '总额度:￥' . $hasQuota . ',已使用额度:￥' . $redisQuota . ',' . "\r\n" . '共发放:' . $totalCount . '张优惠券,已使用' . $has_used . '张,使用率' . (round(($totalCount > 0 ? ($has_used / $totalCount) : 0), 2) * 100) . '%';

        $response = self::response();
        $response->setFrom(Tools::pb_array_filter([
            'information' => $information,
            'has_used' => $has_used,
            'not_used' => $not_used,
            'coupon_list' => $coupon_list,
            'pagination' => $responsePage,
        ]));
        return $response;
    }

    public static function request()
    {
        return new getContractorCouponHistoryRequest();
    }

    public static function response()
    {
        return new getContractorCouponHistoryResponse();
    }

}