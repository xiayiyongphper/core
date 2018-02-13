<?php
namespace service\models\sales\quote;

use service\models\sales\Quote;
use service\models\sales\Validator;

/**
 * Discount calculation model
 *
 * @category    Mage
 * @package     LE_SalesRule
 * @author      Magento Core Team <core@magentocommerce.com>
 */
class Discount extends TotalAbstract
{
    /**
     * Discount calculation object
     *
     * @var Validator
     */
    protected $_calculator;

    /**
     * Initialize discount collector
     */
    public function __construct()
    {
        $this->setCode('discount');
        $this->_calculator = new Validator();
    }

    /**
     * Collect address discount amount
     *
     * @param   Quote $quote
     * @return  $this
     */
    public function collect(Quote $quote)
    {
        $this->_calculator->setQuote($quote);
//        if ($quote->isTrial()) {
//            $this->_calculator->initTrial();
//        } else {
//            $this->_calculator->init()->initCoupons();
//        }
        $this->_calculator->init()->initTotals();
        $quote->setDiscountDescription('');
        return $this;
    }

    /**
     * @param   array $config
     * @return  array
     */
    public function processConfigArray($config)
    {
        return $config;
    }
}
