<?php
namespace tests\service\resources\sales\v1;

use framework\message\Message;
use service\message\common\SourceEnum;
use service\message\contractor\StoreRecentlyOrderResponse;
use service\resources\sales\v1\customerOrderData;
use service\resources\sales\v1\test;
use tests\service\ApplicationTest;

/**
 * Created by PhpStorm.
 * User: henryzhu
 * Date: 17-1-20
 * Time: 上午10:49
 */
class customerOrderDataTest extends ApplicationTest
{
    public function getModel()
    {
        return new customerOrderData();
    }

    public function testRequest()
    {
        $this->assertInstanceOf('service\message\contractor\StoreRecentlyOrderRequest', customerOrderData::request());
    }

    public function testResponse()
    {
        $this->assertInstanceOf('service\message\contractor\StoreRecentlyOrderResponse', customerOrderData::response());
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
        $request = customerOrderData::request();
        $request->setCustomerId($this->customerId);
        $this->header->setRoute('sales.customerOrderData');
        $this->header->setSource(SourceEnum::CORE);
        $rawBody = Message::pack($this->header, $request);
        $this->request->setRawBody($rawBody);
        $response = $this->application->handleRequest($this->request);
        $this->assertNotEmpty($response);
        /** @var StoreRecentlyOrderResponse $data */
        /** @var \service\message\common\ResponseHeader $header */
        list($header, $data) = $response;
        $this->assertEquals(0, $header->getCode());
    }
}