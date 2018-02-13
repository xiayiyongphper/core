<?php

namespace common\models\salesrule;

use framework\components\Date;
use framework\components\ToolsAbstract;
use framework\Exception;
use service\components\Tools;
use service\models\salesrule\rule\condition\Combine;
use service\models\VarienObject;
use Yii;
use framework\db\ActiveRecord;

/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2016/1/26
 * Time: 18:07
 */

/**
 * Class SalesRule
 * @package common\models
 * @property integer $rule_id
 * @property string $name
 * @property string $simple_action
 * @property int $is_active
 * @property int $apply_to_shipping
 * @property int $coupon_type
 * @property int $discount_qty
 * @property int $stop_rules_processing
 * @property float $discount_amount
 * @property integer $uses_per_coupon
 * @property integer $uses_per_customer
 * @property string $conditions_serialized
 * @property string $topic_description
 * @property string $topic_banner
 * @property string $tag_short_color
 * @property string $tag_short
 * @property string $tag_long
 * @property string $tag_long_color
 * @property string $tag_icon
 * @property string $tag_url
 * @property string $wholesaler_description
 * @property string $to_date
 * @property string $from_date
 * @property integer $type
 * @property integer $apply_period
 * @property int $is_del
 * @property string $coupon_title
 * @property string $frontnote
 * @property integer $coupon_mutex
 * @property string $city
 * @property integer $product_id
 * @property string $store_id
 * @property integer $rule_founder
 * @property float $apportion_lelai
 * @property float $apportion_wholesaler
 * @property boolean $subsidies_lelai_included
 * @property double $max_discount_value
 * @property int $rule_uses_limit 每用户总限用次数
 * @property int $rule_uses_daily_limit 活动每日使用次数限制，0：无限制
 */
class Rule extends ActiveRecord
{

    const RULE_COUPON = 2;  //只能填优惠码领取或主动发放
    const RULE_PROMOTION = 1;
    const RULE_COUPON_SHOW = 5; //展示在前端，只可页面领取或主动发放
    const RULE_COUPON_SEND = 4; //只可主动发放
    const SUBSIDIES_LELAI_INCLUDED_YES = 1;
    const SUBSIDIES_LELAI_INCLUDED_NO = 0;

    public $wholesaler_id;

    /**
     * Free Shipping option "For matching items only"
     */
    const FREE_SHIPPING_ITEM = 1;

    /**
     * Free Shipping option "For matching items only"
     */
    const FREE_SHIPPING = 1;

    /**
     * Free Shipping option "For shipment with matching items"
     */
    const FREE_SHIPPING_ADDRESS = 2;

    /**
     * Coupon types
     */
    const COUPON_TYPE_NO_COUPON = 1;
    const COUPON_TYPE_SPECIFIC = 2;
    const COUPON_TYPE_AUTO = 3;
    const COUPON_MUTEX_YES = 1;
    const COUPON_MUTEX_NO = 2;
    const COUPON_TYPE_CUMULATIVE_RETURN = 6;   // 累计满返

    /**
     * Rule type actions
     */
    const BY_PERCENT_ACTION = 'by_percent';
    const BY_FIXED_ACTION = 'by_fixed';
    const BUY_X_GET_Y_FREE_ACTION = 'buy_x_get_y_free';

    //优惠级别，1.单品级,2.多品级,3.订单级
    const TYPE_ITEM = 1;
    const TYPE_GROUP = 2;
    const TYPE_ORDER = 3;

    const RULE_FOUNDER_LELAI = 1;
    const RULE_FOUNDER_WHOLESALER = 2;
    const UNAVAILABLE_REASON_1 = '不满使用金额';
    const UNAVAILABLE_REASON_2 = '不满使用金额数量';
    const UNAVAILABLE_REASON_3 = '指定商品不满使用金额';
    const UNAVAILABLE_REASON_4 = '本单不是优惠券指定的供货商';
    const UNAVAILABLE_REASON_5 = '本券不能与其他优惠同享';
    const UNAVAILABLE_REASON_6 = '指定商品不满使用数量';
    const UNAVAILABLE_REASON_7 = '不满足使用条件';
    const MAX_DISCOUNT_AMOUNT = 999999;

    /**
     * Store rule combine conditions model
     *
     * @var Combine
     */
    protected $_conditions;

    /**
     * @var bool
     */
    protected $_applied = false;

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'salesrule';
    }

    /**
     * @return \yii\db\Connection the database connection used by this AR class.
     */
    public static function getDb()
    {
        return Yii::$app->get('mainDb');
    }

    public function getMultiDiscountAmount($ruleValidResult = 1)
    {

        // 准备分级减额
        $discountAmount = $this->discount_amount;
        // 分级数组
        $discountAmountArray = preg_split('#\s*[,;]\s*#', $discountAmount, null, PREG_SPLIT_NO_EMPTY);
        // 取相应级数减额
        if (isset($discountAmountArray[$ruleValidResult - 1])) {
            $discountAmount = $discountAmountArray[$ruleValidResult - 1];
        } else {
            // 若设置错误则取第一级
            $discountAmount = $discountAmountArray[0];
        }
        return $discountAmount;
    }

    public function getMultiApportionLelai($ruleValidResult = 1)
    {
        // 准备分级减额
        $discountAmount = $this->apportion_lelai;
        // 分级数组
        $discountAmountArray = preg_split('#\s*[,;]\s*#', $discountAmount, null, PREG_SPLIT_NO_EMPTY);
        // 取相应级数减额
        if (isset($discountAmountArray[$ruleValidResult - 1])) {
            $discountAmount = $discountAmountArray[$ruleValidResult - 1];
        } else {
            // 若设置错误则取第一级
            $discountAmount = $discountAmountArray[0];
        }
        return $discountAmount;
    }

    public function getMultiApportionWholesaler($ruleValidResult = 1)
    {
        // 准备分级减额
        $discountAmount = $this->apportion_wholesaler;
        // 分级数组
        $discountAmountArray = preg_split('#\s*[,;]\s*#', $discountAmount, null, PREG_SPLIT_NO_EMPTY);
        // 取相应级数减额
        if (isset($discountAmountArray[$ruleValidResult - 1])) {
            $discountAmount = $discountAmountArray[$ruleValidResult - 1];
        } else {
            // 若设置错误则取第一级
            $discountAmount = $discountAmountArray[0];
        }
        return $discountAmount;
    }

    /**
     * Getter for rule combine conditions instance
     *
     * @return Combine
     */
    public function getConditionsInstance()
    {
        return new Combine();
    }

    /**
     * Prepare data before saving
     * @param bool $insert
     * @return $this
     */
    public function beforeSave($insert)
    {
        // Check if discount amount not negative
        if ($this->discount_amount) {
            if ((int)$this->discount_amount < 0) {
                Exception::throwException('Invalid discount amount.');
            }
        }

        // Serialize conditions
        if ($this->_conditions) {
            $this->conditions_serialized = serialize($this->getConditions()->asArray());
            $this->_conditions = null;
        }

        parent::beforeSave($insert);
        return $this;
    }

    /**
     * Set rule combine conditions model
     *
     * @param Combine $conditions
     *
     * @return $this
     */
    public function setConditions($conditions)
    {
        $this->_conditions = $conditions;
        return $this;
    }

    /**
     * Retrieve rule combine conditions model
     *
     * @return Combine
     */
    public function getConditions()
    {
        if (empty($this->_conditions)) {
            $this->_resetConditions();
        }

        // Load rule conditions if it is applicable
        if ($this->conditions_serialized) {
            $conditions = $this->conditions_serialized;
            if (!empty($conditions)) {
                $conditions = unserialize($conditions);
                if (is_array($conditions) && !empty($conditions)) {
                    $this->_conditions->loadArray($conditions);
                }
            }
            $this->conditions_serialized = null;
        }

        return $this->_conditions;
    }


    /**
     * Reset rule combine conditions
     *
     * @param null|Combine $conditions
     *
     * @return $this
     */
    protected function _resetConditions($conditions = null)
    {
        if (is_null($conditions)) {
            $conditions = $this->getConditionsInstance();
        }
        $conditions->setRule($this)->setId('1')->setPrefix('conditions');
        $this->setConditions($conditions);

        return $this;
    }


    /**
     * Validate rule conditions to determine if rule can run
     *
     * @param VarienObject $object
     *
     * @return bool
     */
    public function validateConditions(VarienObject $object)
    {
        return $this->getConditions()->validate($object);
    }

    /**
     * @param $rule_ids
     * Author Jason Y. wang
     * 用于购物车商品获取优惠信息
     * @return array|bool
     */
    public static function getProductPromotions($rule_ids)
    {
        if (count($rule_ids) == 0) {
            return false;
        }
        //Tools::log($rule_ids,'wangyang.log');
        $rules = self::getProductRules($rule_ids);
        //Tools::log($rules,'wangyang.log');
        if ($rules) {
            return self::getPromotions($rules);
        } else {
            return false;
        }
    }

    /**
     * @param $wholesaler_ids
     * Author Jason Y. wang
     * 用于购物车供应商获取优惠信息
     * @return array|bool
     */
    public static function getWholesalerPromotions($wholesaler_ids)
    {
        if (count($wholesaler_ids) == 0) {
            return false;
        }

        $rules = self::getWholesalerRules($wholesaler_ids);
//        Tools::log($rules, 'rules.log');
        if ($rules) {
            $return = self::getPromotions($rules);
//            Tools::log('----------return------------', 'rules.log');
//            Tools::log($return, 'rules.log');
//            Tools::log('----------return------------', 'rules.log');
            return $return;
        } else {
            return false;
        }
    }

    /**
     * 用于统计综合得分脚本获取供应商全部优惠信息
     * @date  2017-06-12
     * @param $wholesaler_ids
     * @return array|bool
     */
    public static function getWholesalerAllPromotions($wholesaler_ids)
    {
        if (count($wholesaler_ids) == 0) {
            return false;
        }

        $rules = self::getWholesalerAllRules($wholesaler_ids);
        Tools::log($rules, 'debug.txt');
        if ($rules) {
            $return = self::getPromotions($rules);
            Tools::log('----------return------------', 'debug.txt');
            Tools::log($return, 'debug.txt');
            Tools::log('----------return------------', 'debug.txt');
            return $return;
        } else {
            return false;
        }
    }


    private static function getWholesalerRules($wholesaler_ids)
    {
        $rules = [];
        $date = new Date();
        $date = $date->date();
        //coupon_type  1  无优惠券规则，运营后台保证只有一个规则生效 type  2,3  多品级、订单级优惠  生效时间
        foreach ($wholesaler_ids as $wholesaler_id) {
            /** @var Rule $rule */
            $query = self::find()
                ->where(['is_active' => 1])
                ->andWhere(['coupon_type' => [Rule::RULE_PROMOTION, Rule::RULE_COUPON_SHOW]])//优惠券也要展示在前端
//                ->andWhere(['coupon_type' => Rule::RULE_PROMOTION]) //优惠券也要展示在前端
                ->andWhere(['type' => [Rule::TYPE_GROUP, Rule::TYPE_ORDER]])
                ->andWhere(['is_del' => 0])
                ->andWhere(['<', 'from_date', $date])
                ->andWhere(['>', 'to_date', $date])
                //目前乐来的多品级活动，store_id是空，不允许配置乐来多品级活动，其他情况都都有sotre_id,根据store_id筛选 by ryan
                // ->andWhere(['or', ['like', 'store_id', '|' . $wholesaler_id . '|'], ['store_id' => '||']])
                ->andWhere(['like', 'store_id', '|' . $wholesaler_id . '|'])
                ->orderBy(['type' => SORT_DESC]);

            Tools::log('---------------sql: ' . $query->createCommand()->getRawSql(), 'hl.log');
            $wholesaler_rules = $query->all();

            /** @var Rule $wholesaler_rule */
            foreach ($wholesaler_rules as $wholesaler_rule) {
                $wholesaler_rule->wholesaler_id = $wholesaler_id;
                $wholesaler_rule->from_date = $wholesaler_rule->from_date ? substr($wholesaler_rule->from_date, 0, -3) : $wholesaler_rule->from_date;
                $wholesaler_rule->to_date = $wholesaler_rule->to_date ? substr($wholesaler_rule->to_date, 0, -3) : $wholesaler_rule->to_date;
                array_push($rules, $wholesaler_rule);
            }
        }

        return $rules;
    }

    /**
     * 除了不做type过滤，基本同getWholesalerRules
     * @param $wholesaler_ids
     * @return array
     */
    private static function getWholesalerAllRules($wholesaler_ids)
    {
        $rules = [];
        $date = new Date();
        $date = $date->date();
        foreach ($wholesaler_ids as $wholesaler_id) {
            /** @var Rule $rule */
            $query = self::find()
                ->where(['is_active' => 1])
                ->andWhere(['coupon_type' => [Rule::RULE_PROMOTION, Rule::RULE_COUPON_SHOW]])//优惠券也要展示在前端
                ->andWhere(['is_del' => 0])
                ->andWhere(['<', 'from_date', $date])
                ->andWhere(['>', 'to_date', $date])
                ->andWhere(['like', 'store_id', '|' . $wholesaler_id . '|'])
                ->orderBy(['type' => SORT_DESC]);
            Tools::log('---------------sql: ' . $query->createCommand()->getRawSql(), 'debug.txt');
            $wholesaler_rules = $query->all();

            /** @var Rule $wholesaler_rule */
            foreach ($wholesaler_rules as $wholesaler_rule) {
                $wholesaler_rule->wholesaler_id = $wholesaler_id;
                $wholesaler_rule->from_date = $wholesaler_rule->from_date ? substr($wholesaler_rule->from_date, 0, -3) : $wholesaler_rule->from_date;
                $wholesaler_rule->to_date = $wholesaler_rule->to_date ? substr($wholesaler_rule->to_date, 0, -3) : $wholesaler_rule->to_date;
                array_push($rules, $wholesaler_rule);
            }
        }

        return $rules;
    }


    private static function getProductRules($rule_ids)
    {
        $date = new Date();
        $date = $date->date();
        // 生效时间  是否删除
        $rule = self::find()
            ->where(['rule_id' => $rule_ids])
            ->andWhere(['is_del' => 0])
            ->andWhere(['is_active' => 1])
            ->andWhere(['<', 'from_date', $date])
            ->andWhere(['>', 'to_date', $date])
            ->andWhere(['coupon_type' => [Rule::RULE_PROMOTION, Rule::RULE_COUPON_SHOW]])//优惠券也要展示在前端
            ->all();
        return $rule;
    }


    /**
     * Author Jason Y. wang
     *
     * @param $rules
     * @return array|bool
     */
    private static function getPromotions($rules)
    {
        $promotions = [];
        /** @var Rule $rule */
        foreach ($rules as $rule) {
            if ($rule) {
                $rule_conditions = unserialize($rule->conditions_serialized);
                if (!isset($rule_conditions['conditions'])) {
                    continue;
                }
                $conditions = $rule_conditions['conditions']['0'];
                switch ($conditions['attribute']) {
                    case 'subtotal': //满额
                        if ($rule->simple_action == Rule::BY_FIXED_ACTION) {
                            $promotion['promotion_type'] = 1;//满额减
                        } else if ($rule->simple_action == Rule::BY_PERCENT_ACTION) {
                            $promotion['promotion_type'] = 2;//满额折
                        } else if ($rule->simple_action == Rule::BUY_X_GET_Y_FREE_ACTION) {
                            $promotion['promotion_type'] = 3;//满额赠
                        }
                        break;
                    case 'total_qty'://满量
                        if ($rule->simple_action == Rule::BY_FIXED_ACTION) {
                            $promotion['promotion_type'] = 4;//满量减
                        } else if ($rule->simple_action == Rule::BY_PERCENT_ACTION) {
                            $promotion['promotion_type'] = 5;//满量折
                        } else if ($rule->simple_action == Rule::BUY_X_GET_Y_FREE_ACTION) {
                            $promotion['promotion_type'] = 6;//满量赠
                        }

                        break;
                    default:

                        break;
                }

                $promotion['type'] = $rule->type;  //优惠级别，1.单品级,2.多品级,3.订单级
                $promotion['rule'] = self::getCondition($conditions['value'], $rule->discount_amount);
                $promotion['rule_id'] = $rule->rule_id;
                $promotion['name'] = $rule->name;
                $promotion['topic_description'] = $rule->topic_description;
                $promotion['topic_banner'] = $rule->topic_banner;
                $promotion['tag_short'] = $rule->tag_short;
                $promotion['tag_short_color'] = $rule->tag_short_color;
                $promotion['tag_long'] = $rule->tag_long;
                $promotion['tag_long_color'] = $rule->tag_long_color;
                $promotion['wholesaler_id'] = $rule->wholesaler_id;
                $promotion['wholesaler_description'] = $rule->wholesaler_description;
                $promotion['tag_url'] = $rule->tag_url;
                $promotion['tag_icon'] = $rule->tag_icon;
                $promotion['stop_rules_processing'] = $rule->stop_rules_processing;
                $promotion['coupon_type'] = $rule->coupon_type;
                $promotion['subsidies_lelai_included'] = $rule->subsidies_lelai_included;
                $promotion['from_date'] = $rule->from_date;
                $promotion['to_date'] = $rule->to_date;
                $promotion['rule_uses_limit'] = $rule->rule_uses_limit;
                $promotion['rule_detail'] = $rule->rule_detail;

                $promotions[] = $promotion;
            }
        }
        return $promotions;
    }

    public static function getCondition($value, $discount_amount)
    {
        $rules = [];
        $conditions = array_values(array_filter(explode(',', $value)));
        $off = array_values(array_filter(explode(',', $discount_amount)));
        foreach ($conditions as $key => $condition) {
            if (isset($off[$key])) {
                $rule['condition'] = $condition;
                $rule['off'] = $off[$key];
                $rules[] = $rule;
            }
        }
        return $rules;
    }

    public static function getCouponRuleByRuleId($rule_id, $coupon_type)
    {
        $date = new Date();
        $date = $date->date();
        // 生效时间  是否删除
        $rule = self::find()
            ->where(['rule_id' => $rule_id])
            ->andWhere(['is_del' => 0])
            ->andWhere(['is_active' => 1])
            ->andWhere(['>', 'to_date', $date]);//没开始的优惠券是可以领取的
        
        if (!empty($coupon_type)) {
            $rule->andWhere(['coupon_type' => $coupon_type]);
        }

//        Tools::log($rule->createCommand()->getRawSql(),'wangyang.log');
        $rule = $rule->one();
        return $rule;
    }

    /**
     * 根据id和类型（单品/多品等）获取优惠券
     *
     * @param array|integer $ruleIds
     * @param integer $couponType
     * @return Rule[]
     */
    public static function getCouponRulesByRuleIdsCouponType($ruleIds, $couponType)
    {
        $date = new Date();
        $date = $date->date();
        // 生效时间  是否删除
        $rules = self::find()->where([
            'rule_id' => $ruleIds,
            'is_del' => 0,
            'is_active' => 1,
            'coupon_type' => $couponType
        ])->andWhere(['>', 'to_date', $date])->all();

        return $rules;
    }


    /**
     * @param $coupon_code
     * Author Jason Y. wang
     * 绑定优惠券的规则
     * @return array|null|ActiveRecord
     */
    public static function getCouponRuleByCouponCode($coupon_code)
    {
        $date = new Date();
        $date = $date->date();
        // 生效时间  是否删除
        $rule = self::find()
            ->where(['coupon_code' => $coupon_code])
            ->andWhere(['is_del' => 0])
            ->andWhere(['is_active' => 1])
            ->andWhere(['>', 'to_date', $date])//没开始的优惠券是可以领取的
            ->andWhere(['coupon_type' => Rule::RULE_COUPON]);
        //Tools::log($rule->createCommand()->getRawSql(),'wangyang.log');
        $rule = $rule->one();
        return $rule;
    }


    /**
     * @param $wholesaler_id
     * Author Jason Y. wang
     * 绑定优惠券的规则
     * @return array|\yii\db\ActiveRecord[]
     */
    private static function getCouponRulesByWholesaler($wholesaler_id, $sort = null)
    {
        $date = new Date();
        $date = $date->date();
        //coupon_type  1  无优惠券规则，运营后台保证只有一个规则生效 type  3  订单级优惠  生效时间
        /** @var Rule $rule */
        $rules = self::find()
            ->where(['coupon_type' => Rule::RULE_COUPON_SHOW])
            ->andWhere(['type' => 3])
            ->andWhere(['is_del' => 0])
            ->andWhere(['is_active' => 1])
            ->andWhere(['>', 'to_date', $date])//没开始的优惠券是可以领取的
            ->andWhere(['or', ['like', 'store_id', '|' . $wholesaler_id . '|'], ['store_id' => '||']]);
        if (!is_null($sort)) {
            $rules->orderBy($sort);
        }
        //Tools::log($rules->createCommand()->getRawSql(),'hl.log');
        $rules = $rules->all();
        return $rules;
    }

    /**
     * @param null $rule_id
     * @param null $wholesaler_id
     * Author Jason Y. wang
     * 生成优惠券领取列表
     * @return array
     */
    public static function generateCoupons($rule_id = null, $wholesaler_id = null, $params = [])
    {
        $coupons = [];
        $rulesById = [];
        $rulesByWholesaler = [];

        //根据规则ID获取优惠券
        if (!is_null($rule_id)) {
            $rulesById = self::getCouponRuleByRuleId($rule_id, Rule::RULE_COUPON_SHOW);
        }
        //TODO：考虑加入缓存

        //获取店铺级优惠券
        if (!is_null($wholesaler_id)) {
            $sort = isset($params['sort']) ? $params['sort'] : null;
            $rulesByWholesaler = self::getCouponRulesByWholesaler($wholesaler_id, $sort);
        }

        //TODO：考虑加入缓存
        $rules = array_merge([$rulesById], $rulesByWholesaler);

        /** @var Rule $rule */
        foreach ($rules as $rule) {
            if ($coupon = self::getCouponDetail($rule, $params)) {
                array_push($coupons, $coupon);
            }
        }
        return $coupons;
    }

    /**
     * 获取优惠券的详情信息
     *
     * @param Rule $rule
     * @param array $params
     * @return array|null
     */
    public static function getCouponDetail($rule, $params = [])
    {
        if (empty($rule)) {
            return null;
        }

        $coupon = [];
        //优惠券关联ID
        $coupon['rule_id'] = $rule->rule_id;
        //优惠级别，1.单品级,2.多品级,3.订单级 优惠券类型  商品券  订单券
        if ($rule->type == 3) { // 订单级
            if (!empty($params['fromLeiJiManFanDetail'])) {
                $coupon['type'] = '全场通用';
            } else {
                $coupon['type'] = '全店通用';
            }
        } else if ($rule->type == 2) { // 多品级
            if (!empty($params['fromTopic'])) {
                $coupon['type'] = '仅限购买本专题商品';
            } else if (!empty($params['fromDetail'])) {
                $coupon['type'] = '查看指定商品 ＞';
            } else {
                $coupon['type'] = '仅限购买指定商品';
            }
            $coupon['url'] = 'lelaishop://topicV4/list?rid=' . $rule->rule_id;
        } else if ($rule->type == 1) {
            $coupon['type'] = '仅限购买本商品';
        }

        //过期说明
        $coupon['expire_info'] = '领取后' . $rule->apply_period . '天内使用有效';
        // 可用的时间段信息，如果是累计满返的而且设置了N天有效
        if (!empty($params['fromLeiJiManFanDetail']) && $rule->apply_period > 0) {
            $coupon['date_range_info'] = '领取后' . $rule->apply_period . '天有效';
        } else {
            $coupon['date_range_info'] = date('n月j日', strtotime($rule->from_date)) . '至'
                . date('n月j日', strtotime($rule->to_date)) . '有效';
        }

        //优惠券名称
        $coupon['coupon_title'] = $rule->coupon_title;

        //优惠条件
        $rule_conditions = unserialize($rule->conditions_serialized);
        if (!isset($rule_conditions['conditions'])) {
            return null;
        }

        $conditions = $rule_conditions['conditions']['0'];
        switch ($conditions['attribute']) {
            case 'subtotal': //满额
                if ($rule->simple_action == Rule::BY_FIXED_ACTION) {
                    $coupon['discount_type'] = 1;//满额减
                } else if ($rule->simple_action == Rule::BY_PERCENT_ACTION) {
                    $coupon['discount_type'] = 2;//满额折
                } else if ($rule->simple_action == Rule::BUY_X_GET_Y_FREE_ACTION) {
                    $coupon['discount_type'] = 3;//满额赠
                }
                $action_levels = self::getCondition($conditions['value'], $rule->discount_amount);
                $conditionInfo = self::getCouponConditionInfo($action_levels, $conditions['attribute']);
                if ($conditionInfo) {
                    //满额折
                    if ($coupon['discount_type'] == 2) {
                        //折扣特殊处理
                        $coupon['discount'] = $conditionInfo['discount'] / 10;
                    } elseif ($coupon['discount_type'] == 3) {//满赠返回 ‘赠’
                        $coupon['discount'] = '赠';
                    } else {
                        $coupon['discount'] = $conditionInfo['discount'];
                    }
                    $coupon['use_condition'] = $conditionInfo['use_condition'];
                } else {
                    return null;
                }
                break;
            case 'total_qty'://满量
                if ($rule->simple_action == Rule::BY_FIXED_ACTION) {
                    $coupon['discount_type'] = 1;//满量减
                } else if ($rule->simple_action == Rule::BY_PERCENT_ACTION) {
                    $coupon['discount_type'] = 2;//满量折
                } else if ($rule->simple_action == Rule::BUY_X_GET_Y_FREE_ACTION) {
                    $coupon['discount_type'] = 3;//满量赠
                }
                $action_levels = self::getCondition($conditions['value'], $rule->discount_amount);
                $conditionInfo = self::getCouponConditionInfo($action_levels, $conditions['attribute']);
                if ($conditionInfo) {
                    if ($coupon['discount_type'] == 2) {
                        //折扣特殊处理
                        $coupon['discount'] = $conditionInfo['discount'] / 10;
                    } elseif ($coupon['discount_type'] == 3) {//满赠返回 ‘赠’
                        $coupon['discount'] = '赠';
                    } else {
                        $coupon['discount'] = $conditionInfo['discount'];
                    }
                    $coupon['use_condition'] = $conditionInfo['use_condition'];
                } else {
                    return null;
                }
                break;
            default:
                return null;
        }
        return $coupon;
    }

    /**
     * @param $action_levels
     * @param $conditions_attribute
     *
     * @return array|null
     */
    public static function getCouponConditionInfo($action_levels, $conditions_attribute)
    {
        $conditionInfo = [];
        if (count($action_levels) == 0) {
            return null;
        } else if (count($action_levels) == 1) {
            //单级优惠
            $condition = array_shift($action_levels);
        } else {
            //多级优惠，只取最后一级优惠
            $condition = array_pop($action_levels);
        }
        $conditionInfo['discount'] = $condition['off'];
        if ($conditions_attribute == 'subtotal') {
            $conditionInfo['use_condition'] = '满' . $condition['condition'] . '元可用';
        } elseif ($conditions_attribute == 'total_qty') {
            $conditionInfo['use_condition'] = '满' . $condition['condition'] . '件可用';
        } else {
            $conditionInfo['use_condition'] = '';
        }
        return $conditionInfo;
    }

    /**
     * @param UserCoupon $coupon
     * @return boolean
     */
    public function validateCoupon($coupon)
    {
        //活动启用，验证失败
        if (!$this->is_active || $this->is_del != 0) {
            return false;
        }

        //优惠券不能用于此活动，验证失败
        if ($coupon->rule_id != $this->rule_id) {
            return false;
        }

        $date = ToolsAbstract::getDate();
        $currentDate = $date->date('Y-m-d H:i:s');
        //优惠券已过期，验证失败
        if ($coupon->expiration_date < $currentDate) {
            return false;
        }

        //活动未开始，验证失败
        if ($this->from_date > $currentDate) {
            return false;
        }

        //活动已结束，验证失败
        if ($this->to_date < $currentDate) {
            return false;
        }

        //优惠券已经被使用，验证识别
        if ($coupon->state !== UserCoupon::USER_COUPON_UNUSED) {
            return false;
        }
        //验证通过
        return true;
    }

    /**
     * @return int
     */
    public function getDiscountType()
    {
        switch ($this->simple_action) {
            case self::BY_FIXED_ACTION:
                $type = 1;
                break;
            case self::BY_PERCENT_ACTION:
                $type = 2;
                break;
            case self::BUY_X_GET_Y_FREE_ACTION:
                $type = 3;
                break;
            default:
                $type = 0;//未知
        }
        return $type;
    }

    /**
     * 由于只存在一个条件，不存在条件组合的情况。
     * 取最高级优惠结果
     * @return int|mixed
     */
    public function getDiscountAmount()
    {
        $values = preg_split('#\s*[,;]\s*#', $this->discount_amount, null, PREG_SPLIT_NO_EMPTY);
        $value = 0;
        if (count($values) > 0) {
            switch ($this->simple_action) {
                case self::BY_PERCENT_ACTION:
                    $rulePercent = end($values);
                    $rulePercent = floor(($rulePercent - floor($rulePercent / 100) * 100));
                    if ($rulePercent % 10 === 0) {
                        $rulePercent = $rulePercent / 10;
                    } else {
                        $rulePercent = round($rulePercent / 10, 1);
                    }
                    $value = $rulePercent;
                    break;
                case self::BUY_X_GET_Y_FREE_ACTION:
                    $value = '赠品';
                    break;
                //固定金额
                case self::BY_FIXED_ACTION:
                default:
                    $value = end($values);
            }
        }
        return $value;
    }

    /**
     * 由于只存在一个条件，不存在条件组合的情况。
     * 当存在多级规则的时候，条件需要取最高级条件
     * @return int|mixed|string
     */
    public function getUseCondition()
    {
        $conditionsSerialized = $this->getOldAttribute('conditions_serialized');
        if (!$conditionsSerialized) {
            return '';
        }
        $data = unserialize($conditionsSerialized);
        $conditions = isset($data['conditions']) ? $data['conditions'] : '';
        if (!is_array($conditions) || count($conditions) == 0) {
            return '';
        }
        $condition = current($conditions);
        $conditionValue = $condition['value'];
        $value = 0;
        $values = preg_split('#\s*[,;]\s*#', $conditionValue, null, PREG_SPLIT_NO_EMPTY);
        if (count($values) > 0) {
            $value = end($values);
        }
        $conditionAttribute = $condition['attribute'];
        switch ($conditionAttribute) {
            case 'subtotal':
                $text = sprintf('满%s元可用', $value);
                break;
            case 'total_qty':
                $text = sprintf('满%s件可用', $value);
                break;
            default:
                $text = $value;
                break;
        }
        return $text;
    }

    public static function getCoupon($rule, $customerId, $source, $contractor_id = 0)
    {
        $dateModel = new Date();
        $date = $dateModel->date();
        $rule_id = $rule->rule_id;

        //未达上线
        //是否存在未使用的该优惠券
        $userRule = UserCoupon::find()->where(['rule_id' => $rule_id])
            ->andWhere(['customer_id' => $customerId])
            ->andWhere(['state' => UserCoupon::USER_COUPON_UNUSED])
            ->andWhere(['>=', 'salesrule_user_coupon.expiration_date', $date])
            ->count();
        if ($userRule > 0) {
            \service\resources\Exception::couponUserReceived();
        }

        //先判断所有用户领取是否达到上线
        $totalCount = UserCoupon::find()->where(['rule_id' => $rule_id])->count();
        if ($totalCount >= $rule->uses_per_coupon) {
            \service\resources\Exception::couponReceiveOut();
        }

        //在判断单个用户领取是否达到上线
        $userTotalCount = UserCoupon::find()->where(['rule_id' => $rule_id])
            ->andWhere(['customer_id' => $customerId])->count();
        if ($userTotalCount >= $rule->uses_per_customer) {
            //判断单个用户领取是否达到上线
            \service\resources\Exception::couponUserReceiveOut();
        }


        $userCoupon = new UserCoupon();
        $userCoupon->rule_id = $rule->rule_id;
        $userCoupon->customer_id = $customerId;
        $userCoupon->state = UserCoupon::USER_COUPON_UNUSED;
        $userCoupon->source = $source;
        $userCoupon->created_at = $date;
        $userCoupon->contractor_id = $contractor_id;
        //过期时间  未到规则生效日期也可以领取  还没到期的规则，领了不能用，过几天在用   有效期是以规则日期来进行计算
        if ($rule->from_date > $date) {
            $toDate = date('Y-m-d H:i:s', strtotime($rule->from_date . ' ' . $rule->apply_period . ' days'));
        } else {
            $toDate = date('Y-m-d H:i:s', strtotime($date . ' ' . $rule->apply_period . ' days'));
        }

        //超过规则失效日期，则优惠券失效时间为这个规则的失效日期 || 填0天后失效的，直接按规则的失效日期来
        if ($rule->to_date < $toDate || $rule->apply_period <= 0) {
            $userCoupon->expiration_date = $rule->to_date;
        } else {
            $userCoupon->expiration_date = $toDate;
        }

        //优惠券操作
        $usage = new Usage();
        $usage->status = Usage::COUPON_RECEIVE;
        $userCoupon->usage = $usage;
        if (!$userCoupon->save()) {
            //数据库保存错误
            throw new \Exception(Exception::DEFAULT_ERROR_CODE, json_encode($userCoupon->getErrors()));
            //return false;
        }

        return true;

    }

    /**
     * @return boolean
     */
    public function isApplied()
    {
        return $this->_applied;
    }

    /**
     * @param boolean $applied
     */
    public function setApplied($applied)
    {
        $this->_applied = $applied;
    }


}
