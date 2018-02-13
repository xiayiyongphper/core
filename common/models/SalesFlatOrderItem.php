<?php

namespace common\models;

use framework\components\Date;
use framework\db\ActiveRecord;
use service\helpers\ProductHelper;
use service\models\Product;
use service\models\sales\Quote;
use service\models\sales\quote\Convert;
use service\models\sales\quote\Item;
use service\models\VarienObject;
use Yii;

/**
 * This is the model class for table "sales_flat_order_item".
 *
 * @property string $item_id
 * @property string $order_id
 * @property integer $wholesaler_id
 * @property string $created_at
 * @property string $updated_at
 * @property string $product_id
 * @property string $product_type
 * @property string $product_options
 * @property string $tags
 * @property string $weight
 * @property string $sku
 * @property string $name
 * @property string $brand
 * @property string $qty
 * @property string $price
 * @property string $original_price
 * @property float $row_total
 * @property string $image
 * @property string $barcode
 * @property string $specification
 * @property string $first_category_id
 * @property string $third_category_id
 * @property string $second_category_id
 * @property string $rebates
 * @property integer $is_calculate_lelai_rebates
 * @property float $rebates_calculate
 * @property float $commission
 * @property float $commission_percent
 * @property integer $receipt
 * @property float $subsidies_wholesaler
 * @property float $subsidies_lelai
 * @property float $rebates_lelai
 * @property float $rebates_calculate_lelai
 * @property string $origin
 * @property string $promotion_text
 * @property float $rule_apportion
 * @property float $rule_apportion_lelai
 * @property float $rule_apportion_wholesaler
 * @property string $buy_path 购买路径
 * @property integer $activity_id 活动id
 * @property string $additional_info
 * @property int $parent_id
 * @property float $rule_apportion_order_act_lelai 整行商品分摊的订单级优惠活动的优惠金额部分，乐来部分
 * @property float $rule_apportion_products_act_lelai 整行商品分摊的多品级优惠活动的优惠金额部分，乐来部分
 * @property float $rule_apportion_order_coupon_lelai 整行商品分摊的订单级优惠券的优惠金额部分，乐来部分
 * @property float $rule_apportion_products_coupon_lelai 整行商品分摊的多品级优惠券的优惠金额部分，乐来部分
 * @property string $sales_type 商品销售类型。自营/普通等。字符串，多个|隔开
 */
class SalesFlatOrderItem extends ActiveRecord
{
    const RECEIPT_YES = 1;
    const RECEIPT_NO = 0;

    /** @var int 普通商品，可以跟其他类型组合 */
    const PRODUCT_TYPE_SIMPLE = 0x2000;
    /** @var int 秒杀商品，可以跟其他类型组合 */
    const PRODUCT_TYPE_SECKILL = 0x4000;
    /** @var int 特殊商品，可以跟其他类型组合 */
    const PRODUCT_TYPE_SPECIAL = 0x6000;
    /** @var int 套餐商品，可以跟其他类型组合 */
    const PRODUCT_TYPE_GROUP = 0x4;
    /** @var int 套餐子商品，可以跟其他类型组合 */
    const PRODUCT_TYPE_GROUP_SUB = 0x8;

    /** @var \service\message\common\Product 子商品 */
    public $relativeProducts;

    public $lsin;

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'sales_flat_order_item';
    }

    /**
     * @return \yii\db\Connection the database connection used by this AR class.
     */
    public static function getDb()
    {
        return Yii::$app->get('mainDb');
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['order_id', 'wholesaler_id', 'product_id'], 'integer']
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'item_id' => 'Item Id',
            'order_id' => 'Order Id',
            'wholesaler_id' => 'Store Id',
            'created_at' => 'Created At',
            'updated_at' => 'Updated At',
            'product_id' => 'Product Id',
            'product_type' => 'Product Type',
            'product_options' => 'Product Options',
            'weight' => 'Weight',
            'sku' => 'Sku',
            'name' => 'Name',
            'description' => 'Description',
            'is_qty_decimal' => 'Is Qty Decimal',
            'qty_ordered' => 'Qty Ordered',
            'price' => 'Price',
            'original_price' => 'Original Price',
            'row_total' => 'Row Total',
        ];
    }

    public static function getGeneralSelectColumns()
    {
        return [
            'item_id',
            'product_id',
            'name',
            'barcode',
            'specification',
            'image',
            'price',
            'original_price',
            'qty',
            'row_total',
        ];
    }

    public function beforeSave($insert)
    {
        $date = new Date();
        if (!$this->created_at) {
            $this->created_at = $date->gmtDate();
        }

        if (is_null($this->commission_percent)) {
            $this->commission_percent = 0;
        }

        if (is_null($this->commission)) {
            $this->commission = 0;
        }

        /** 新老数据转换！！！小于10代表是2.9之前的值，需要转换为新的值 @since 3.0 */
        if ((int)$this->product_type < 10) {
            if (ProductHelper::isSpecialProduct($this->product_id)) {
                if (ProductHelper::isOldSeckillProductType($this->product_type)) {
                    $this->product_type = ProductHelper::TYPE_SECKILL;
                } else {
                    $this->product_type = ProductHelper::TYPE_SPECIAL;
                }
            } else {
                if (ProductHelper::isOldGroupProductType($this->product_type)) {
                    $this->product_type = ProductHelper::TYPE_GROUP | ProductHelper::TYPE_SIMPLE;
                } elseif (ProductHelper::isOldSimpleProductType($this->product_type)) {
                    $this->product_type = ProductHelper::TYPE_SIMPLE;
                } elseif (empty($this->product_type)) {    // 空的默认为普通商品
                    $this->product_type = ProductHelper::TYPE_SIMPLE;
                }
            }
        }

        $this->updated_at = $date->gmtDate();
        return parent::beforeSave($insert); // TODO: Change the autogenerated stub
    }

    /**
     * @inheritdoc
     */
    public function afterSave($insert, $changedAttributes)
    {
        if ($insert) {
            // 如果套餐商品而且有子商品
            if (($this->product_type & self::PRODUCT_TYPE_GROUP) && $this->relativeProducts) {
                $convert = new Convert();
                $quote = new Quote();
                foreach ($this->relativeProducts as $relativeProduct) {
                    /** @var \service\message\common\Product $relativeProduct */
                    $isSpecialPrice = abs($this->original_price - $this->price) < 0.01 ? false : true;
                    if ($isSpecialPrice) {    // 特价
                        $relativeProduct->setPrice($relativeProduct->getSpecialPrice());
                    } else {    // 原价
                        $relativeProduct->setPrice($relativeProduct->getOriginalPrice());
                    }
                    $instance = new Product($relativeProduct);
                    /**
                     * 设置购买的参数
                     * @see Quote::_prepareProduct()
                     */
                    $buyRequestProduct = new \service\message\common\Product();
                    $buyRequestProduct->setBuyPath($this->buy_path);
                    $buyRequestProduct->setNum($relativeProduct->getNum() * $this->qty);
                    $buyRequestProduct->setWholesalerId($this->wholesaler_id);

                    $buyRequest = new VarienObject($buyRequestProduct->toArray());
                    $quote->addProduct($instance, $buyRequest);
                }

                $quote->collectTotals(false);

                foreach ($quote->getItems() as $item) {
                    $orderItem = $convert->itemToOrderItem($item);
                    $orderItem->parent_id = $this->getPrimaryKey();
                    $orderItem->order_id = $this->order_id;
                    /* 设置为子商品属性 */
                    $orderItem->product_type = $orderItem->product_type | self::PRODUCT_TYPE_GROUP_SUB;
                    $orderItem->save();
                }
            }
        }
        return parent::afterSave($insert, $changedAttributes);
    }
}
