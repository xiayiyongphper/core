<?php
namespace tests\service\resources\sales\v1;

use framework\components\ProxyAbstract;
use framework\components\ToolsAbstract;
use framework\message\Message;
use service\message\common\SourceEnum;
use service\resources\sales\v1\contractorHomeData;
use service\resources\sales\v1\test;
use tests\service\ApplicationTest;

/**
 * Created by PhpStorm.
 * User: henryzhu
 * Date: 17-1-20
 * Time: 上午10:49
 */
class contractorHomeDataTest extends ApplicationTest
{
    public function getModel()
    {
        return new contractorHomeData();
    }

    public function testRequest()
    {
        $this->assertInstanceOf('service\message\contractor\ContractorHomeDataRequest', contractorHomeData::request());
    }

    public function testResponse()
    {
        $this->assertInstanceOf('service\message\common\ContractorStatics', contractorHomeData::response());
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
        $request = contractorHomeData::request();
        $city = $this->getContractor()->getCity();
        $city_list = $this->getContractor()->getCityList();
        $role = $this->getContractor()->getRole();
        $data = [
            'contractor_id' => $this->getContractor()->getContractorId(),
            'city' => $city,
            'role' => $role,
            'city_list' => $city_list,
        ];
        $request->setFrom(ToolsAbstract::pb_array_filter($data));
        $this->header->setRoute('sales.contractorHomeData');
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