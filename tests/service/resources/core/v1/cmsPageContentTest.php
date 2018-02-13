<?php
namespace tests\service\resources\core\v1;

use framework\components\ToolsAbstract;
use framework\message\Message;
use framework\Request;
use service\message\common\SourceEnum;
use service\message\core\CmsResponse;
use service\message\core\ConfigResponse;
use service\resources\core\v1\cms;
use service\resources\core\v1\cmsPageContent;
use service\resources\core\v1\config;
use tests\service\ApplicationTest;

class cmsPageContentTest extends ApplicationTest
{
    public function getModel()
    {
        return new cmsPageContent();
    }

    public function testGetFetch()
    {
        $this->assertInstanceOf('service\resources\core\v1\cmsPageContent', $this->model);
    }

    public function testGetConfigRequest()
    {
        $this->assertInstanceOf('service\message\core\CmsRequest', cmsPageContent::request());
    }

    public function testGetConfigResponseResponse()
    {
        $this->assertInstanceOf('service\message\core\CmsResponse', cmsPageContent::response());
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
        $this->request->setRemote(true);
        $request = cmsPageContent::request();
        $request->setPageId(2);
        $this->header->setRoute('core.cmsPageContent');
        $this->header->setSource(SourceEnum::CORE);
        $rawBody = Message::pack($this->header, $request);
        $this->request->setRawBody($rawBody);
        $response = $this->application->handleRequest($this->request);
        $this->assertNotEmpty($response);
        /** @var CmsResponse $data */
        /** @var \service\message\common\ResponseHeader $header */
        list($header, $data) = $response;
        $this->assertEquals(0, $header->getCode());
        $this->assertInstanceOf('service\message\core\CmsResponse', $data);
        $this->assertEquals('home', $data->getIdentifier());
        $this->assertNotEmpty($data->getTitle());
        $this->assertNotEmpty($data->getContent());
        $this->assertGreaterThan(0, $data->getPageId());
    }

}