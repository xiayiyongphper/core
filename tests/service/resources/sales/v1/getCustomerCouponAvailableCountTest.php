<?php
namespace tests\service\resources\sales\v1;

use framework\message\Message;
use service\message\common\SourceEnum;
use service\message\sales\getCustomerCouponAvailableCountResponse;
use service\resources\sales\v1\getCustomerCouponAvailableCount;
use service\resources\sales\v1\test;
use tests\service\ApplicationTest;

/**
 * Created by PhpStorm.
 * User: henryzhu
 * Date: 17-1-20
 * Time: 上午10:49
 */
class getCustomerCouponAvailableCountTest extends ApplicationTest
{
    public function getModel()
    {
        return new getCustomerCouponAvailableCount();
    }

    public function testRequest()
    {
        $this->assertInstanceOf('service\message\sales\getCustomerCouponAvailableCountRequest', getCustomerCouponAvailableCount::request());
    }

    public function testResponse()
    {
        $this->assertInstanceOf('service\message\sales\getCustomerCouponAvailableCountResponse', getCustomerCouponAvailableCount::response());
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
        $request = getCustomerCouponAvailableCount::request();
        $request->setCustomerId($this->customerId);
        $request->setAuthToken($this->authToken);
        $this->header->setRoute('sales.getCustomerCouponAvailableCount');
        $this->header->setSource(SourceEnum::CORE);
        $rawBody = Message::pack($this->header, $request);
        $this->request->setRawBody($rawBody);
        $response = $this->application->handleRequest($this->request);
        $this->assertNotEmpty($response);
        /** @var getCustomerCouponAvailableCountResponse $data */
        /** @var \service\message\common\ResponseHeader $header */
        list($header, $data) = $response;
        $this->assertEquals(0, $header->getCode());
    }
}