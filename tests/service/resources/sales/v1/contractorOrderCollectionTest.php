<?php
namespace tests\service\resources\sales\v1;

use framework\components\ProxyAbstract;
use framework\components\ToolsAbstract;
use framework\message\Message;
use service\message\common\Pagination;
use service\message\common\SourceEnum;
use service\message\sales\OrderCollectionResponse;
use service\resources\sales\v1\contractorHomeData;
use service\resources\sales\v1\contractorOrderCollection;
use service\resources\sales\v1\test;
use tests\service\ApplicationTest;

/**
 * Created by PhpStorm.
 * User: henryzhu
 * Date: 17-1-20
 * Time: 上午10:49
 */
class contractorOrderCollectionTest extends ApplicationTest
{
    public function getModel()
    {
        return new contractorOrderCollection();
    }

    public function testRequest()
    {
        $this->assertInstanceOf('service\message\contractor\OrderListRequest', contractorOrderCollection::request());
    }

    public function testResponse()
    {
        $this->assertInstanceOf('service\message\sales\OrderCollectionResponse', contractorOrderCollection::response());
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
        $request = contractorOrderCollection::request();
        $request->setContractorId($this->contractorId);
        $request->setAuthToken($this->contractorAuthToken);
        $page = new Pagination();
        $page->setPage(1);
        $request->setPagination($page);
        $this->header->setRoute('sales.contractorOrderCollection');
        $this->header->setSource(SourceEnum::CORE);
        $rawBody = Message::pack($this->header, $request);
        $this->request->setRawBody($rawBody);
        $response = $this->application->handleRequest($this->request);
        $this->assertNotEmpty($response);
        /** @var OrderCollectionResponse $data */
        /** @var \service\message\common\ResponseHeader $header */
        list($header, $data) = $response;
        $this->assertEquals(0, $header->getCode());
    }
}