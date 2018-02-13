<?php
namespace tests\service\resources\sales\v1;

use framework\message\Message;
use service\message\common\SourceEnum;
use service\message\core\CouponReceiveListResponse;
use service\resources\sales\v1\couponReceiveList;
use service\resources\sales\v1\test;
use tests\service\ApplicationTest;

/**
 * Created by PhpStorm.
 * User: henryzhu
 * Date: 17-1-20
 * Time: 上午10:49
 */
class couponReceiveListTest extends ApplicationTest
{
    public function getModel()
    {
        return new couponReceiveList();
    }

    public function testRequest()
    {
        $this->assertInstanceOf('service\message\core\CouponReceiveListRequest', couponReceiveList::request());
    }

    public function testResponse()
    {
        $this->assertInstanceOf('service\message\core\CouponReceiveListResponse', couponReceiveList::response());
    }

    public function testHeader()
    {
        $this->assertInstanceOf('service\message\common\Header', $this->header);
    }

    public function testFrameworkRequest()
    {
        $this->assertInstanceOf('framework\Request', $this->request);
    }

    public function testRun()
    {
        $this->request->setRemote(true);
        $request = couponReceiveList::request();
        $request->setWholesalerId($this->wholesalerId);
        $request->setLocation(2);
        $this->header->setRoute('sales.couponReceiveList');
        $this->header->setSource(SourceEnum::CORE);
        $rawBody = Message::pack($this->header, $request);
        $this->request->setRawBody($rawBody);
        $response = $this->application->handleRequest($this->request);
        $this->assertNotEmpty($response);
        /** @var CouponReceiveListResponse $data */
        /** @var \service\message\common\ResponseHeader $header */
        list($header, $data) = $response;
        $this->assertEquals(0, $header->getCode());
    }

    public function testRun1()
    {
        $this->request->setRemote(true);
        $request = couponReceiveList::request();
        $request->setWholesalerId($this->wholesalerId);
        $request->setRuleId(133);
        $request->setLocation(1);
        $this->header->setRoute('sales.couponReceiveList');
        $this->header->setSource(SourceEnum::CORE);
        $rawBody = Message::pack($this->header, $request);
        $this->request->setRawBody($rawBody);
        $response = $this->application->handleRequest($this->request);
        $this->assertNotEmpty($response);
        /** @var CouponReceiveListResponse $data */
        /** @var \service\message\common\ResponseHeader $header */
        list($header, $data) = $response;
        $this->assertEquals(0, $header->getCode());
    }

    public function testRun2()
    {
        $this->request->setRemote(true);
        $request = couponReceiveList::request();
//        $request->setWholesalerId($this->wholesalerId);
        $request->setRuleId(133);
        $request->setLocation(3);
        $this->header->setRoute('sales.couponReceiveList');
        $this->header->setSource(SourceEnum::CORE);
        $rawBody = Message::pack($this->header, $request);
        $this->request->setRawBody($rawBody);
        $response = $this->application->handleRequest($this->request);
        $this->assertNotEmpty($response);
        /** @var CouponReceiveListResponse $data */
        /** @var \service\message\common\ResponseHeader $header */
        list($header, $data) = $response;
        $this->assertEquals(0, $header->getCode());
    }

    public function testRun3()
    {
        $this->request->setRemote(true);
        $request = couponReceiveList::request();
        $request->setWholesalerId($this->wholesalerId);
//        $request->setRuleId(133);
        $request->setLocation(4);
        $this->header->setRoute('sales.couponReceiveList');
        $this->header->setSource(SourceEnum::CORE);
        $rawBody = Message::pack($this->header, $request);
        $this->request->setRawBody($rawBody);
        $response = $this->application->handleRequest($this->request);
        $this->assertNotEmpty($response);
        /** @var CouponReceiveListResponse $data */
        /** @var \service\message\common\ResponseHeader $header */
        list($header, $data) = $response;
        $this->assertEquals(0, $header->getCode());
    }

    public function testRun4()
    {
        $this->request->setRemote(true);
        $request = couponReceiveList::request();
        $request->setWholesalerId($this->wholesalerId);
//        $request->setRuleId(133);
        $request->setLocation(5);
        $this->header->setRoute('sales.couponReceiveList');
        $this->header->setSource(SourceEnum::CORE);
        $rawBody = Message::pack($this->header, $request);
        $this->request->setRawBody($rawBody);
        $response = $this->application->handleRequest($this->request);
        $this->assertNotEmpty($response);
        /** @var CouponReceiveListResponse $data */
        /** @var \service\message\common\ResponseHeader $header */
        list($header, $data) = $response;
        $this->assertEquals(0, $header->getCode());
    }
}