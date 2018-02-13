<?php
namespace tests\service\resources\core\v1;

use framework\message\Message;
use service\message\common\CategoryNode;
use service\message\common\SourceEnum;
use service\message\core\OrderStatusResponse;
use service\resources\core\v1\getCategory;
use service\resources\core\v1\getStatus;
use service\resources\core\v1\test;
use tests\service\ApplicationTest;

/**
 * Created by PhpStorm.
 * User: henryzhu
 * Date: 17-1-20
 * Time: 上午10:49
 */
class testTest extends ApplicationTest
{
    public function getModel()
    {
        return new test();
    }

    public function testRequest()
    {
        $this->assertInstanceOf('service\message\customer\TestReportRequest', test::request());
    }

    public function testResponse()
    {
        $this->assertTrue(test::response());
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
        $request = test::request();
        $request->setIp('123');
        $this->header->setRoute('core.test');
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
}