<?php
namespace tests\service\resources\core\v1;

use framework\message\Message;
use service\message\common\CategoryNode;
use service\message\common\SourceEnum;
use service\message\core\OrderStatusResponse;
use service\resources\core\v1\getCategory;
use service\resources\core\v1\getStatus;
use tests\service\ApplicationTest;

/**
 * Created by PhpStorm.
 * User: henryzhu
 * Date: 17-1-20
 * Time: 上午10:49
 */
class getStatusTest extends ApplicationTest
{
    public function getModel()
    {
        return new getStatus();
    }

    public function testRequest()
    {
        $this->assertInstanceOf('service\message\core\OrderStatusRequest', getStatus::request());
    }

    public function testResponse()
    {
        $this->assertInstanceOf('service\message\core\OrderStatusResponse', getStatus::response());
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
        $request = getStatus::request();
        $request->setContractorId($this->contractorId);
        $request->setAuthToken($this->contractorAuthToken);
        $this->header->setRoute('core.getStatus');
        $this->header->setSource(SourceEnum::CORE);
        $rawBody = Message::pack($this->header, $request);
        $this->request->setRawBody($rawBody);
        $response = $this->application->handleRequest($this->request);
        $this->assertNotEmpty($response);
        /** @var OrderStatusResponse $data */
        /** @var \service\message\common\ResponseHeader $header */
        list($header, $data) = $response;
        $this->assertEquals(0, $header->getCode());
        $this->assertInstanceOf('service\message\core\OrderStatusResponse', $data);
    }
}