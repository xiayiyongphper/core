<?php
namespace tests\service\resources\sales\v1;

use framework\message\Message;
use service\message\common\SourceEnum;
use service\message\sales\CreateOrdersResponse;
use service\resources\sales\v1\createOrders;
use service\resources\sales\v1\reorder;
use service\resources\sales\v1\test;
use tests\service\ApplicationTest;

/**
 * Created by PhpStorm.
 * User: henryzhu
 * Date: 17-1-20
 * Time: 上午10:49
 */
class reorderTest extends ApplicationTest
{
    public function getModel()
    {
        return new reorder();
    }

    /*
     * @covers reorder::run
     */
    public function testRequest()
    {
        $this->assertInstanceOf('service\message\common\OrderAction', reorder::request());
    }

    public function testResponse()
    {
        $this->assertInstanceOf('service\message\common\UniversalResponse', reorder::response());
    }

    public function testHeader()
    {
        $this->assertInstanceOf('service\message\common\Header', $this->header);
    }

    public function testFrameworkRequest()
    {
        $this->assertInstanceOf('framework\Request', $this->request);
    }

    public function testReorder()
    {
        /** @var CreateOrdersResponse $createOrderResponse */
        $this->request->setRemote(true);
        $request = reorder::request();
        $request->setCustomerId($this->customerId);
        $request->setAuthToken($this->authToken);
        $request->setOrderId($this->orderId);
        $this->header->setRoute('sales.reorder');
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

    public function testReorder1()
    {
        /** @var CreateOrdersResponse $createOrderResponse */
        $this->request->setRemote(true);
        $request = reorder::request();
        $request->setCustomerId($this->customerId);
        $request->setAuthToken($this->authToken);
        $request->setOrderId(-1);
        $this->header->setRoute('sales.reorder');
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