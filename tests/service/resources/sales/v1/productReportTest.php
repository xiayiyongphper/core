<?php
namespace tests\service\resources\sales\v1;

use framework\message\Message;
use service\message\common\Order;
use service\message\common\Pagination;
use service\message\common\SourceEnum;
use service\message\sales\ProductReportResponse;
use service\resources\sales\v1\productReport;
use tests\service\ApplicationTest;

/**
 * Created by PhpStorm.
 * User: henryzhu
 * Date: 17-1-20
 * Time: 上午11:22
 */
class productReportTest extends ApplicationTest
{
    public function getModel()
    {
        return new productReport();
    }

    public function testModel()
    {
        $this->assertInstanceOf('service\resources\sales\v1\productReport', $this->model);
    }

    public function testRequest()
    {
        $this->assertInstanceOf('service\message\sales\ProductReportRequest', productReport::request());
    }

    public function testResponse()
    {
        $this->assertInstanceOf('service\message\sales\ProductReportResponse', productReport::response());
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
        $request = productReport::request();
        $filters = [
            ['key' => 'customer_id', 'value' => $this->customerId],
//            ['key' => 'wholesaler_id', 'value' => $this->wholesalerId],
            ['key' => 'time_range', 'value' => '2016-01-01TO2019-01-01']
        ];
        $requestData = [
            'filters'=>$filters
        ];
        $request->setFrom($requestData);
        $pagination = new Pagination();
        $pagination->setPage(1);
        $request->setPagination($pagination);
        $this->header->setRoute('sales.productReport');
        $this->header->setSource(SourceEnum::CORE);
        $rawBody = Message::pack($this->header, $request);
        $this->request->setRawBody($rawBody);
        $response = $this->application->handleRequest($this->request);
        $this->assertNotEmpty($response);
        /** @var ProductReportResponse $data */
        /** @var \service\message\common\ResponseHeader $header */
        list($header, $data) = $response;
        $this->assertEquals(0, $header->getCode());
        $this->assertInstanceOf('service\message\sales\ProductReportResponse', $data);
    }

    public function testRun1()
    {
        $this->request->setRemote(true);
        $request = productReport::request();
        $filters = [
            ['key' => 'customer_id', 'value' => $this->customerId],
            ['key' => 'wholesaler_id', 'value' => $this->wholesalerId],
            ['key' => 'category_id', 'value' => 127],
            ['key' => 'category_level', 'value' => 1],
        ];
        $requestData = [
            'filters'=>$filters
        ];
        $request->setFrom($requestData);
        $pagination = new Pagination();
        $pagination->setPage(1);
        $request->setPagination($pagination);
        $this->header->setRoute('sales.productReport');
        $this->header->setSource(SourceEnum::CORE);
        $rawBody = Message::pack($this->header, $request);
        $this->request->setRawBody($rawBody);
        $response = $this->application->handleRequest($this->request);
        $this->assertNotEmpty($response);
        /** @var ProductReportResponse $data */
        /** @var \service\message\common\ResponseHeader $header */
        list($header, $data) = $response;
        $this->assertEquals(31002, $header->getCode());
    }

    public function testRun2()
    {
        $this->request->setRemote(FALSE);
        $request = productReport::request();
        $filters = [
            ['key' => 'customer_id', 'value' => $this->customerId],
            ['key' => 'wholesaler_id', 'value' => $this->wholesalerId],
            ['key' => 'category_id', 'value' => 127],
            ['key' => 'category_level', 'value' => 1],
            ['key' => 'time_range', 'value' => '2016-01-01TO2019-01-01']
        ];
        $requestData = [
            'filters' => $filters
        ];
        $request->setFrom($requestData);
        $pagination = new Pagination();
        $pagination->setPage(1);
        $request->setPagination($pagination);
        $this->header->setRoute('sales.productReport');
        $this->header->setSource(SourceEnum::CORE);
        $rawBody = Message::pack($this->header, $request);
        $this->request->setRawBody($rawBody);
        $response = $this->application->handleRequest($this->request);
        $this->assertNotEmpty($response);
        /** @var ProductReportResponse $data */
        /** @var \service\message\common\ResponseHeader $header */
        list($header, $data) = $response;
        $this->assertEquals(0, $header->getCode());
    }

    public function testRun3()
    {
        $this->request->setRemote(FALSE);
        $request = productReport::request();
        $filters = [
            ['key' => 'customer_id', 'value' => $this->customerId],
            ['key' => 'wholesaler_id', 'value' => $this->wholesalerId],
            ['key' => 'category_id', 'value' => 127],
            ['key' => 'category_level', 'value' => 2],
            ['key' => 'time_range', 'value' => '2016-01-01TO2019-01-01']
        ];
        $requestData = [
            'filters' => $filters
        ];
        $request->setFrom($requestData);
        $pagination = new Pagination();
        $pagination->setPage(-1);
        $request->setPagination($pagination);
        $this->header->setRoute('sales.productReport');
        $this->header->setSource(SourceEnum::CORE);
        $rawBody = Message::pack($this->header, $request);
        $this->request->setRawBody($rawBody);
        $response = $this->application->handleRequest($this->request);
        $this->assertNotEmpty($response);
        /** @var ProductReportResponse $data */
        /** @var \service\message\common\ResponseHeader $header */
        list($header, $data) = $response;
        $this->assertEquals(0, $header->getCode());
    }

    public function testRun4()
    {
        $this->request->setRemote(FALSE);
        $request = productReport::request();
        $filters = [
            ['key' => 'customer_id', 'value' => $this->customerId],
            ['key' => 'wholesaler_id', 'value' => $this->wholesalerId],
            ['key' => 'category_id', 'value' => 127],
            ['key' => 'category_level', 'value' => 3],
            ['key' => 'time_range', 'value' => '2016-01-01TO2019-01-01']
        ];
        $requestData = [
            'filters' => $filters
        ];
        $request->setFrom($requestData);
        $pagination = new Pagination();
        $pagination->setPage(100);
        $request->setPagination($pagination);
        $this->header->setRoute('sales.productReport');
        $this->header->setSource(SourceEnum::CORE);
        $rawBody = Message::pack($this->header, $request);
        $this->request->setRawBody($rawBody);
        $response = $this->application->handleRequest($this->request);
        $this->assertNotEmpty($response);
        /** @var ProductReportResponse $data */
        /** @var \service\message\common\ResponseHeader $header */
        list($header, $data) = $response;
        $this->assertEquals(0, $header->getCode());
    }
}