<?php
namespace tests\service\resources\sales\v1;

use framework\message\Message;
use service\message\common\Order;
use service\message\common\Pagination;
use service\message\common\SourceEnum;
use service\message\merchant\SaleRuleResponse;
use service\message\sales\ProductReportResponse;
use service\resources\sales\v1\productReport;
use service\resources\sales\v1\saleRule;
use tests\service\ApplicationTest;

/**
 * Created by PhpStorm.
 * User: henryzhu
 * Date: 17-1-20
 * Time: 上午11:22
 */
class saleRuleTest extends ApplicationTest
{
    public function getModel()
    {
        return new saleRule();
    }

    public function testModel()
    {
        $this->assertInstanceOf('service\resources\sales\v1\saleRule', $this->model);
    }

    public function testRequest()
    {
        $this->assertInstanceOf('service\message\merchant\SaleRuleRequest', saleRule::request());
    }

    public function testResponse()
    {
        $this->assertInstanceOf('service\message\merchant\SaleRuleResponse', saleRule::response());
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
        $this->request->setRemote(false);
        $request = saleRule::request();
        $request->appendRuleId(146);
        $this->header->setRoute('sales.saleRule');
        $this->header->setSource(SourceEnum::CORE);
        $rawBody = Message::pack($this->header, $request);
        $this->request->setRawBody($rawBody);
        $response = $this->application->handleRequest($this->request);
        $this->assertNotEmpty($response);
        /** @var SaleRuleResponse $data */
        /** @var \service\message\common\ResponseHeader $header */
        list($header, $data) = $response;
        $this->assertEquals(0, $header->getCode());
        $this->assertInstanceOf('service\message\merchant\SaleRuleResponse', $data);
    }

    public function testRun1()
    {
        $this->request->setRemote(false);
        $request = saleRule::request();
        $request->appendWholesalerId($this->wholesalerId);
        $this->header->setRoute('sales.saleRule');
        $this->header->setSource(SourceEnum::CORE);
        $rawBody = Message::pack($this->header, $request);
        $this->request->setRawBody($rawBody);
        $response = $this->application->handleRequest($this->request);
        $this->assertNotEmpty($response);
        /** @var SaleRuleResponse $data */
        /** @var \service\message\common\ResponseHeader $header */
        list($header, $data) = $response;
        $this->assertEquals(0, $header->getCode());
        $this->assertInstanceOf('service\message\merchant\SaleRuleResponse', $data);
    }
}