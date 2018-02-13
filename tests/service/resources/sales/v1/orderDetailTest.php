<?php
namespace tests\service\resources\sales\v1;

use framework\components\ToolsAbstract;
use framework\message\Message;
use framework\Request;
use service\message\common\Order;
use service\message\common\SourceEnum;
use service\message\core\CmsResponse;
use service\message\core\ConfigResponse;
use service\message\sales\OrderDetailRequest;
use service\resources\core\v1\cms;
use service\resources\core\v1\config;
use service\resources\sales\v1\orderDetail;
use tests\service\ApplicationTest;

class orderDetailTest extends ApplicationTest
{
    public function getModel()
    {
        return new orderDetail();
    }

    public function testModel()
    {
        $this->assertInstanceOf('service\resources\sales\v1\orderDetail', $this->model);
    }

    public function testRequest()
    {
        $this->assertInstanceOf('service\message\sales\OrderDetailRequest', orderDetail::request());
    }

    public function testResponse()
    {
        $this->assertInstanceOf('service\message\common\Order', orderDetail::response());
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
        $request = OrderDetail::request();
        $request->setCustomerId($this->customerId);
        $request->setAuthToken($this->authToken);
        $request->setOrderId($this->orderId);
        $this->header->setRoute('sales.orderDetail');
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