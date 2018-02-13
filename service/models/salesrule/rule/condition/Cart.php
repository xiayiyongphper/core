<?php
namespace service\models\salesrule\rule\condition;

use common\models\salesrule\Rule;
use service\models\sales\Quote;
use service\models\salesrule\rule\ConditionAbstract;
use service\models\VarienObject;

class Cart extends ConditionAbstract
{
    public function loadAttributeOptions()
    {
        $attributes = array(
            'subtotal' => 'Subtotal',
            'total_qty' => 'Total Items Quantity',
            //'weight' => Mage::helper('salesrule')->__('Total Weight'),
            //'payment_method' => Mage::helper('salesrule')->__('Payment Method'),
            //'shipping_method' => Mage::helper('salesrule')->__('Shipping Method'),
            //'postcode' => Mage::helper('salesrule')->__('Shipping Postcode'),
            //'region' => Mage::helper('salesrule')->__('Shipping Region'),
            //'region_id' => Mage::helper('salesrule')->__('Shipping State/Province'),
            //'country_id' => Mage::helper('salesrule')->__('Shipping Country'),
        );

        $this->setAttributeOption($attributes);

        return $this;
    }

    public function getAttributeElement()
    {
        $element = parent::getAttributeElement();
        $element->setShowAsText(true);
        return $element;
    }

    public function getInputType()
    {
        switch ($this->getAttribute()) {
            case 'base_subtotal':
            case 'total_qty':
                return 'string';
            case 'weight':
                return 'numeric';
            case 'shipping_method':
            case 'payment_method':
            case 'country_id':
            case 'region_id':
                return 'select';
        }
        return 'string';
    }

    public function getValueElementType()
    {
        switch ($this->getAttribute()) {
            case 'shipping_method':
            case 'payment_method':
            case 'country_id':
            case 'region_id':
                return 'select';
        }
        return 'text';
    }

    public function getValueSelectOptions()
    {
        if (!$this->hasData('value_select_options')) {
            switch ($this->getAttribute()) {
                case 'country_id':
                    $options = Mage::getModel('adminhtml/system_config_source_country')
                        ->toOptionArray();
                    break;

                case 'region_id':
                    $options = Mage::getModel('adminhtml/system_config_source_allregion')
                        ->toOptionArray();
                    break;

                case 'shipping_method':
                    $options = Mage::getModel('adminhtml/system_config_source_shipping_allmethods')
                        ->toOptionArray();
                    break;

                case 'payment_method':
                    $options = Mage::getModel('adminhtml/system_config_source_payment_allmethods')
                        ->toOptionArray();
                    break;

                default:
                    $options = array();
            }
            $this->setData('value_select_options', $options);
        }
        return $this->getData('value_select_options');
    }

    /**
     * @param VarienObject|Quote $quote
     * @return bool
     */
    public function validate(VarienObject $quote)
    {
        $cart = new VarienObject();
        // 送到下一层去判断
        $cart->setSubtotal($quote->getGrandTotal());
        $cart->setTotalQty($quote->getItemsQty());
        $result = parent::validate($cart);
        if ($result === false && $quote->isTrial()) {
            switch ($this->getAttribute()) {
                case 'subtotal':
                    if ($quote->getRuleType() == Rule::TYPE_ITEM || $quote->getRuleType() == Rule::TYPE_GROUP) {
                        $quote->setUnavailableReason(Rule::UNAVAILABLE_REASON_3);
                    } else {
                        $quote->setUnavailableReason(Rule::UNAVAILABLE_REASON_1);
                    }
                    break;
                case 'total_qty':
                    if ($quote->getRuleType() == Rule::TYPE_ITEM || $quote->getRuleType() == Rule::TYPE_GROUP) {
                        $quote->setUnavailableReason(Rule::UNAVAILABLE_REASON_6);
                    } else {
                        $quote->setUnavailableReason(Rule::UNAVAILABLE_REASON_2);
                    }
                    break;
            }
        }
        return $result;
    }
}
