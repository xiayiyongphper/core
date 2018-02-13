<?php
namespace tests\service\resources\sales\v1;

use framework\components\ToolsAbstract;
use framework\message\Message;
use service\message\common\SourceEnum;
use service\message\contractor\OrderTrackingResponse;
use service\resources\sales\v1\contractorOrderTracking;
use tests\service\ApplicationTest;

/**
 * Created by PhpStorm.
 * User: henryzhu
 * Date: 17-1-20
 * Time: 上午10:49
 */
class contractorOrderTrackingTest extends ApplicationTest
{
    public function getModel()
    {
        return new contractorOrderTracking();
    }

    public function testRequest()
    {
        $this->assertInstanceOf('service\message\contractor\OrderTrackingRequest', contractorOrderTracking::request());
    }

    public function testResponse()
    {
        $this->assertInstanceOf('service\message\contractor\OrderTrackingResponse', contractorOrderTracking::response());
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
        $request = contractorOrderTracking::request();
        $requestData = [
            'contractor_id' => $this->contractorId,
            'auth_token' => $this->contractorAuthToken,
            'role' => $this->getContractor()->getRole(),
            'city_list' => $this->getContractor()->getCityList()
        ];
        $request->setFrom(ToolsAbstract::pb_array_filter($requestData));
        $this->header->setRoute('sales.contractorOrderTracking');
        $this->header->setSource(SourceEnum::CORE);
        $rawBody = Message::pack($this->header, $request);
        $this->request->setRawBody($rawBody);
        $response = $this->application->handleRequest($this->request);
        $this->assertNotEmpty($response);
        /** @var OrderTrackingResponse $data */
        /** @var \service\message\common\ResponseHeader $header */
        list($header, $data) = $response;
        $this->assertEquals(0, $header->getCode());
    }
}