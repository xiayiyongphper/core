<?php
namespace tests\service\resources;

use service\resources\Exception;
use tests\AbstractTest;

/**
 * Created by PhpStorm.
 * User: henryzhu
 * Date: 17-1-20
 * Time: 上午11:56
 */
class ExceptionTest extends AbstractTest
{
    /**
     * @expectedException \Exception
     * @expectedExceptionCode 39999
     */
    public function testofflineException()
    {
        Exception::offline('system is offline');

    }

    /**
     * @expectedException \Exception
     * @expectedExceptionCode 39004
     */
    public function testcontractorPermissionError()
    {
        Exception::contractorPermissionError();

    }

    /**
     * @expectedException \Exception
     * @expectedExceptionCode 39001
     */
    public function testcontractorInitError()
    {
        Exception::contractorInitError();

    }

    /**
     * @expectedException \Exception
     * @expectedExceptionCode 38006
     */
    public function testcouponNumberError()
    {
        Exception::couponNumberError();

    }

    /**
     * @expectedException \Exception
     * @expectedExceptionCode 38005
     */
    public function testcouponExpire()
    {
        Exception::couponExpire();
    }

    /**
     * @expectedException \Exception
     * @expectedExceptionCode 38004
     */
    public function testcouponReceivedError()
    {
        Exception::couponReceivedError();
    }

    /**
     * @expectedException \Exception
     * @expectedExceptionCode 38003
     */
    public function testcouponUserReceived()
    {
        Exception::couponUserReceived();
    }

    /**
     * @expectedException \Exception
     * @expectedExceptionCode 38002
     */
    public function testcouponUserReceiveOut()
    {
        Exception::couponUserReceiveOut();
    }

    /**
     * @expectedException \Exception
     * @expectedExceptionCode 38001
     */
    public function testcouponReceiveOut()
    {
        Exception::couponReceiveOut();
    }

    /**
     * @expectedException \Exception
     * @expectedExceptionCode 34004
     */
    public function testcatalogProductOutOfRestrictDaily()
    {
        Exception::catalogProductOutOfRestrictDaily('111', 5, 5);
    }

    /**
     * @expectedException \Exception
     * @expectedExceptionCode 35019
     */
    public function testnotSatisfyMinTradeAmount()
    {
        Exception::notSatisfyMinTradeAmount(100);
    }

    /**
     * @expectedException \Exception
     * @expectedExceptionCode 35020
     */
    public function testsalesOrderCanNotDecline()
    {
        Exception::salesOrderCanNotDecline();
    }

    /**
     * @expectedException \Exception
     * @expectedExceptionCode 35014
     */
    public function testsalesOrderCanNotReview()
    {
        Exception::salesOrderCanNotReview();
    }

    /**
     * @expectedException \Exception
     * @expectedExceptionCode 35002
     */
    public static function testpaymentMethodNotSupported()
    {
        Exception::paymentMethodNotSupported();
    }

    /**
     * @expectedException \Exception
     * @expectedExceptionCode 35001
     */
    public function testorderNotExisted()
    {
        Exception::orderNotExisted();
    }

    /**
     * @expectedException \Exception
     * @expectedExceptionCode 32001
     */
    public static function testcustomerNotExisted()
    {
        Exception::customerNotExisted();
    }

    /**
     * @expectedException \Exception
     * @expectedExceptionCode 33001
     */
    public function teststoreNotExisted()
    {
        Exception::storeNotExisted();
    }

    /**
     * @expectedException \Exception
     * @expectedExceptionCode 31001
     */
    public function testresourceNotFound()
    {
        Exception::resourceNotFound();
    }

    /**
     * @expectedException \Exception
     * @expectedExceptionCode 31002
     */
    public function testinvalidRequestRoute()
    {
        Exception::invalidRequestRoute();
    }

    /**
     * @expectedException \Exception
     * @expectedExceptionCode 33003
     */
    public function testmultiStoreNotAllowed()
    {
        Exception::multiStoreNotAllowed();
    }

    /**
     * @expectedException \Exception
     * @expectedExceptionCode 32003
     */
    public function testemptyShoppingCart()
    {
        Exception::emptyShoppingCart();
    }

    /**
     * @expectedException \Exception
     * @expectedExceptionCode 32007
     */
    public function testbalanceInsufficient()
    {
        Exception::balanceInsufficient();
    }

    /**
     * @expectedException \Exception
     * @expectedExceptionCode 35023
     */
    public function testbalanceOverGrandTotal()
    {
        Exception::balanceOverGrandTotal();
    }

    /**
     * @expectedException \Exception
     * @expectedExceptionCode 35024
     */
    public function testbalanceOverDailyLimit()
    {
        Exception::balanceOverDailyLimit();
    }

    /**
     * @expectedException \Exception
     * @expectedExceptionCode 35007
     */
    public function testsalesOrderCanNotCanceled()
    {
        Exception::salesOrderCanNotCanceled();
    }

    /**
     * @expectedException \Exception
     * @expectedExceptionCode 35021
     */
    public function testsalesOrderCanNotUnHold()
    {
        Exception::salesOrderCanNotUnHold();
    }

    /**
     * @expectedException \Exception
     * @expectedExceptionCode 35013
     */
    public function testsalesOrderCanNotReceiptConfirm()
    {
        Exception::salesOrderCanNotReceiptConfirm();
    }

}