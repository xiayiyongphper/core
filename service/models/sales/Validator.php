<?php
namespace service\models\sales;

use common\models\salesrule\Rule;
use common\models\salesrule\UserCoupon;
use framework\components\ToolsAbstract;
use service\models\sales\quote\Item;
use service\models\VarienObject;
use yii\db\Expression;
use service\components\Tools;
use common\redis\Keys;


/**
 * SalesRule Validator Model
 *
 * Allows dispatching before and after events for each controller action
 * Class Validator
 * @package service\models\sales
 */
class Validator extends VarienObject
{
    /**
     * Rule source collection
     *
     * @var array
     */
    protected $_rules;

    /**
     * @var array
     */
    protected $_couponRules = [];

    /**
     * @var Quote
     */
    protected $_quote = null;


    /**
     * Defines if rule with stop further rules is already applied
     *
     * @var bool
     */
    protected $_stopFurtherRules = false;

    protected $_debug = true;

    /**
     * Init validator
     * Init process load collection of rules for specific website,
     * customer group and coupon code
     *
     * @return  Validator
     */
    public function init()
    {
        $date = ToolsAbstract::getDate();
        $now = $date->date('Y-m-d H:i:s');
        $ruleIds = [];
        foreach ($this->getQuote()->getItems() as $item) {
            /** @var $item Item */
            if ($item->getRuleId() > 0) {
                $ruleIds[$item->getRuleId()] = $item->getRuleId();
            }
        }
        $wholesalerId = $this->getQuote()->getWholesalerId();
        $customerId = $this->getQuote()->getCustomerId();

        if (!isset($this->_rules)) {
            $query = Rule::find();
            if (count($ruleIds) > 0) {
                $query->where(
                    ['or',
                        ['and', ['rule_founder' => Rule::RULE_FOUNDER_WHOLESALER], ['store_id' => "|$wholesalerId|"], ['type' => [Rule::TYPE_ITEM, Rule::TYPE_GROUP]], ['rule_id' => $ruleIds]],//店铺单品级、多品级
                        ['and', ['rule_founder' => Rule::RULE_FOUNDER_WHOLESALER], ['store_id' => "|$wholesalerId|"], ['type' => Rule::TYPE_ORDER]],//店铺订单级
                        ['and', ['rule_founder' => Rule::RULE_FOUNDER_LELAI], ['store_id' => '||'], ['type' => [Rule::TYPE_ITEM, Rule::TYPE_GROUP]], ['rule_id' => $ruleIds]],//平台级别，店铺无关的，单品级、多品级
                        ['and', ['rule_founder' => Rule::RULE_FOUNDER_LELAI], ['store_id' => '||'], ['type' => Rule::TYPE_ORDER]],//平台级别，店铺无关的，订单级
                        ['and', ['rule_founder' => Rule::RULE_FOUNDER_LELAI], ['like', 'store_id', "|$wholesalerId|"], ['type' => [Rule::TYPE_ITEM, Rule::TYPE_GROUP]], ['rule_id' => $ruleIds]],//平台级别，店铺相关，单品级、多品级
                        ['and', ['rule_founder' => Rule::RULE_FOUNDER_LELAI], ['like', 'store_id', "|$wholesalerId|"], ['type' => Rule::TYPE_ORDER]],//平台级别，店铺相关，订单级
                    ]);
            } else {
                $query->where(
                    ['or',
                        ['and', ['rule_founder' => Rule::RULE_FOUNDER_WHOLESALER], ['like', 'store_id', "|$wholesalerId|"]],//店铺单品级、多品级、订单级
                        ['and', ['rule_founder' => Rule::RULE_FOUNDER_LELAI], ['store_id' => '||']],//平台级别，店铺无关的，单品级、多品级、订单级
                        ['and', ['rule_founder' => Rule::RULE_FOUNDER_LELAI], ['like', 'store_id', "|$wholesalerId|"]],//平台级别，店铺相关，单品级、多品级、订单级
                    ]);
            }
            $query->andWhere(['is_active' => 1])
                ->andWhere(['<=', 'from_date', $now])
                ->andWhere(['>=', 'to_date', $now])
                ->andWhere(['is_del' => 0])
                ->andWhere(['city' => $this->getQuote()->getWholesaler()->getCity()])
                ->andWhere(['coupon_type' => Rule::COUPON_TYPE_NO_COUPON])
                ->orderBy(new Expression('type ASC,coupon_type DESC'));
            $this->log(__FILE__ . ':' . __LINE__);
            $this->log($query->createCommand()->getRawSql());
            //$this->_rules = $query->all();
            $rules = $query->all();
            if ($customerId > 0) {
                /** @var Rule $rule */
                foreach ($rules as $k => $rule) {
                    $enjoyTimesKey = Keys::getEnjoyTimesKey($customerId, $rule->rule_id);
                    $enjoyDailyTimesKey = Keys::getEnjoyDailyTimesKey($customerId, $rule->rule_id);
                    //在Redis中取得数据
                    $enjoyTimes = Tools::getRedis()->get($enjoyTimesKey);
                    $enjoyDailyTimes = Tools::getRedis()->get($enjoyDailyTimesKey);
                    $this->log($enjoyDailyTimes . '####' . $enjoyDailyTimesKey);
                    $this->log($enjoyTimes . '##' . $enjoyDailyTimes);
                    $this->log("type of enjoy_times=====" . gettype($enjoyTimes));
                    $this->log("rule_uses_limit========" . $rule->rule_uses_limit);
                    // 超过用户使用总次数则过滤掉该优惠券
                    if ($rule->rule_uses_limit > 0 && $enjoyTimes >= $rule->rule_uses_limit) {
                        unset($rules[$k]);
                    }
                    // 超过每日使用次数则过滤掉该优惠券
                    if ($rule->rule_uses_daily_limit > 0 && $enjoyDailyTimes >= $rule->rule_uses_daily_limit) {
                        unset($rules[$k]);
                    }
                }
            }

            $this->_rules = $rules;
            //Tools::log('quot_rules=====','hl.log');
            //Tools::log($this->_getRules(),'hl.log');

        }
        return $this;
    }

    /**
     * Calculate quote totals for each rule and save results
     *
     * @return $this
     */
    public function initTotals()
    {
        Tools::log('quot_rules_count=====' . count($this->_getRules()), 'hl.log');
        //Tools::log($this->_getRules(),'hl.log');
        //这里的规则全是无优惠券的规则
        foreach ($this->_getRules() as $rule) {
            /** @var Rule $rule */
            $this->processRule($rule);
        }
        $this->_initCouponTotals();
        $this->log($this->getQuote()->getAppliedRuleIds());
        return $this;
    }

    /**
     * 计算优惠券的规则
     * @return $this
     */
    private function _initCouponTotals()
    {
        $this->log(__METHOD__);
        $quote = $this->getQuote();
        $this->log('customer_coupon_id:' . $quote->getOriginalCouponId());
        $date = ToolsAbstract::getDate();
        $currentDate = $date->date('Y-m-d H:i:s');
        //属于这个用户的未过期的优惠券
        $coupons = UserCoupon::find()
            ->joinWith('rule')
            ->where(['salesrule_user_coupon.state' => UserCoupon::USER_COUPON_UNUSED,])
            ->andWhere(['>=', 'salesrule_user_coupon.expiration_date', $currentDate])
            ->andWhere(['salesrule_user_coupon.customer_id' => $quote->getCustomerId()])
            ->groupBy('salesrule_user_coupon.rule_id')
            ->limit(UserCoupon::MAX_COUPON_LIMIT)
            ->orderBy(new Expression('expiration_date ASC'))
            ->all();
        $selectedCoupon = null;
        $giftCoupon = null;
        $couponDiscountAmount = 0;
        $finalRule = null;
        $_hasGift = false;
        /** @var UserCoupon $coupon */
        foreach ($coupons as $coupon) {
            if (!$coupon || !$coupon->rule || !$coupon->rule->rule_id) {
                //优惠券查询不到对应的活动，跳过
                continue;
            }
            /** @var Rule $rule */
            $rule = $coupon->rule;
            $quote->setTrailCoupon($coupon);
            $quote->setTrial(true);
            $quote->setCouponId($coupon->entity_id);
            if ($quote->getOriginalCouponId() > 0 && $quote->getOriginalCouponId() == $coupon->entity_id) {
                $finalRule = $coupon->rule;
            }
            $this->processRule($rule);
            $trailCoupon = $quote->getTrailCoupon();
            $this->log(__FILE__ . ':' . __LINE__ . ',trail_rule_id:' . $rule->rule_id . ',discountAmount:' . $trailCoupon->getDiscountAmount());
            if ($trailCoupon->getDiscountAmount() > 0) {
                //可使用列表
                if ($trailCoupon->getDiscountAmount() > $couponDiscountAmount) {
                    $selectedCoupon = $trailCoupon;
                    $couponDiscountAmount = $trailCoupon->getDiscountAmount();
                }
                $this->log(__FILE__ . ':' . __LINE__ . ',rule_id:' . $rule->rule_id);
                $quote->addAvailableCoupons($trailCoupon, ceil($couponDiscountAmount));
            } elseif ($trailCoupon->getGiftDiscount() === true) {
                //有赠品
                if ($_hasGift === false) {
                    $_hasGift = true;
                    $giftCoupon = $trailCoupon;
                }
                $this->log(__FILE__ . ':' . __LINE__ . ',rule_id:' . $rule->rule_id);
                $quote->addAvailableCoupons($trailCoupon, Rule::MAX_DISCOUNT_AMOUNT);
            } else {
                //优惠券不可使用,有原因的
                $this->log('不可使用原因：' . $trailCoupon->getReason());
                $this->log(__FILE__ . ':' . __LINE__ . ',rule_id:' . $rule->rule_id);
                $quote->addUnavailableCoupons($trailCoupon, $trailCoupon->getReason());
            }
        }
        $quote->unsetData('trail_coupon');
        $quote->setCouponId($quote->getOriginalCouponId());
        $quote->setTrial(false);//试算结束，进入最终优惠券计算逻辑

        if ($selectedCoupon) {
            $this->log('$_selectedCouponId:' . $selectedCoupon->entity_id);
        }

        if (!$quote->getOriginalCouponId() && $selectedCoupon) {
            $quote->setCouponId($selectedCoupon->entity_id);//自动选中该优惠券
            $finalRule = $selectedCoupon->rule;
        }

        if (!$quote->getOriginalCouponId() && !$selectedCoupon && $giftCoupon) {
            $quote->setCouponId($giftCoupon->entity_id);//自动选中该优惠券
            $finalRule = $giftCoupon->rule;
        }

        //自动计算完毕，使用优惠券计算
        if ($quote->getCouponId() > 0) {
            $this->processRule($finalRule);
        }

        return $this;
    }

    /**
     * @param Rule $rule
     * @return bool
     */
    protected function processRule($rule)
    {
        $quote = $this->getQuote();
        $passed = true;
        $storeId = "|{$quote->getWholesalerId()}|";
        $this->log(__LINE__);
        $this->log('quote store id' . $storeId);
        $this->log('rule_id:' . $rule->rule_id);
        switch ($rule->rule_founder) {
            case Rule::RULE_FOUNDER_WHOLESALER:
                $this->log(__LINE__);
                $this->log($rule->store_id);
                if ($rule->store_id != $storeId) {
                    if ($quote->isTrial() === true) {
                        $quote->getTrailCoupon()->setReason(Rule::UNAVAILABLE_REASON_4);
                    }
                    $passed = false;
                }
                break;
            case Rule::RULE_FOUNDER_LELAI:
                $this->log(__LINE__);
                $this->log($rule->store_id);
                if ($rule->store_id != '||' && strpos($rule->store_id, $storeId) === false) {
                    if ($quote->isTrial() === true) {
                        $quote->getTrailCoupon()->setReason(Rule::UNAVAILABLE_REASON_4);
                    }
                    $passed = false;
                }
                break;
        }

        //优惠券互斥
        if ($this->getQuote()->getCouponMutex() === Rule::COUPON_MUTEX_YES) {
            if ($quote->isTrial() === true) {
                $quote->getTrailCoupon()->setReason(Rule::UNAVAILABLE_REASON_5);
                $passed = false;
            }
        }

        if ($passed === false) {
            return false;
        }

        switch ($rule->type) {
            /** 单品优惠计算 **/
            case Rule::TYPE_ITEM:
                /** @var $item Item */
                $this->log(__FILE__ . ':' . __LINE__ . ',rule_id:' . $rule->rule_id);
                foreach ($this->getQuote()->getItems() as $item) {
                    if ($item->getRuleId() == $rule->rule_id && !$item->getExcluded()) {
                        $this->processItem($rule, $item);
                    }
                }
                break;
            /** 多品优惠计算 **/
            case Rule::TYPE_GROUP:
                /** @var $item Item */
                $this->log(__FILE__ . ':' . __LINE__ . ',rule_id:' . $rule->rule_id);
                $group = [];
                foreach ($this->getQuote()->getItems() as $item) {
                    if ($item->getRuleId() == $rule->rule_id && !$item->getExcluded()) {
                        $group[] = $item;
                    }
                }
                if (count($group) > 0) {
                    $this->processGroup($rule, $group);
                }
                break;
            /** 订单级别优惠计算 **/
            case Rule::TYPE_ORDER:
                $this->log(__FILE__ . ':' . __LINE__ . ',rule_id:' . $rule->rule_id);
                $this->processOrder($rule);
                break;
            /** 无优惠 **/
            default:
                //to do nothing
        }
        return true;
    }

    /**
     * @param Rule $rule
     * @param Item $item
     * @return $this
     */
    protected function processItem($rule, $item)
    {
        $ruleQuote = $this->_newRuleQuote();
        $ruleQuote->addItem($item);
        $ruleQuote->setItemsCount(1);
        $ruleQuote->setItemsQty($item->getQty());
        $item->calcRowTotal();
        $ruleQuote->setGrandTotal($item->getRowTotal());
        $ruleQuote->setSubtotal($item->getRowTotal());
        if ($item->getIndex() > 0) {
            $ruleQuote->setPromoItemText('[序号' . $item->getIndex() . ']' . $rule->name);
        }
        $this->log(__FILE__ . ':' . __LINE__);
        $this->log('ruleId:' . $rule->rule_id);
        $this->log($ruleQuote->getData());
        $this->processRuleQuote($rule, $ruleQuote);
        return $this;
    }

    /**
     * @param Rule $rule
     * @param array $group
     * @return $this
     */
    protected function processGroup($rule, $group)
    {
        $ruleQuote = $this->_newRuleQuote();
        $indexes = [];
        foreach ($group as $item) {
            /** @var Item $item */
            if ($item->getExcluded()) {
                continue;
            }
            $ruleQuote->addItem($item);
            $ruleQuote->setItemsCount($ruleQuote->getItemsCount() + 1);
            $ruleQuote->setItemsQty((float)$ruleQuote->getItemsQty() + $item->getQty());
            $item->calcRowTotal();
            $ruleQuote->setGrandTotal($ruleQuote->getGrandTotal() + $item->getRowTotal());
            $ruleQuote->setSubtotal($ruleQuote->getSubtotal() + $item->getRowTotal());
            $indexes[] = $item->getIndex();
        }
        if (count($indexes) > 0) {
            $ruleQuote->setPromoItemText('[序号' . implode(',', $indexes) . ']' . $rule->name);
        }
        $this->log(__FILE__ . ':' . __LINE__);
        $this->log('ruleId:' . $rule->rule_id);
        $this->log($ruleQuote->getData());
        $this->processRuleQuote($rule, $ruleQuote);
        return $this;
    }

    /**
     * @param Rule $rule
     * @return $this
     */
    protected function processOrder($rule)
    {
        $quote = $this->getQuote();
        $ruleQuote = $this->_newRuleQuote();
        $indexes = [];
        //$this->log('quote_getItems====');
        //$this->log($quote->getItems());
        foreach ($quote->getItems() as $item) {
            /** @var Item $item */
            if ($item->getExcluded()) {
                $this->log('getExcluded===' . json_encode($item->getExcluded()));
                continue;
            }
            if ($rule->subsidies_lelai_included === Rule::SUBSIDIES_LELAI_INCLUDED_NO && $item->getProduct()->isSpecialPrice()) {
                $this->log('subsidies_lelai_included===' . $rule->subsidies_lelai_included);
                $this->log('isSpecialPrice===' . $item->getProduct()->isSpecialPrice());
                continue;
            }
            $ruleQuote->addItem($item);
            $ruleQuote->setItemsCount($ruleQuote->getItemsCount() + 1);
            $ruleQuote->setItemsQty((float)$ruleQuote->getItemsQty() + $item->getQty());
            $item->calcRowTotal();
            $ruleQuote->setGrandTotal($ruleQuote->getGrandTotal() + $item->getRowTotal());
            $ruleQuote->setSubtotal($ruleQuote->getSubtotal() + $item->getRowTotal());
            $indexes[] = $item->getIndex();
        }
        if (count($indexes) > 0) {
            $ruleQuote->setPromoItemText('[序号' . implode(',', $indexes) . ']' . $rule->name);
        }
        $this->log(__FILE__ . ':' . __LINE__ . ',rule_id:' . $rule->rule_id);
        $this->log('ruleId:' . $rule->rule_id);
        //$this->log($ruleQuote->getData());
        $this->processRuleQuote($rule, $ruleQuote);
    }

    /**
     * @param Rule $rule
     * @param Quote $ruleQuote
     * @return $this
     */
    protected function processRuleQuote($rule, $ruleQuote)
    {
        $quote = $this->getQuote();
        $ruleId = $rule->rule_id;
        $ruleQuote->setRuleType($rule->type);
        $ruleValidResult = $this->_canProcessRule($rule, $ruleQuote);
        $this->log('items_count====' . $ruleQuote->getItemsCount());
        $this->log('is_active====' . $rule->is_active);
        $this->log('ruleValidResult====' . json_encode($ruleValidResult));
        if ($ruleQuote->getItemsCount() > 0 && $rule->is_active && $ruleValidResult !== false) {
            if ($quote->getIsMultiStore()) {
                $quote->setPromoInfo(Quote::PROMO_TEXT);
                return $this;
            }
            $discountAmount = 0;
            switch ($rule->simple_action) {
                case Rule::BY_FIXED_ACTION:
                    // 准备分级减额
                    $discountAmount = $rule->getMultiDiscountAmount(intval($ruleValidResult));
                    $this->log(__FILE__ . ':' . __LINE__ . ',rule_id:' . $rule->rule_id);
                    $this->log('discountAmount===' . $discountAmount);

                    if ($ruleQuote->getGrandTotal() < $discountAmount) {
                        $discountAmount = $ruleQuote->getGrandTotal();
                    }

                    $discountAmount = $this->formatPrice($discountAmount);
                    $this->log(__FILE__ . ':' . __LINE__ . ',rule_id:' . $rule->rule_id . ',discountAmount:' . $discountAmount);
                    if (Quote::FREE_SHIPPING_AFTER_SALES_RULE) {
                        //运费尚未参与计算
                        if ($quote->getGrandTotal() > $discountAmount) {
                            if ($quote->isTrial() === true) {
                                $quote->getTrailCoupon()->setDiscountAmount($discountAmount);
                            } else {
                                $quote->setGrandTotal($quote->getGrandTotal() - $discountAmount);
                                $this->setDiscountAmount($quote, $discountAmount, $rule->coupon_type);
                            }
                        } else {
                            $discountAmount = $quote->getGrandTotal();
                            if ($quote->isTrial() === true) {
                                $quote->getTrailCoupon()->setDiscountAmount($discountAmount);
                            } else {
                                $quote->setGrandTotal(0);
                                $this->setDiscountAmount($quote, $discountAmount, $rule->coupon_type);
                            }
                        }
                    } else {
                        //运费已计算
                        if ($rule->apply_to_shipping) {
                            if ($quote->getGrandTotal() > $discountAmount) {
                                if ($quote->isTrial() === true) {
                                    $quote->getTrailCoupon()->setDiscountAmount($discountAmount);
                                } else {
                                    $quote->setGrandTotal($quote->getGrandTotal() - $discountAmount);
                                    $this->setDiscountAmount($quote, $discountAmount, $rule->coupon_type);
                                }
                            } else {
                                $discountAmount = $quote->getGrandTotal();
                                if ($quote->isTrial() === true) {
                                    $quote->getTrailCoupon()->setDiscountAmount($discountAmount);
                                } else {
                                    $quote->setGrandTotal(0);
                                    $this->setDiscountAmount($quote, $discountAmount, $rule->coupon_type);
                                }
                            }
                        } else {
                            if (($quote->getGrandTotal() - $quote->getShippingAmount()) > $discountAmount) {
                                if ($quote->isTrial() === true) {
                                    $quote->getTrailCoupon()->setDiscountAmount($discountAmount);
                                } else {
                                    $quote->setGrandTotal($quote->getGrandTotal() - $discountAmount);
                                    $this->setDiscountAmount($quote, $discountAmount, $rule->coupon_type);
                                }
                            } else {
                                $discountAmount = $quote->getGrandTotal() - $quote->getShippingAmount();
                                if ($quote->isTrial() === true) {
                                    $quote->getTrailCoupon()->setDiscountAmount($discountAmount);
                                } else {
                                    $quote->setGrandTotal($quote->getShippingAmount());
                                    $this->setDiscountAmount($quote, $discountAmount, $rule->coupon_type);
                                }
                            }
                        }
                    }
                    if ($quote->isTrial() !== true) {
                        if ($ruleQuote->getPromoItemText()) {
                            $quote->addPromotion($ruleQuote->getPromoItemText(), $rule);
                        }
                        $quote->addTag($rule, $discountAmount);
                    }
                    break;
                case Rule::BY_PERCENT_ACTION:
                    $discountPercent = $rule->getMultiDiscountAmount(intval($ruleValidResult));
                    $this->log('discountAmount===' . $discountAmount);
                    $this->log(__FILE__ . ':' . __LINE__ . ',rule_id:' . $rule->rule_id);
                    $this->log('percent:' . $discountPercent);

                    // 计算折扣百分比
                    $rulePercent = min(100, $discountPercent);// 原为$rule->getDiscountAmount()，现改为按级数来的
                    if ($rulePercent >= 100 || $rulePercent <= 0) {
                        return $this;
                    }
                    $_rulePct = (100 - $rulePercent) / 100;
                    $this->log('rulePct:' . $_rulePct);

                    // 根据打折基数计算优惠额，打折基数为$smallQuote->getGrandTotal()
                    if (Quote::FREE_SHIPPING_AFTER_SALES_RULE) {
                        $discountAmount = $ruleQuote->getGrandTotal() * $_rulePct;
                    } else {
                        if ($rule->apply_to_shipping) {
                            $discountAmount = $ruleQuote->getGrandTotal() * $_rulePct;
                        } else {
                            $discountAmount = ($ruleQuote->getGrandTotal() - $quote->getShippingAmount()) * $_rulePct;
                        }
                    }

                    // 最高减额
                    if ($rule->discount_qty > 0 && $discountAmount > $rule->discount_qty) {
                        $discountAmount = $rule->discount_qty;
                    }
                    $discountAmount = $this->formatPrice($discountAmount);
                    $this->log(__FILE__ . ':' . __LINE__ . ',rule_id:' . $rule->rule_id . ',discountAmount:' . $discountAmount);
                    if ($quote->isTrial() === true) {
                        $quote->getTrailCoupon()->setDiscountAmount($discountAmount);
                    } else {
                        $quote->setGrandTotal($quote->getGrandTotal() - $discountAmount);
                        $this->setDiscountAmount($quote, $discountAmount, $rule->coupon_type);
                        if ($ruleQuote->getPromoItemText()) {
                            $quote->addPromotion($ruleQuote->getPromoItemText(), $rule);
                        }
                        $quote->addTag($rule, $discountAmount);
                    }
                    break;
                case Rule::BUY_X_GET_Y_FREE_ACTION:
                    $discountAmount = $rule->getMultiDiscountAmount(intval($ruleValidResult));
                    $this->log(__FILE__ . ':' . __LINE__ . ',rule_id:' . $rule->rule_id . ',discountAmount:' . $discountAmount);
                    if ($quote->isTrial() === true) {
                        $quote->getTrailCoupon()->setGiftDiscount(true);
                    } else {
                        $quote->addPromotionGift($discountAmount);
                        $quote->addTag($rule, $discountAmount);
                        $quote->setGiftDiscount(true);
                    }
                    // 赠品的减额，按乐来和供应商分摊之和算
                    $discountAmount = $rule->getMultiApportionLelai(intval($ruleValidResult)) + $rule->getMultiApportionWholesaler(intval($ruleValidResult));
                    break;
            }

            //非试算模式，相关数据处理
            $this->log('is_trial=====' . json_encode($quote->isTrial()));
            if ($quote->isTrial() !== true) {
                // 计算每行商品在优惠上的分摊
                if (is_numeric($discountAmount) && $discountAmount > 0) {
                    // 已经被计算的分摊额(引入这个是为了避免最后小数点的问题)
                    $apportion_left = $discountAmount;
                    // 取得分级的乐来供应商分摊设置
                    $apportion_lelai = $rule->getMultiApportionLelai(intval($ruleValidResult));
                    $apportion_wholesaler = $rule->getMultiApportionWholesaler(intval($ruleValidResult));

                    // 大quote上增加这个优惠的总分摊
                    $quote->setRuleApportion($quote->getRuleApportion() + $discountAmount);

                    /** @var Item $_item */
                    $i = 0;
                    $item_count = count($ruleQuote->getItems());
                    foreach ($ruleQuote->getItems() as $_item) {
                        // 本行商品总共分摊额为
                        $apportion_row = $this->formatPrice($discountAmount * $_item->getRowTotal() / $ruleQuote->getGrandTotal());
                        // 循环到最后一件商品时，如果有零头，最后一件item全部分摊掉，保证所有分摊额之和为$discountAmount，避免小数点问题
                        if ($i == $item_count - 1 && $apportion_left != $apportion_row) {
                            $apportion_row = $apportion_left;
                        }
                        $_item->setRuleApportion($_item->getRuleApportion() + $apportion_row);// 要累加，有可能有多个优惠
                        $apportion_left -= $apportion_row;

                        // 计算乐来和供应商分摊的部分
                        // 当前行的当前优惠分摊总额
                        $rule_apportion = $apportion_row;
                        if ($apportion_lelai + $apportion_wholesaler != 0) {
                            // 计算乐来和供应商分摊的部分
                            $rule_apportion_lelai = $this->formatPrice($rule_apportion * $apportion_lelai / ($apportion_lelai + $apportion_wholesaler));
                        } else {
                            // 设置分摊有误的情况下，全部给供应商？
                            $rule_apportion_lelai = 0;
                        }
                        $rule_apportion_wholesaler = $rule_apportion - $rule_apportion_lelai;// 用减法避免小数点问题
                        $_item->setRuleApportionLelai($_item->getRuleApportionLelai() + $rule_apportion_lelai);// 要累加，有可能有多个优惠
                        $_item->setRuleApportionWholesaler($_item->getRuleApportionWholesaler() + $rule_apportion_wholesaler);// 要累加，有可能有多个优惠

                        // 大quote上增加这个优惠的乐来和供应商分摊
                        $quote->setRuleApportionLelai($quote->getRuleApportionLelai() + $rule_apportion_lelai);
                        $quote->setRuleApportionWholesaler($quote->getRuleApportionWholesaler() + $rule_apportion_wholesaler);

                        $this->setRuleApportions($quote, $_item, $rule, $rule_apportion_lelai, $rule_apportion_wholesaler);

                        $i++;
                    }

                }

                //优惠规则ID记录
                $appliedRuleIds = $quote->getAppliedRuleIds();
                $appliedRuleIds[$ruleId] = $ruleId;
                $quote->setAppliedRuleIds($appliedRuleIds);
                $appliedRules = $quote->getAppliedRules();
                $appliedRules [] = $rule;
                $this->log('setAppliedRules:' . $rule->rule_id);
                $quote->setAppliedRules($appliedRules);

                if ($rule->stop_rules_processing) {
                    $this->setRuleQuoteExcluded($ruleQuote);
                }

                //规则与优惠券互斥
                if ($rule->coupon_mutex === Rule::COUPON_MUTEX_YES) {
                    $quote->setCouponMutex($rule->coupon_mutex);
                }
            }
        }
    }


    /**
     * @param Quote $quote
     * @param Item $item
     * @param Rule $rule
     * @param float $ruleApportionLelai
     * @param float $ruleApportionWholesaler
     */
    protected function setRuleApportions(Quote $quote, Item $item, $rule, $ruleApportionLelai, $ruleApportionWholesaler)
    {

        if ($rule->type == Rule::TYPE_GROUP) {  // 多品级
            if ($rule->coupon_type == Rule::COUPON_TYPE_NO_COUPON) { // 优惠活动
                $item->setRuleApportionProductsActLelai($item->getRuleApportionProductsActLelai() + $ruleApportionLelai);
                $quote->setRuleApportionProductsActLelai($quote->getRuleApportionProductsActLelai() + $ruleApportionLelai);
            } else {    // 优惠券
                $item->setRuleApportionProductsCouponLelai($item->getRuleApportionProductsCouponLelai() + $ruleApportionLelai);
                $quote->setRuleApportionProductsCouponLelai($quote->getRuleApportionProductsCouponLelai() + $ruleApportionLelai);
            }
        } elseif ($rule->type == Rule::TYPE_ORDER) {    // 订单级
            if ($rule->coupon_type == Rule::COUPON_TYPE_NO_COUPON) { // 优惠活动
                $item->setRuleApportionOrderActLelai($item->getRuleApportionOrderActLelai() + $ruleApportionLelai);
                $quote->setRuleApportionOrderActLelai($quote->getRuleApportionOrderActLelai() + $ruleApportionLelai);
            } else {    // 优惠券
                $item->setRuleApportionOrderCouponLelai($item->getRuleApportionOrderCouponLelai() + $ruleApportionLelai);
                $quote->setRuleApportionOrderCouponLelai($quote->getRuleApportionOrderCouponLelai() + $ruleApportionLelai);
            }
        }
    }

    /**
     * @param Quote $quote
     * @param $discountAmount
     * @param $couponType
     * @return $this
     */
    protected function setDiscountAmount($quote, $discountAmount, $couponType)
    {
        if ($couponType == Rule::COUPON_TYPE_NO_COUPON) {
            $quote->setDiscountAmount($quote->getDiscountAmount() + $discountAmount);
        } else {
            $quote->setCouponDiscountAmount($quote->getCouponDiscountAmount() + $discountAmount);
        }
        return $this;
    }

    /**
     * @param Quote $ruleQuote
     */
    protected function setRuleQuoteExcluded($ruleQuote)
    {
        /** @var Item $_item */
        foreach ($ruleQuote->getItems() as $_item) {
            $_item->setExcluded(true);
        }
    }

    /**
     * 规则检测，检测该规则是否可以应用
     *
     * @param   Rule $rule
     * @param   Quote $ruleQuote
     * @return  bool
     */
    protected function _canProcessRule($rule, $ruleQuote)
    {
        $quote = $this->getQuote();
        $this->log(__METHOD__);
        $coupon = null;
        if ($rule->coupon_type != Rule::COUPON_TYPE_NO_COUPON) {
            $this->log(__FILE__ . ':' . __LINE__ . ',rule_id:' . $rule->rule_id);
            $couponId = $quote->getCouponId();
            $this->log('rule_id:' . $rule->rule_id);
            $this->log('coupon_id:' . $couponId);
            //客户端选择不使用优惠券，验证失败
            if ($couponId <= 0) {
                return false;
            }
            /** @var UserCoupon $coupon */
            $coupon = UserCoupon::findOne(['entity_id' => $couponId]);
            //没有查询到优惠券信息，优惠券无效，验证失败
            if (!$coupon) {
                return false;
            }
            //非法优惠券，验证失败
            if ($coupon->customer_id != $quote->getCustomerId()) {
                return false;
            }
            if (!$rule->validateCoupon($coupon)) {
                return false;
            }
        }
        // 这里可以得到级数。
        $validResult = $rule->validateConditions($ruleQuote);
        if ($quote->isTrial() === true && $validResult === false) {
            $quote->getTrailCoupon()->setReason($ruleQuote->getUnavailableReason());
        }
        //验证通过,优惠券使用
        if ($quote->isTrial() !== true && $validResult !== false && $coupon) {
            $quote->setCoupon($coupon);
        }
        $this->log(__FILE__ . ':' . __LINE__ . ',rule_id:' . $rule->rule_id);
        $this->log('rule_id:' . $rule->rule_id . ',validResult:' . $validResult);
        //$this->log( 'validResult_type:');
        $this->log('validResult====' . json_encode($validResult));

        /**
         * passed all validations, remember to be valid
         */
        return $validResult;
    }

    /**
     * @return Quote
     */
    public function getQuote()
    {
        return $this->_quote;
    }

    /**
     * @param $quote
     * @return Validator
     */
    public function setQuote($quote)
    {
        $this->_quote = $quote;
        //保留用户的优惠券id
        $this->_quote->setOriginalCouponId($this->_quote->getCouponId());
        return $this;
    }

    /**
     * Get rules collection for current object state
     *
     * @return array
     */
    protected function _getRules()
    {
        return $this->_rules;
    }

    /**
     * @return Quote
     */
    protected function _newRuleQuote()
    {
        $quote = $this->getQuote();
        $ruleQuote = new Quote();
        $ruleQuote->setTrial($quote->isTrial());
        return $ruleQuote;
    }

    /**
     * @return Quote
     */
    protected function getTrailQuote()
    {
        $quote = $this->getQuote();
        $cloneQuote = new Quote();
        foreach ($quote->getItems() as $item) {
            $cloneItem = clone $item;
            $cloneQuote->addItem($cloneItem);
        }
        $cloneQuote->setIsMultiStore($quote->getIsMultiStore());
        $cloneQuote->setWholesaler($quote->getWholesaler());
        $cloneQuote->setCustomerId($quote->getCustomerId());
        $cloneQuote->reset();
        $cloneQuote->setTrial(true);
        return $cloneQuote;
    }

    /**
     * @param $price
     * @return float
     */
    public function formatPrice($price)
    {
        return round($price, 2);
    }

    /**
     * @return array
     */
    public function getCouponRules()
    {
        return $this->_couponRules;
    }

    /**
     * @param array $couponRules
     */
    public function setCouponRules($couponRules)
    {
        $this->_couponRules = $couponRules;
    }

    public function log($data)
    {
        if ($this->_debug === true) {
            ToolsAbstract::log($data, 'validator.log');
        }
    }
}
