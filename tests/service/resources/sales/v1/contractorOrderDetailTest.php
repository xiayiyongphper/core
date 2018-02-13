<?php
namespace tests\service\resources\sales\v1;

use framework\components\ProxyAbstract;
use framework\components\ToolsAbstract;
use framework\message\Message;
use service\message\common\SourceEnum;
use service\message\sales\CreateOrdersResponse;
use service\resources\sales\v1\contractorHomeData;
use service\resources\sales\v1\contractorOrderDetail;
use service\resources\sales\v1\createOrders;
use service\resources\sales\v1\test;
use tests\service\ApplicationTest;

/**
 * Created by PhpStorm.
 * User: henryzhu
 * Date: 17-1-20
 * Time: 上午10:49
 */
class contractorOrderDetailTest extends ApplicationTest
{
    public function getModel()
    {
        return new contractorOrderDetail();
    }

    public function testRequest()
    {
        $this->assertInstanceOf('service\message\sales\DriverOrderDetailRequest', contractorOrderDetail::request());
    }

    public function testResponse()
    {
        $this->assertInstanceOf('service\message\common\Order', contractorOrderDetail::response());
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
        $this->request->setRemote(false);
        $request = contractorOrderDetail::request();
        $request->setOrderId($this->orderId);
        $this->header->setRoute('sales.contractorOrderDetail');
        $this->header->setSource(SourceEnum::CORE);
        $rawBody = Message::pack($this->header, $request);
        $this->request->setRawBody($rawBody);
        $response = $this->application->handleRequest($this->request);
        $this->assertNotEmpty($response);
        /** @var bool $data */
        /** @var \service\message\common\ResponseHeader $header */
        list($header, $data) = $response;
        $this->assertEquals(0, $header->getCode());
    }

    public function testRun1()
    {
        $this->request->setRemote(true);
        $request = contractorOrderDetail::request();
        $request->setOrderId($this->orderId);
        $this->header->setRoute('sales.contractorOrderDetail');
        $this->header->setSource(SourceEnum::CORE);
        $rawBody = Message::pack($this->header, $request);
        $this->request->setRawBody($rawBody);
        $response = $this->application->handleRequest($this->request);
        $this->assertNotEmpty($response);
        /** @var bool $data */
        /** @var \service\message\common\ResponseHeader $header */
        list($header, $data) = $response;
        $this->assertEquals(31001, $header->getCode());
    }

    public function testRun2()
    {
        $this->request->setRemote(false);
        $request = contractorOrderDetail::request();
        $this->header->setRoute('sales.contractorOrderDetail');
        $this->header->setSource(SourceEnum::CORE);
        $rawBody = Message::pack($this->header, $request);
        $this->request->setRawBody($rawBody);
        $response = $this->application->handleRequest($this->request);
        $this->assertNotEmpty($response);
        /** @var bool $data */
        /** @var \service\message\common\ResponseHeader $header */
        list($header, $data) = $response;
        $this->assertEquals(35001, $header->getCode());
    }

    public function testRun3()
    {
        $this->request->setRemote(false);
        $request = contractorOrderDetail::request();
        $request->setOrderId(123456485);
        $this->header->setRoute('sales.contractorOrderDetail');
        $this->header->setSource(SourceEnum::CORE);
        $rawBody = Message::pack($this->header, $request);
        $this->request->setRawBody($rawBody);
        $response = $this->application->handleRequest($this->request);
        $this->assertNotEmpty($response);
        /** @var bool $data */
        /** @var \service\message\common\ResponseHeader $header */
        list($header, $data) = $response;
        $this->assertEquals(35001, $header->getCode());
    }

    public function testRun4()
    {
        $this->request->setRemote(false);
        $request = contractorOrderDetail::request();
        $request->setIncrementId('123456485');
        $this->header->setRoute('sales.contractorOrderDetail');
        $this->header->setSource(SourceEnum::CORE);
        $rawBody = Message::pack($this->header, $request);
        $this->request->setRawBody($rawBody);
        $response = $this->application->handleRequest($this->request);
        $this->assertNotEmpty($response);
        /** @var bool $data */
        /** @var \service\message\common\ResponseHeader $header */
        list($header, $data) = $response;
        $this->assertEquals(35001, $header->getCode());
    }
}