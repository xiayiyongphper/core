<?php
namespace tests\service\resources\core\v1;

use framework\message\Message;
use service\message\common\CategoryNode;
use service\message\common\SourceEnum;
use service\message\core\getHomeActivityResponse;
use service\resources\core\v1\getCategory;
use service\resources\core\v1\getHomeActivity;
use tests\service\ApplicationTest;

/**
 * Created by PhpStorm.
 * User: henryzhu
 * Date: 17-1-20
 * Time: 上午10:49
 */
class getHomeActivityTest extends ApplicationTest
{
    public function getModel()
    {
        return new getHomeActivity();
    }

    public function testRequest()
    {
        $this->assertInstanceOf('service\message\core\getHomeActivityRequest', getHomeActivity::request());
    }

    public function testResponse()
    {
        $this->assertInstanceOf('service\message\core\getHomeActivityResponse', getHomeActivity::response());
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
        $request = getHomeActivity::request();
        $request->setCustomerId($this->customerId);
        $request->setAuthToken($this->authToken);
        $this->header->setRoute('core.getHomeActivity');
        $this->header->setSource(SourceEnum::CORE);
        $rawBody = Message::pack($this->header, $request);
        $this->request->setRawBody($rawBody);
        $response = $this->application->handleRequest($this->request);
        $this->assertNotEmpty($response);
        /** @var getHomeActivityResponse $data */
        /** @var \service\message\common\ResponseHeader $header */
        list($header, $data) = $response;
        $this->assertEquals(0, $header->getCode());
        $this->assertInstanceOf('service\message\core\getHomeActivityResponse', $data);
    }
}