<?php
namespace tests\service\resources\sales\v1;

use framework\message\Message;
use service\message\common\Order;
use service\message\common\SourceEnum;
use service\resources\sales\v1\orderDetailBrief;
use tests\service\ApplicationTest;

class orderDetailBriefTest extends ApplicationTest
{
    public function getModel()
    {
        return new orderDetailBrief();
    }

    public function testModel()
    {
        $this->assertInstanceOf('service\resources\sales\v1\orderDetailBrief', $this->model);
    }

    public function testRequest()
    {
        $this->assertInstanceOf('service\message\sales\OrderDetailRequest', orderDetailBrief::request());
    }

    public function testResponse()
    {
        $this->assertInstanceOf('service\message\common\Order', orderDetailBrief::response());
    }

    public function testGetHeader()
    {
        $this->assertInstanceOf('service\message\common\Header', $this->header);
    }

    public function testGetRequest()
    {
        $this->assertInstanceOf('framework\Request', $this->request);
    }

    public function testRun()
    {
        $this->request->setRemote(true);
        $request = orderDetailBrief::request();
        $request->setCustomerId($this->customerId);
        $request->setAuthToken($this->authToken);
        $request->setOrderId(467);
        $this->header->setRoute('sales.orderDetailBrief');
        $this->header->setSource(SourceEnum::CORE);
        $rawBody = Message::pack($this->header, $request);
        $this->request->setRawBody($rawBody);
        $response = $this->application->handleRequest($this->request);
        $this->assertNotEmpty($response);
        /** @var Order $data */
        /** @var \service\message\common\ResponseHeader $header */
        list($header, $data) = $response;
        $this->assertEquals(0, $header->getCode());
        $this->assertInstanceOf('service\message\common\Order', $data);
    }

}