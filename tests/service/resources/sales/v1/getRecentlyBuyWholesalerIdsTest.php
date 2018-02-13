<?php
namespace tests\service\resources\sales\v1;

use framework\message\Message;
use service\message\common\SourceEnum;
use service\message\core\getWholesalerResponse;
use service\resources\sales\v1\getRecentlyBuyWholesalerIds;
use service\resources\sales\v1\test;
use tests\service\ApplicationTest;

/**
 * Created by PhpStorm.
 * User: henryzhu
 * Date: 17-1-20
 * Time: 上午10:49
 */
class getRecentlyBuyWholesalerIdsTest extends ApplicationTest
{
    public function getModel()
    {
        return new getRecentlyBuyWholesalerIds();
    }

    public function testRequest()
    {
        $this->assertInstanceOf('service\message\core\getWholesalerRequest', getRecentlyBuyWholesalerIds::request());
    }

    public function testResponse()
    {
        $this->assertInstanceOf('service\message\core\getWholesalerResponse', getRecentlyBuyWholesalerIds::response());
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
        $request = getRecentlyBuyWholesalerIds::request();
        $request->setCustomerId($this->customerId);
        $this->header->setRoute('sales.getRecentlyBuyWholesalerIds');
        $this->header->setSource(SourceEnum::CORE);
        $rawBody = Message::pack($this->header, $request);
        $this->request->setRawBody($rawBody);
        $response = $this->application->handleRequest($this->request);
        $this->assertNotEmpty($response);
        /** @var getWholesalerResponse $data */
        /** @var \service\message\common\ResponseHeader $header */
        list($header, $data) = $response;
        $this->assertEquals(0, $header->getCode());
    }
}