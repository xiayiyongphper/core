<?php
/**
 * Created by PhpStorm.
 * User: ZQY
 * Date: 2017/9/11
 * Time: 11:41
 */

namespace service\helpers;

use service\message\customer\CustomerResponse;
use service\models\Product;
use service\models\sales\Quote;
use service\models\VarienObject;

/**
 * Class ProductHelper
 * @package service\helpers
 */
class ProductHelper
{
    const SPECIAL_PRODUCT_MASK = 0x80000000;
    const TYPE_SECKILL_OLD = 1;
    const TYPE_SECKILL = 0x4000;

    const TYPE_SPECIAL_OLD = 2;
    const TYPE_SPECIAL = 0x6000;

    const TYPE_GROUP_OLD = 2;
    const TYPE_GROUP = 0x4;

    const TYPE_GROUP_SUB = 0x8;

    /** @var int 普通商品，可以跟其他类型组合 */
    const TYPE_SIMPLE = 0x2000;
    const TYPE_SIMPLE_OLD = 0;
    /**
     * @param int $productId
     */
    static public function isSpecialProduct($productId)
    {
        return ($productId & self::SPECIAL_PRODUCT_MASK) ? true : false;
    }

    static public function isOldSeckillProductType($type)
    {
        return $type == self::TYPE_SECKILL_OLD ? true : false;
    }

    static public function isNewSeckillProductType($type)
    {
        return !empty($type) && ((0xe000 & $type) == self::TYPE_SECKILL);
    }

    static public function isOldGroupProductType($type)
    {
        return $type == self::TYPE_GROUP_OLD ? true : false;
    }

    static public function isOldSimpleProductType($type)
    {
        return $type == self::TYPE_SIMPLE_OLD ? true : false;
    }

    /**
     * @param CustomerResponse $customer
     * @param Product $productWrapper
     * @param VarienObject $buyRequest
     * @return array
     */
    static public function seperateRestrictDailyProduct($customer, $productWrapper, $buyRequest)
    {
        if ($productWrapper->getRestrictDaily() <= 0) { // 不是限购的，直接返回
            return [[$productWrapper, $buyRequest]];
        }

        $qty = $buyRequest->getNum();   // 现在购买的数量
        $restrictDailyNum = $productWrapper->getRestrictDaily();    // 限购数量
        $alreadyBuyNum = $productWrapper->getDailyPurchaseNum($customer);   // 已经购买的数量
        $leftNum = $restrictDailyNum - $alreadyBuyNum;  // 剩余数量

        /**
         * 如果 有剩余限购数 #1
         *    如果 购买的数量大于剩余的限购数，则剩下的限购数按特价购买，其余按原价购买。 #1-1
         *        需要重新设置数量、价格。@see Quote::_prepareProduct()
         *    否则 都按特价购买 #1-2
         * 否则全部按照特价购买。 #2
         */
        if ($leftNum > 0) { // #1
            if ($qty > $leftNum) { #1-1
                // 特价部分，现仅需要修改数量
                $productWrapper->setNum($leftNum);
                $buyRequest->setNum($leftNum);
                // 原价部分
                $productWrapper1 = clone $productWrapper;
                $buyRequest1 = clone $buyRequest;
                $productWrapper1->setNum($qty - $leftNum);
                $productWrapper1->setPrice($productWrapper1->getOriginalPrice());
                $buyRequest1->setNum($qty - $leftNum);
                return [[$productWrapper, $buyRequest], [$productWrapper1, $buyRequest1]];
            } else { // #1-2
                return [[$productWrapper, $buyRequest]];
            }
        } else {    // #2
            $productWrapper->setPrice($productWrapper->getOriginalPrice());
            return [[$productWrapper, $buyRequest]];
        }
    }
}
