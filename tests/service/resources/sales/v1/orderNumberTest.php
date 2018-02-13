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
use service\message\sales\OrderNumberResponse;
use service\resources\core\v1\cms;
use service\resources\core\v1\config;
use service\resources\sales\v1\orderDetail;
use service\resources\sales\v1\orderNumber;
use tests\service\ApplicationTest;

class orderNumberTest extends ApplicationTest
{
    public function getModel()
    {
        return new orderNumber();
    }

    public function testModel()
    {
        $this->assertInstanceOf('service\resources\sales\v1\orderNumber', $this->model);
    }

    public function testRequest()
    {
        $this->assertInstanceOf('service\message\sales\OrderNumberRequest', orderNumber::request());
    }

    public function testResponse()
    {
        $this->assertInstanceOf('service\message\sales\OrderNumberResponse', orderNumber::response());
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
        $request = orderNumber::request();
        $request->setCustomerId($this->customerId);
        $request->setAuthToken($this->authToken);
        $this->header->setRoute('sales.orderNumber');
        $this->header->setSource(SourceEnum::CORE);
        $rawBody = Message::pack($this->header, $request);
        $this->request->setRawBody($rawBody);
        $response = $this->application->handleRequest($this->request);
        $this->assertNotEmpty($response);
        /** @var OrderNumberResponse $data */
        /** @var \service\message\common\ResponseHeader $header */
        list($header, $data) = $response;
        $this->assertEquals(0, $header->getCode());
        $this->assertInstanceOf('service\message\sales\OrderNumberResponse', $data);
    }

}