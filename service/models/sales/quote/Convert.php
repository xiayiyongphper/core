<?php
namespace service\models\sales\quote;

use common\models\SalesFlatOrderItem;
use framework\components\Date;
use service\components\Tools;
use service\models\Product;
use service\models\sales\Quote;
use service\models\VarienObject;

/**
 * Quote data convert model
 *
 * @category    Mage
 * @package     Mage_Sales
 * @author      Magento Core Team <core@magentocommerce.com>
 */
class Convert extends VarienObject
{

    /**
     * Convert quote model to order model
     *
     * @param   Quote $quote
     * @return  Mage_Sales_Model_Order
     */
    public function toOrder(Quote $quote, $order = null)
    {
        if (!($order instanceof Mage_Sales_Model_Order)) {
            $order = Mage::getModel('sales/order');
        }
        /* @var $order Mage_Sales_Model_Order */

        $order->setIncrementId($quote->getReservedOrderId())
            ->setStoreId($quote->getStoreId())
            ->setQuoteId($quote->getId())
            ->setQuote($quote)
            ->setCustomer($quote->getCustomer());

        Mage::helper('core')->copyFieldset('sales_convert_quote', 'to_order', $quote, $order);
        Mage::dispatchEvent('sales_convert_quote_to_order', array('order' => $order, 'quote' => $quote));
        return $order;
    }

    /**
     * Convert quote address model to order
     *
     * @param   Mage_Sales_Model_Quote $quote
     * @return  Mage_Sales_Model_Order
     */
    public function addressToOrder(Mage_Sales_Model_Quote_Address $address, $order = null)
    {
        if (!($order instanceof Mage_Sales_Model_Order)) {
            $order = $this->toOrder($address->getQuote());
        }

        Mage::helper('core')->copyFieldset('sales_convert_quote_address', 'to_order', $address, $order);

        Mage::dispatchEvent('sales_convert_quote_address_to_order', array('address' => $address, 'order' => $order));
        return $order;
    }

    /**
     * Convert quote address to order address
     *
     * @param   Mage_Sales_Model_Quote_Address $address
     * @return  Mage_Sales_Model_Order_Address
     */
    public function addressToOrderAddress(Mage_Sales_Model_Quote_Address $address)
    {
        $orderAddress = Mage::getModel('sales/order_address')
            ->setStoreId($address->getStoreId())
            ->setAddressType($address->getAddressType())
            ->setCustomerId($address->getCustomerId())
            ->setCustomerAddressId($address->getCustomerAddressId());

        Mage::helper('core')->copyFieldset('sales_convert_quote_address', 'to_order_address', $address, $orderAddress);

        Mage::dispatchEvent('sales_convert_quote_address_to_order_address',
            array('address' => $address, 'order_address' => $orderAddress));

        return $orderAddress;
    }

    /**
     * Convert quote payment to order payment
     *
     * @param   Mage_Sales_Model_Quote_Payment $payment
     * @return  Mage_Sales_Model_Quote_Payment
     */
    public function paymentToOrderPayment(Mage_Sales_Model_Quote_Payment $payment)
    {
        $orderPayment = Mage::getModel('sales/order_payment')
            ->setStoreId($payment->getStoreId())
            ->setCustomerPaymentId($payment->getCustomerPaymentId());
        Mage::helper('core')->copyFieldset('sales_convert_quote_payment', 'to_order_payment', $payment, $orderPayment);

        Mage::dispatchEvent('sales_convert_quote_payment_to_order_payment',
            array('order_payment' => $orderPayment, 'quote_payment' => $payment));

        return $orderPayment;
    }

    /**
     * Convert quote item to order item
     *
     * @param   Item $item
     * @return  SalesFlatOrderItem
     */
    public function itemToOrderItem(Item $item)
    {
        $date = new Date();
        $time = $date->gmtDate();
        $orderItem = new SalesFlatOrderItem();
        $orderItem->lsin = $item->getlsin();
        $orderItem->name = $item->getName();
        $orderItem->brand = $item->getBrand();
        $orderItem->sku = $item->getSku();
        $orderItem->specification = $item->getSpecification();
        $orderItem->barcode = $item->getBarcode();
        $orderItem->first_category_id = $item->getFirstCategoryId();
        $orderItem->second_category_id = $item->getSecondCategoryId();
        $orderItem->third_category_id = $item->getThirdCategoryId();
        $orderItem->image = $item->getImage();
        $orderItem->wholesaler_id = $item->getWholesalerId();
        $orderItem->product_id = $item->getProductId();
        $orderItem->product_type = $item->getProductType();
        $orderItem->row_total = $item->getRowTotal();
        $orderItem->price = $item->getPrice();
        $orderItem->original_price = $item->getOriginalPrice();
        $orderItem->product_options = $item->getProductOptions();
        $orderItem->tags = $item->getTags();
        $orderItem->qty = $item->getQty();
        $orderItem->is_calculate_lelai_rebates = $item->getIsCalculateLelaiRebates() ? $item->getIsCalculateLelaiRebates() : 0;
        $orderItem->rebates = $item->getRebates();
        $orderItem->rebates_calculate = $item->getRowTotal() * $item->getRebatesAll() / 100;
        $orderItem->commission = $item->getRowTotal() * $item->getCommission() / 100;
        $orderItem->commission_percent = $item->getCommission();
        $orderItem->created_at = $time;
        $orderItem->updated_at = $time;
        $orderItem->subsidies_wholesaler = $item->getSubsidiesWholesaler();
        $orderItem->subsidies_lelai = $item->getSubsidiesLelai();
        $orderItem->origin = $item->getOrigin();
        $orderItem->promotion_text = $item->getPromotionText();
        $orderItem->buy_path = $item->getBuyPath();
        $orderItem->activity_id = (int)$item->getActivityId();
        $orderItem->additional_info = $item->getAdditionalInfo();
        $orderItem->sales_type = $item->getSalesTypes();

        // 套餐商品
        if ($item->getProductType() & SalesFlatOrderItem::PRODUCT_TYPE_GROUP) {
            $orderItem->relativeProducts = $item->getRelativeProducts();
        }

        if ($item->getRuleApportion()) {
            $orderItem->rule_apportion = $item->getRuleApportion();
        }
        if ($item->getRuleApportionLelai()) {
            $orderItem->rule_apportion_lelai = $item->getRuleApportionLelai();
        }
        if ($item->getRuleApportionWholesaler()) {
            $orderItem->rule_apportion_wholesaler = $item->getRuleApportionWholesaler();
        }
        if ($item->getRuleApportionOrderActLelai()) {
            $orderItem->rule_apportion_order_act_lelai = $item->getRuleApportionOrderActLelai();
        }
        if ($item->getRuleApportionOrderCouponLelai()) {
            $orderItem->rule_apportion_order_coupon_lelai = $item->getRuleApportionOrderCouponLelai();
        }
        if ($item->getRuleApportionProductsActLelai()) {
            $orderItem->rule_apportion_products_act_lelai = $item->getRuleApportionProductsActLelai();
        }
        if ($item->getRuleApportionProductsCouponLelai()) {
            $orderItem->rule_apportion_products_coupon_lelai = $item->getRuleApportionProductsCouponLelai();
        }

        // 当时乐来对这个商品的返点百分比，会计算平台返点与平台单独对这个商品的返点和
        // 如果is_calculate_lelai_rebates为真,则直接取平台全局乐来返点值,否则取rebates_lelai字段
        $orderItem->rebates_lelai = $orderItem->is_calculate_lelai_rebates ? $item->getLelaiRebates() : $item->getRebatesLelai();

        // 乐来对这行商品的补贴
        $orderItem->rebates_calculate_lelai = $item->getRowTotal() * $orderItem->rebates_lelai / 100;

        return $orderItem;
    }


}
