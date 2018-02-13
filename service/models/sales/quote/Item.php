<?php
namespace service\models\sales\quote;

use service\models\Product;
use service\models\sales\Quote;
use service\models\VarienObject;

/**
 * Class Item
 * @package service\models\sales\quote
 * @method int getRuleId()
 * @method setRuleId(int $id)
 * @method float getRowTotal()
 * @method float getOriginalTotal()
 * @method setExcluded(bool $flag)
 * @method bool getExcluded()
 * @method int getProductId()
 * @method setProductId(int $id)
 * @method string getRebatesWholesaler()
 * @method string getRebatesLelai()
 * @method setIndex(int $index)
 * @method int getIndex()
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
 * @method float getSubsidiesLelai()
 * @method \service\models\Product getProduct()
 * @method string getBuyPath()
 * @method \service\message\common\Product[] getRelativeProducts()
 */
class Item extends VarienObject
{
    /**
     * Prefix of model events names
     *
     * @var string
     */
    protected $_eventPrefix = 'sales_quote_item';

    /**
     * Parameter name in event
     *
     * In observe method you can use $observer->getEvent()->getObject() in this case
     *
     * @var string
     */
    protected $_eventObject = 'item';

    /**
     * Quote model object
     *
     * @var Quote
     */
    protected $_quote;


    /**
     * Array of errors associated with this quote item
     *
     * @var Mage_Sales_Model_Status_List
     */
    protected $_errorInfos = null;

    /**
     * Declare quote model object
     *
     * @param   Quote $quote
     * @return Item
     */
    public function setQuote(Quote $quote)
    {
        $this->_quote = $quote;
        $this->setQuoteId($quote->getId());
        return $this;
    }

    /**
     * Retrieve quote model object
     *
     * @return Quote
     */
    public function getQuote()
    {
        return $this->_quote;
    }

    /**
     * Prepare quantity
     *
     * @param float|int $qty
     * @return int|float
     */
    protected function _prepareQty($qty)
    {
        $qty = ($qty > 0) ? $qty : 1;
        return $qty;
    }

    /**
     * Get original (not related with parent item) item quantity
     *
     * @return  int|float
     */
    public function getQty()
    {
        return $this->_getData('qty');
    }

    /**
     * Adding quantity to quote item
     *
     * @param float $qty
     * @return Item
     */
    public function addQty($qty)
    {
        $oldQty = $this->qty;
        $qty = $this->_prepareQty($qty);

        /**
         * We can't modify quontity of existing items which have parent
         * This qty declared just once duering add process and is not editable
         */

        $this->setQty($oldQty + $qty);
        return $this;
    }


    /**
     * Declare quote item quantity
     *
     * @param float $qty
     * @return Item
     */
    public function setQty($qty)
    {
        $qty = $this->_prepareQty($qty);
        $oldQty = $this->_getData('qty');
        $this->setData('qty', $qty);

        if ($this->getQuote() && $this->getQuote()->getIgnoreOldQty()) {
            return $this;
        }

        if ($this->getUseOldQty()) {
            $this->setData('qty', $oldQty);
        }

        return $this;
    }

    /**
     * Setup product for quote item
     *
     * @param   Product $product
     * @return  Item
     */
    public function setProduct($product)
    {
        $this->setData('product', $product)
            ->setProductId($product->getProductId())
            ->setStoreId($product->getWholesalerId())
            ->setWholesalerId($product->getWholesalerId())
            ->setSku($product->getSku())
            ->setSpecification($product->getSpecification())
            ->setImage($product->getImage180X180())
            ->setPrice($product->getPrice())
            ->setOriginalPrice($product->getOriginalPrice())
            ->setName($product->getName())
            ->setBrand($product->getBrand())
            ->setBarcode($product->getBarcode())
            ->setFirstCategoryId($product->getFirstCategoryId())
            ->setSecondCategoryId($product->getSecondCategoryId())
            ->setThirdCategoryId($product->getThirdCategoryId())
            ->setProductOptions(serialize([
                    'specification' => $product->getSpecification(),
                    'package_spe' => $product->getPackageSpe(),
                    'package_num' => $product->getPackageNum(),
                    'package' => $product->getPackage(),]
            ))
            ->setRebatesAll($product->getRebatesAll())
            ->setNum($product->getNum())
            ->setTags(serialize($product->getTags()))
            ->setWeight($product->getWeight())
            ->setRebates($product->getRebates())
            ->setRebatesWholesaler($product->getRebatesWholesaler())
            ->setRebatesLelai($product->getRebatesLelai())
            ->setIsCalculateLelaiRebates($product->getIsCalculateLelaiRebates())
            ->setCommission($product->getCommission())
            ->setSubsidiesWholesaler($product->getSubsidiesWholesaler())
            ->setSubsidiesLelai($product->getSubsidiesLelai())
            ->setLelaiRebates($product->getLelaiRebates())
            ->setOrigin($product->getOrigin())
            ->setPromotionText($product->getPromotionText())
            ->setRuleId($product->getRuleId())
            ->setBuyPath($product->getBuyPath())
            ->setType($product->getType())
            ->setAdditionalInfo($product->getAdditionalInfo())
            ->setRelativeProducts($product->getRelativeProducts())
            ->setActivityId($product->getActivityId())
            ->setLsin($product->getLsin());
        return $this;
    }

    /**
     * Check product representation in item
     *
     * @param   Product $product
     * @return  bool
     */
    public function representProduct($product)
    {
        if (!$product || $this->_productId != $product->getProductId()) {
            return false;
        }
        return true;
    }

    /**
     * Convert Quote Item to array
     *
     * @param array $arrAttributes
     * @return array
     */
    public function toArray(array $arrAttributes = array())
    {
        $data = parent::toArray($arrAttributes);

        if ($product = $this->getProduct()) {
            $data['product'] = $product->toArray();
        }
        return $data;
    }

    /**
     * Checks that item model has data changes.
     * Call save item options if model isn't need to save in DB
     *
     * @return boolean
     */
    protected function _hasModelChanged()
    {
        if (!$this->hasDataChanges()) {
            return false;
        }

        return $this->_getResource()->hasDataChanged($this);
    }

    /**
     * Clone quote item
     *
     * @return Item
     */
    public function __clone()
    {
        $this->setId(null);
        $this->_quote = null;
        return $this;
    }

    /**
     * Returns formatted buy request - object, holding request received from
     * product view page with keys and options for configured product
     *
     * @return VarienObject
     */
    public function getBuyRequest()
    {
        $option = $this->getOptionByCode('info_buyRequest');
        $buyRequest = new VarienObject($option ? unserialize($option->getValue()) : null);

        // Overwrite standard buy request qty, because item qty could have changed since adding to quote
        $buyRequest->setOriginalQty($buyRequest->getQty())
            ->setQty($this->getQty() * 1);

        return $buyRequest;
    }

    /**
     * Sets flag, whether this quote item has some error associated with it.
     *
     * @param bool $flag
     * @return Item
     */
    protected function _setHasError($flag)
    {
        return $this->setData('has_error', $flag);
    }

    /**
     * Sets flag, whether this quote item has some error associated with it.
     * When TRUE - also adds 'unknown' error information to list of quote item errors.
     * When FALSE - clears whole list of quote item errors.
     * It's recommended to use addErrorInfo() instead - to be able to remove error statuses later.
     *
     * @param bool $flag
     * @return Item
     * @see addErrorInfo()
     */
    public function setHasError($flag)
    {
        if ($flag) {
            $this->addErrorInfo();
        } else {
            $this->_clearErrorInfo();
        }
        return $this;
    }

    /**
     * Clears list of errors, associated with this quote item.
     * Also automatically removes error-flag from oneself.
     *
     * @return Item
     */
    protected function _clearErrorInfo()
    {
        $this->_errorInfos->clear();
        $this->_setHasError(false);
        return $this;
    }

    /**
     * Adds error information to the quote item.
     * Automatically sets error flag.
     *
     * @param string|null $origin Usually a name of module, that embeds error
     * @param int|null $code Error code, unique for origin, that sets it
     * @param string|null $message Error message
     * @param Varien_Object|null $additionalData Any additional data, that caller would like to store
     * @return Item
     */
    public function addErrorInfo($origin = null, $code = null, $message = null, $additionalData = null)
    {
        $this->_errorInfos->addItem($origin, $code, $message, $additionalData);
        if ($message !== null) {
            $this->setMessage($message);
        }
        $this->_setHasError(true);

        return $this;
    }

    /**
     * Retrieves all error infos, associated with this item
     *
     * @return array
     */
    public function getErrorInfos()
    {
        return $this->_errorInfos->getItems();
    }

    /**
     * Removes error infos, that have parameters equal to passed in $params.
     * $params can have following keys (if not set - then any item is good for this key):
     *   'origin', 'code', 'message'
     *
     * @param array $params
     * @return Item
     */
    public function removeErrorInfosByParams($params)
    {
        $removedItems = $this->_errorInfos->removeItemsByParams($params);
        foreach ($removedItems as $item) {
            if ($item['message'] !== null) {
                $this->removeMessageByText($item['message']);
            }
        }

        if (!$this->_errorInfos->getItems()) {
            $this->_setHasError(false);
        }

        return $this;
    }

    /**
     * Get item option by code
     *
     * @param   string $code
     * @return  Mage_Catalog_Model_Product_Configuration_Item_Option_Interface
     */
    public function getOptionByCode($code)
    {
        // TODO: Implement getOptionByCode() method.
    }

    /**
     * Calculate item row total price
     *
     * @return $this
     */
    public function calcRowTotal()
    {
        $qty = $this->getTotalQty();
        // Round unit price before multiplying to prevent losing 1 cent on subtotal
        $total = $this->roundPrice($this->getPrice()) * $qty;
        $originalTotal = $this->roundPrice($this->getOriginalPrice()) * $qty;
        $this->setRowTotal($this->roundPrice($total));
        $this->setOriginalTotal($this->roundPrice($originalTotal));
        return $this;
    }

    /**
     * Get item price. Item price currency is website base currency.
     *
     * @return decimal
     */
    public function getPrice()
    {
        return $this->_getData('price');
    }

    /**
     * @return float
     */
    public function getOriginalPrice()
    {
        return $this->getProduct()->getOriginalPrice();
    }

    /**
     * Get total item quantity (include parent item relation)
     *
     * @return  int|float
     */
    public function getTotalQty()
    {
        return $this->getQty();
    }

    /**
     * @return int
     */
    public function getProductType()
    {
        return $this->getProduct()->getType();
    }

    /**
     * @return string
     */
    public function getSalesTypes()
    {
        return $this->getProduct()->getSalesTypes();
    }

    /**
     * Round price
     *
     * @param mixed $price
     * @return double
     */
    public function roundPrice($price)
    {
        return round($price, 2);
    }
}
