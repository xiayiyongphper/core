<?php
namespace service\models\sales;

use common\models\SalesRule;
use common\models\salesrule\Rule;
use common\models\salesrule\UserCoupon;
use service\message\common\Store;
use service\models\Product;
use service\models\ProductRequest;
use service\models\sales\quote\Discount;
use service\models\sales\quote\Item;
use service\models\sales\quote\total\Collector;
use service\models\VarienObject;
use service\resources\Exception;

/**
 * Class Quote
 * @package service\models\sales
 * @method setGrandTotal(float $total)
 * @method setSubtotal(float $total)
 * @method setShippingAmount(float $amount)
 * @method setDiscountAmount(float $amount)
 * @method setOriginalAmount(float $amount)
 * @method getOriginalAmount()
 * @method setItemsCount(int $count)
 * @method setItemsQty(int $qty)
 * @method setVirtualItemsQty(int $qty)
 * @method bool getTotalsCollectedFlag()
 * @method int getItemsCount()
 * @method int getItemsQty()
 * @method float getGrandTotal()
 * @method float getSubtotal()
 * @method setIsMultiStore(bool $flag)
 * @method bool getIsMultiStore()
 * @method bool getBalanceIsUse()
 * @method setBalanceUsed(bool $used)
 * @method setTotalsCollectedFlag(bool $flag)
 * @method setCurrentStore($store)
 * @method setToHowMoneyFreeShipping($amount)
 * @method setCouponDiscountAmount(float $amount)
 * @method float getCouponDiscountAmount()
 * @method setDiscountDescription(string $desc)
 * @method setCartFixedRules(array $rules)
 * @method setPromoInfo(string $txt)
 * @method float getDiscountAmount()
 * @method float getShippingAmount()
 * @method float getSpecialActDiscount()
 * @method void setSpecialActDiscount(float $discount)
 * @method int getDeliveryMethod()
 * @method setCouponId(int $id)
 * @method int getCouponId()
 * @method setCustomerId(int $id)
 * @method int getCustomerId()
 * @method setCoupon($coupon)
 * @method \common\models\salesrule\UserCoupon getCoupon()
 * @method setUnavailableReason(string $reason)
 * @method string getUnavailableReason()
 * @method setGiftDiscount(bool $gift)
 * @method bool getGiftDiscount()
 * @method setTrailCoupon(\common\models\salesrule\UserCoupon $coupon)
 * @method \common\models\salesrule\UserCoupon getTrailCoupon()
 * @method setOriginalCouponId(int $id)
 * @method integer getOriginalCouponId()
 * @method setCouponMutex(bool $flag)
 * @method bool getCouponMutex()
 * @method setPromoItemText(string $text)
 * @method string getPromoItemText()
 * @method setRuleType(integer $type)
 * @method integer getRuleType()
 * @method float setRuleApportion(float $apportion)
 * @method float getRuleApportion()
 * @method float setRuleApportionLelai(float $apportion)
 * @method float getRuleApportionLelai()
 * @method float setRuleApportionWholesaler(float $apportion)
 * @method float getRuleApportionWholesaler()
 * @method void setRuleApportionProductsActLelai(float $val)
 * @method float getRuleApportionProductsActLelai()
 * @method void setRuleApportionProductsCouponLelai(float $val)
 * @method float getRuleApportionProductsCouponLelai()
 * @method void setRuleApportionOrderActLelai(float $val)
 * @method float getRuleApportionOrderActLelai()
 * @method void setRuleApportionOrderCouponLelai(float $val)
 * @method float getRuleApportionOrderCouponLelai()
 */
class Quote extends VarienObject
{
    /**
     * @var array
     */
    protected $_items = [];

    const PROMO_TEXT = '';

    const CART_TIP_TEXT = '满9元免运费';
    /**
     *免运费门槛计算顺序，是否放在促销规则之后
     */
    const FREE_SHIPPING_AFTER_SALES_RULE = false;

    /**
     * @var Store
     */
    protected $_wholesaler;

    /**
     * Total models collector
     *
     * @var Collector
     */
    protected $_totalCollector = null;

    protected $_appliedRuleIds = [];
    protected $_appliedRules = [];
    protected $_promotions = [];
    protected $_tags = [];
    protected $_availableCoupons = [];
    protected $_unavailableCoupons = [];
    /**
     * 是否为试算模式
     * @var bool
     */
    protected $_trial = false;

    /**
     * 是否优惠券互斥
     * @var bool
     */
    protected $_couponMutex = false;

    const PROMO_RULE_GIFT = 'promo_gifts';
    const PROMO_RULE_ITEM = 'promo_item';
    const PROMO_RULE_ORDER = 'promo_order';
    const PROMO_BY_FIXED_ACTION = '【满减】';
    const PROMO_BY_PERCENT_ACTION = '【满减】';
    const PROMO_BUY_X_GET_Y_FREE_ACTION = '【满赠】';

    /**
     * Add product to quote
     * Advanced func to add product to quote - processing mode can be specified there.
     * Returns error message if product type instance can't prepare product.
     * @param Product $product
     * @param null $request
     * @return array|Item|string
     * @throws \Exception
     */
    public function addProduct(Product $product, $request = null)
    {
        if ($request === null) {
            $request = 1;
        }
        if (is_numeric($request)) {
            $request = new VarienObject(array('qty' => $request));
        }
        if (!($request instanceof VarienObject)) {
            throw new \Exception('Invalid request for adding product to quote.');
        }

        $cartCandidates = $this->_prepareProduct($request, $product);

        /**
         * Error message
         */
        if (is_string($cartCandidates)) {
            return $cartCandidates;
        }

        /**
         * If prepare process return one object
         */
        if (!is_array($cartCandidates)) {
            $cartCandidates = array($cartCandidates);
        }

        $parentItem = null;
        $items = array();
        foreach ($cartCandidates as $candidate) {
            $item = $this->_addCatalogProduct($candidate);
            $items[] = $item;
            /**
             * We specify qty after we know about parent (for stock)
             */
            $item->addQty($candidate->getNum());
        }

        return $item;
    }

    /**
     * Prepare product and its configuration to be added to some products list.
     * Perform standard preparation process and then prepare options belonging to specific product type.
     *
     * @param  ProductRequest $buyRequest
     * @param  Product $product
     * @return array|string
     */
    protected function _prepareProduct(VarienObject $buyRequest, $product)
    {
        // set quantity in cart
        $product->setNum($buyRequest->getNum());
        $product->setWholesalerId($buyRequest->getWholesalerId());
        $product->setBuyPath($buyRequest->getBuyPath());
        return array($product);
    }

    /**
     * Adding catalog product object data to quote
     *
     * @param   Product $product
     * @return  Item
     */
    protected function _addCatalogProduct(Product $product)
    {
        $newItem = false;
        $item = $this->getItemByProduct($product);
        if (!$item) {
            $item = new Item();
            /* @var $item Item */
            $item->setQuote($this);
            $newItem = true;
        }

        $item->setProduct($product);

        // Add only item that is not in quote already (there can be other new or already saved item
        if ($newItem) {
            $this->addItem($item);
        }

        return $item;
    }

    /**
     * Retrieve quote item by product id
     * @param $product
     * @return bool|Item
     */
    public function getItemByProduct($product)
    {
        foreach ($this->getItems() as $item) {
            /* @var $item Item */
            if ($item->representProduct($product)) {
                return $item;
            }
        }
        return false;
    }

    /**
     * @return array
     */
    public function getItems()
    {
        return $this->_items;
    }

    /**
     * Adding new item to quote
     *
     * @param   Item $item
     * @return  Quote
     */
    public function addItem(Item $item)
    {
        /**
         * Temporary workaround for purchase process: it is too dangerous to purchase more than one nominal item
         * or a mixture of nominal and non-nominal items, although technically possible.
         *
         * The problem is that currently it is implemented as sequential submission of nominal items and order, by one click.
         * It makes logically impossible to make the process of the purchase failsafe.
         * Proper solution is to submit items one by one with customer confirmation each time.
         */
        $item->setQuote($this);
        if (!$item->getId()) {
            $this->_items[] = $item;
        }
        return $this;
    }

    /**
     * Collect totals
     * @param $withDiscount
     * @return $this
     * @throws \Exception
     */
    public function collectTotals($withDiscount = false)
    {
        /**
         * Protect double totals collection
         */
        if ($this->getTotalsCollectedFlag()) {
            return $this;
        }

        $storeCounts = array();
        $freeShippingAmount = null;
        foreach ($this->getItems() as $index => $item) {
            /** @var $item Item */
            if ($item->isDeleted()) continue;
            $this->setItemsCount($this->getItemsCount() + 1);
            $this->setItemsQty((float)$this->getItemsQty() + $item->getQty());
            $item->calcRowTotal();
            $item->setIndex($index + 1);
            $this->setGrandTotal($this->getGrandTotal() + $item->getRowTotal());
            $this->setSubtotal($this->getSubtotal() + $item->getRowTotal());
            $this->setOriginalAmount($this->getOriginalAmount() + $item->getOriginalTotal());
            $this->setSpecialActDiscount($this->getSpecialActDiscount() + $item->getOriginalTotal() - $item->getRowTotal());
            $storeCounts[$item->getStoreId()][] = $item->getStoreId();
        }

        $this->setCurrentStore($this->_wholesaler);
        $this->setToHowMoneyFreeShipping($freeShippingAmount);

        if (!$this->getIsMultiStore()) {
            $count = array_keys($storeCounts);
            if (count($count) > 1) {
                $this->setIsMultiStore(true);
            } else {
                $this->setIsMultiStore(false);
            }
        }
        if (!self::FREE_SHIPPING_AFTER_SALES_RULE) {
            //用于计算免运费金额，所有的优惠规则可能与运费有关
            $this->processShipping();
        }
        if ($withDiscount) {
            foreach ($this->getTotalCollector()->getCollectors() as $model) {
                /** @var $model Discount */
                $model->collect($this);
            }
        }

        if (self::FREE_SHIPPING_AFTER_SALES_RULE) {
            //最后用于计算免运费金额，所有的优惠规则均与运费无关
            $this->processShipping();
        }

        $this->setData('trigger_recollect', 0);
        $this->setTotalsCollectedFlag(true);
        return $this;
    }

    /**
     * 计算运费
     * @throws \Exception
     * @return $this
     */
    protected function processShipping()
    {
        if ($this->_wholesaler) {
            //首个订单免运费
            if (false) {
//                if ($customer->getOrderCount() == 0) {
                $shippingAmount = 0;
            } else {
                //$deliveryMethod = $this->getDeliveryMethod();
                $deliveryMethod = 0;
                //$storeDeliveryMethod = $this->_wholesaler->getDelivery()->getDeliveryMethod();
                $storeDeliveryMethod = 1;
                //$getDeliveryMoney = $this->_wholesaler->getDelivery()->getDeliveryMoney();
                $getDeliveryMoney = 5;
                //$freeShippingAmount = $this->_wholesaler->getDelivery()->getFreeShipping();
                $freeShippingAmount = 0;
                //$eventFreeShippingAmount = $this->_wholesaler->getDelivery()->getEventFreeShipping();// 活动免运费
                $eventFreeShippingAmount = 0;
                switch ($deliveryMethod) {
                    case 3:
                        // 看店铺支不支持自提
                        $spuInfo = $this->_wholesaler->getStoreSelfPickUpInfo();
                        if ($spuInfo['is_support']) {
                            $shippingAmount = 0;
                            $this->setShippingAmount(0);
                        } else {
                            throw new \Exception('该店铺不支持自提', Exception::STORE_NOT_FOUND);
                        }
                        break;
                    case 2:
                        // 看店铺是自送还是乐来送
                        if ($storeDeliveryMethod == 1) {
                            // 店铺设置自送
                            $shippingAmount = 0;
                            break;
                        } else {
                            // 啥都不做，走default流程
                        }
                    default:
                        $this->setDeliveryMethod(2);

                        //Mage::log("quote:", null, "topicTest.log");
                        //Mage::log("hasEventProduct:".$this->hasEventProduct(), null, "topicTest.log");
                        //Mage::log($eventFreeShippingAmount, null, "topicTest.log");
                        //Mage::log($freeShippingAmount, null, "topicTest.log");
                        if ($this->hasEventProduct()) {
                            $freeShippingAmountStart = $eventFreeShippingAmount;
                        } else {
                            $freeShippingAmountStart = $freeShippingAmount;
                        }

                        if ($this->getGrandTotal() >= $freeShippingAmountStart) {
                            $shippingAmount = 0;
                        } else {
                            $shippingAmount = $getDeliveryMoney;
                        }
                }
            }
            $shippingTotal = $this->getShippingAmount() + $shippingAmount;
            $this->setShippingAmount($shippingTotal);
            if ($this->getFreeShipping()) {
                $this->setShippingAmount(0);
            }

            $this->setGrandTotal($this->getGrandTotal() + $this->getShippingAmount());
        }
        return $this;
    }

    /**
     * Get totals collector model
     *
     * @return Collector
     */
    public function getTotalCollector()
    {
        if ($this->_totalCollector === null) {
            $this->_totalCollector = new Collector();
        }
        return $this->_totalCollector;
    }

    /**
     * @return array
     */
    public function getStoreProductDetail()
    {
        $return = new VarienObject();
        $return->setWholesalerId($this->_wholesaler->getWholesalerId());
        $return->setWholesalerName($this->_wholesaler->getWholesalerName());
        $return->setWholesalerDeliveryTime($this->_wholesaler->getDeliveryTime());
        $return->setWholesalerDeliveryText($this->_wholesaler->getDeliveryText());
//        $return->setWholesalerDeliveryText('11月大促期间，订单配送时效可能略受影响，我们会尽快为你配送，请您耐心等待。');
        $return->setShippingAmount($this->getShippingAmount());
        //$return->setDeliveryMethodList($this->_wholesaler->getDeliveryMethod());// 配送方式, 目前是只有自提
        $items = [];
        //促销说明
        $off_money = 0;
        foreach ($this->getItems() as $item) {
            /** @var Item $item */
            $items[] = array_filter([
                'product_id' => $item->getProductId(),
                'name' => $item->getName(),
                'image' => $item->getProduct()->getImage180X180(),
                'barcode' => $item->getProduct()->getBarcode(),
                'qty' => (int)$item->getQty(),
                'price' => $item->getPrice(),
                'original_price' => $item->getOriginalPrice(),
                'row_total' => $item->getRowTotal(),
                'rebates_all' => $item->getRebatesAll(),
                'tags' => unserialize($item->getTags()),
                'type' => $item->getProductType()
            ]);
            if ($item->getRebatesAll() > 0) {
                $off_money += $item->getPrice() * $item->getNum() * ($item->getRebatesAll() / 100);
            }
        }
        if ($off_money > 0) {
            $return->setPromotions([['text' => '本单预计可返现¥' . number_format($off_money, 2, null, '')]]);
        }

        $return->setItems($items);
        return array_filter($return->toArray());
    }

    /**
     * @param Store $store
     * @return $this
     */
    public function setWholesaler(Store $store)
    {
        $this->_wholesaler = $store;
        return $this;
    }

    /**
     * @return Store $store
     */
    public function getWholesaler()
    {
        return $this->_wholesaler;
    }

    public function getWholesalerId()
    {
        return $this->_wholesaler->getWholesalerId();
    }

    public function __construct()
    {
        parent::__construct();
        $this->init();
    }

    public function init()
    {
        $this->setGrandTotal(0);
        $this->setSubtotal(0);
        $this->setOriginalAmount(0);
        $this->setShippingAmount(0);
        $this->setSpecialActDiscount(0);
        $this->setDiscountAmount(0);
        $this->setItemsCount(0);
        $this->setItemsQty(0);
        $this->setVirtualItemsQty(0);
        $this->setCouponDiscountAmount(0);
        $this->setRuleApportion(0);
        $this->setRuleApportionLelai(0);
        $this->setRuleApportionWholesaler(0);
        $this->setRuleApportionProductsActLelai(0);
        $this->setRuleApportionProductsCouponLelai(0);
        $this->setRuleApportionOrderActLelai(0);
        $this->setRuleApportionOrderCouponLelai(0);
    }

    /**
     * @param bool $asString
     * @return array|string
     */
    public function getAppliedRuleIds($asString = false)
    {
        if ($asString) {
            return implode(',', $this->_appliedRuleIds);
        }
        return $this->_appliedRuleIds;
    }

    /**
     * @param $appliedRuleIds
     * @return $this
     */
    public function setAppliedRuleIds($appliedRuleIds)
    {
        $this->_appliedRuleIds = $appliedRuleIds;
        return $this;
    }

    public function getAppliedRules(){
        return $this->_appliedRules;
    }

    public function setAppliedRules($appliedRules){
        $this->_appliedRules = $appliedRules;
        return $this;
    }

    /**
     * @param bool $asArray
     * @return array
     */
    public function getPromotions($asArray = false)
    {
        if ($asArray) {
            $data = [];
            foreach ($this->_promotions as $promotion) {
                $data = array_merge($data, $promotion);
            }
            return $data;
        }
        return $this->_promotions;
    }

    public function addPromotionGift($promo)
    {
        if (!isset($this->_promotions[Quote::PROMO_RULE_GIFT])) {
            $this->_promotions[Quote::PROMO_RULE_GIFT] = [];
        }
        $this->_promotions[Quote::PROMO_RULE_GIFT][] = $promo;
    }

    /**
     * @param $promo
     * @param Rule $rule
     */
    public function addPromotion($promo, $rule)
    {
        switch ($rule->type) {
            /** 单品优惠计算 **/
            case Rule::TYPE_ITEM:
                /** 多品优惠计算 **/
            case Rule::TYPE_GROUP:
                if (!isset($this->_promotions[Quote::PROMO_RULE_ITEM])) {
                    $this->_promotions[Quote::PROMO_RULE_ITEM] = [];
                }
                $this->_promotions[Quote::PROMO_RULE_ITEM][] = $promo;
                break;
            /** 订单级优惠计算 **/
            case Rule::TYPE_ORDER:
            default:
                if (!isset($this->_promotions[Quote::PROMO_RULE_ORDER])) {
                    $this->_promotions[Quote::PROMO_RULE_ORDER] = [];
                }
                $this->_promotions[Quote::PROMO_RULE_ORDER][] = $promo;
                break;
        }

    }

    /**
     * @return array
     */
    public function getTags()
    {
        return $this->_tags;
    }

    /**
     * @param $tags
     * @return $this
     */
    public function setTags($tags)
    {
        $this->_tags = $tags;
        return $this;
    }

    /**
     * @param \common\models\salesrule\Rule $rule
     * @param float $discountAmount
     * @return $this
     */
    public function addTag($rule, $discountAmount)
    {
        /* 优惠券类型显示优惠券及其名称 */
        if ($rule->coupon_type != Rule::COUPON_TYPE_NO_COUPON) { // 除了1没有优惠券，其他的暂时都是优惠券
            $this->_tags[] = [
                'text' => '【优惠券】' . ($rule->coupon_title ? $rule->coupon_title : $rule->name),
                'type' => $rule->coupon_type,
                'id' => $rule->rule_id
            ];
        } else {
            switch ($rule->simple_action) {
                case Rule::BY_FIXED_ACTION:
                    $this->_tags[] = ['text' => Quote::PROMO_BY_FIXED_ACTION . '已享受"' . $rule->name . '",已减' . $discountAmount . '元'];
                    break;
                case Rule::BY_PERCENT_ACTION:
                    $this->_tags[] = ['text' => Quote::PROMO_BY_PERCENT_ACTION . '已享受"' . $rule->name . '",已减' . $discountAmount . '元'];
                    break;
                case Rule::BUY_X_GET_Y_FREE_ACTION:
                    $this->_tags[] = ['text' => Quote::PROMO_BUY_X_GET_Y_FREE_ACTION . '已享受"' . $rule->name . '",已赠' . $discountAmount];
                    break;
            }
        }
        return $this;
    }

    /**
     * @return boolean
     */
    public function isTrial()
    {
        return $this->_trial;
    }

    /**
     * @param bool $trial
     * @return Quote
     */
    public function setTrial($trial)
    {
        $this->_trial = $trial;
        return $this;
    }

    /**
     * @param UserCoupon $coupon
     * @param integer $discountAmount
     * @return $this
     */
    public function addAvailableCoupons($coupon, $discountAmount)
    {
        if (isset($this->_availableCoupons[$discountAmount])) {
            //当优惠金额相同时，直接排在后面
            $this->addAvailableCoupons($coupon, $discountAmount + 1);
        } else {
            //有效期
            $validity_time = '';
            if($coupon->rule->from_date){
                $validity_time = date("m月d日 H:i",strtotime($coupon->rule->from_date));
            }
            $validity_time .= " 至 ".date("m月d日 H:i",strtotime($coupon->expiration_date))."有效";

            $this->_availableCoupons[$discountAmount] = [
                'entity_id' => $coupon->entity_id,
                'customer_id' => $coupon->customer_id,
                'state' => $coupon->state,
                'rule_id' => $coupon->rule_id,
                'expiration_date' => $coupon->expiration_date,
                'source' => $coupon->source,
                'created_at' => $coupon->created_at,
                'coupon_title' => $coupon->rule->coupon_title,
                'frontnote' => $coupon->rule->frontnote,
                'discount_type' => $coupon->rule->getDiscountType(),
                'discount' => $coupon->rule->getDiscountAmount(),
                'use_condition' => $coupon->rule->getUseCondition(),
                'validity_time' => $validity_time
            ];
        }
    }

    /**
     * @param UserCoupon $coupon
     * @param string $reason
     * @return $this
     */
    public function addUnavailableCoupons($coupon, $reason)
    {
        //有效期
        $validity_time = '';
        if($coupon->rule->from_date){
            $validity_time = date("m月d日 H:i",strtotime($coupon->rule->from_date));
        }
        $validity_time .= " 至 ".date("m月d日 H:i",strtotime($coupon->expiration_date))."有效";

        $this->_unavailableCoupons[] = [
            'entity_id' => $coupon->entity_id,
            'customer_id' => $coupon->customer_id,
            'state' => $coupon->state,
            'rule_id' => $coupon->rule_id,
            'expiration_date' => $coupon->expiration_date,
            'source' => $coupon->source,
            'created_at' => $coupon->created_at,
            'coupon_title' => $coupon->rule->coupon_title,
            'frontnote' => $coupon->rule->frontnote,
            'discount_type' => $coupon->rule->getDiscountType(),
            'discount' => $coupon->rule->getDiscountAmount(),
            'use_condition' => $coupon->rule->getUseCondition(),
            'unavailable_reason' => $reason ? $reason : Rule::UNAVAILABLE_REASON_7,
            'validity_time' => $validity_time
        ];
    }

    /**
     * @return array
     */
    public function getUnavailableCoupons()
    {
        return $this->_unavailableCoupons;
    }

    /**
     * @param array $unavailableCoupons
     */
    public function setUnavailableCoupons($unavailableCoupons)
    {
        $this->_unavailableCoupons = $unavailableCoupons;
    }

    /**
     * @return array
     */
    public function getAvailableCoupons()
    {
        krsort($this->_availableCoupons);
        return array_values($this->_availableCoupons);
    }

    /**
     * @param array $availableCoupons
     */
    public function setAvailableCoupons($availableCoupons)
    {
        $this->_availableCoupons = $availableCoupons;
    }


    /**
     * @return Quote
     */
    public function reset()
    {
        $this->_totalCollector = null;
        return $this;
    }
}
