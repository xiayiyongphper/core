<?php
namespace tests\service\resources\core\v1;

use framework\components\ToolsAbstract;
use framework\message\Message;
use framework\Request;
use service\message\common\SourceEnum;
use service\message\core\ConfigResponse;
use service\resources\core\v1\config;
use tests\service\ApplicationTest;

class configTest extends ApplicationTest
{
    public function getModel()
    {
        return new config();
    }

    public function testGetFetch()
    {
        $this->assertInstanceOf('service\resources\core\v1\config', $this->model);
    }

    public function testGetConfigRequest()
    {
        $this->assertInstanceOf('service\message\core\ConfigRequest', config::request());
    }

    public function testGetConfigResponseResponse()
    {
        $this->assertInstanceOf('service\message\core\ConfigResponse', config::response());
    }

    public function testGetHeader()
    {
        $this->assertInstanceOf('service\message\common\Header', $this->header);
    }

    public function testGetRequest()
    {
        $this->assertInstanceOf('framework\Request', $this->request);
    }

    public function testRunNonDebug()
    {
        $this->request->setRemote(true);
        $request = config::request();
        $request->setVer('1.0');
        $this->header->setRoute('core.config');
        $this->header->setSource(SourceEnum::CORE);
        $rawBody = Message::pack($this->header, $request);
        $this->request->setRawBody($rawBody);
        $response = $this->application->handleRequest($this->request);
        $this->assertNotEmpty($response);
        /** @var ConfigResponse $data */
        /** @var \service\message\common\ResponseHeader $header */
        list($header, $data) = $response;
        $this->assertEquals(0, $header->getCode());
        $this->assertInstanceOf('service\message\core\ConfigResponse', $data);
        $this->assertEquals('4008949580', $data->getCsh());
        $this->assertStringStartsWith('http://assets.lelai.com', $data->getCouponHelperUrl());
        $this->assertStringStartsWith('http://assets.lelai.com', $data->getWalletHelperUrl());
        $this->assertStringStartsWith('http://assets.lelai.com', $data->getHelperUrl());
    }

    public function testRunDebugLevel1()
    {

        $this->request->setRemote(true);
        $request = config::request();
        $request->setVer('1.0');
        $this->header->setRoute('core.config');
        $this->header->setSource(SourceEnum::CORE);
        $this->header->setCustomerId($this->customerId);
        ToolsAbstract::getRedis()->hSet(Request::REDIS_KEY_DEBUG_DEVICE_TABLE, $this->customerId, 1);
        $rawBody = Message::pack($this->header, $request);
        $this->request->setRawBody($rawBody);
        $response = $this->application->handleRequest($this->request);
        $this->assertNotEmpty($response);
        /** @var ConfigResponse $data */
        /** @var \service\message\common\ResponseHeader $header */
        list($header, $data) = $response;
        $this->assertEquals(0, $header->getCode());
        $this->assertInstanceOf('service\message\core\ConfigResponse', $data);
        $this->assertEquals('4008949580', $data->getCsh());
        $this->assertStringStartsWith('http://assets.lelai.com', $data->getCouponHelperUrl());
        $this->assertStringStartsWith('http://assets.lelai.com', $data->getWalletHelperUrl());
        $this->assertStringStartsWith('http://assets.lelai.com', $data->getHelperUrl());
    }

    public function testRunDebugLevel2()
    {

        $this->request->setRemote(true);
        $request = config::request();
        $request->setVer('1.0');
        $this->header->setRoute('core.config');
        $this->header->setSource(SourceEnum::CORE);
        $this->header->setCustomerId($this->customerId);
        ToolsAbstract::getRedis()->hSet(Request::REDIS_KEY_DEBUG_DEVICE_TABLE, $this->customerId, 2);
        $rawBody = Message::pack($this->header, $request);
        $this->request->setRawBody($rawBody);
        $response = $this->application->handleRequest($this->request);
        $this->assertNotEmpty($response);
        /** @var ConfigResponse $data */
        /** @var \service\message\common\ResponseHeader $header */
        list($header, $data) = $response;
        $this->assertEquals(0, $header->getCode());
        $this->assertInstanceOf('service\message\core\ConfigResponse', $data);
        $this->assertEquals('4008949580', $data->getCsh());
        $this->assertStringStartsWith('http://assets.lelai.com', $data->getCouponHelperUrl());
        $this->assertStringStartsWith('http://assets.lelai.com', $data->getWalletHelperUrl());
        $this->assertStringStartsWith('http://assets.lelai.com', $data->getHelperUrl());
    }

    public function testRunDebugLevel3()
    {

        $this->request->setRemote(true);
        $request = config::request();
        $request->setVer('1.0');
        $this->header->setRoute('core.config');
        $this->header->setSource(SourceEnum::CORE);
        $this->header->setCustomerId($this->customerId);
        ToolsAbstract::getRedis()->hSet(Request::REDIS_KEY_DEBUG_DEVICE_TABLE, $this->customerId, 9);
        $rawBody = Message::pack($this->header, $request);
        $this->request->setRawBody($rawBody);
        $response = $this->application->handleRequest($this->request);
        $this->assertNotEmpty($response);
        /** @var ConfigResponse $data */
        /** @var \service\message\common\ResponseHeader $header */
        list($header, $data) = $response;
        $this->assertEquals(0, $header->getCode());
        $this->assertInstanceOf('service\message\core\ConfigResponse', $data);
        $this->assertEquals('4008949580', $data->getCsh());
        $this->assertStringStartsWith('http://assets.lelai.com', $data->getCouponHelperUrl());
        $this->assertStringStartsWith('http://assets.lelai.com', $data->getWalletHelperUrl());
        $this->assertStringStartsWith('http://assets.lelai.com', $data->getHelperUrl());
    }
}