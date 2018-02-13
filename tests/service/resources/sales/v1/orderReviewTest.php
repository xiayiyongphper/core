<?php
namespace tests\service\resources\sales\v1;

use framework\message\Message;
use service\message\common\SourceEnum;
use service\message\sales\OrderReviewResponse;
use service\resources\sales\v1\orderReview;
use tests\service\ApplicationTest;

/**
 * Created by PhpStorm.
 * User: henryzhu
 * Date: 17-1-17
 * Time: 下午6:02
 */
class orderReviewTest extends ApplicationTest
{
    public function getModel()
    {
        return new orderReview();
    }

    public function testModel()
    {
        $this->assertInstanceOf('service\resources\sales\v1\orderReview', $this->model);
    }

    public function testRequest()
    {
        $this->assertInstanceOf('service\message\sales\OrderReviewRequest', orderReview::request());
    }

    public function testResponse()
    {
        $this->assertInstanceOf('service\message\sales\OrderReviewResponse', orderReview::response());
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
        $request = orderReview::request();
        $requestData = [
            'customer_id' => $this->customerId,
            'auth_token' => $this->authToken,
            'items' => [
                [
                    "wholesaler_id" => 3,
                    "product_id" => 1,
                    "num" => 190
                ],
                [
                    "wholesaler_id" => 3,
                    "product_id" => 2,
                    "num" => 100
                ]
            ]
        ];
        $request->setFrom($requestData);
        $this->header->setRoute('sales.orderReview');
        $this->header->setSource(SourceEnum::CORE);
        $rawBody = Message::pack($this->header, $request);
        $this->request->setRawBody($rawBody);
        $response = $this->application->handleRequest($this->request);
        $this->assertNotEmpty($response);
        /** @var OrderReviewResponse $data */
        /** @var \service\message\common\ResponseHeader $header */
        list($header, $data) = $response;
        $this->assertEquals(0, $header->getCode());
        $this->assertInstanceOf('service\message\sales\OrderReviewResponse', $data);
    }

    public function testRun1()
    {
        $this->request->setRemote(true);
        $request = orderReview::request();
        $requestData = [
            'customer_id' => $this->customerId,
            'auth_token' => $this->authToken,
//            'balance'=>1,
            'items' => [
                [
                    "wholesaler_id" => 25,
                    "product_id" => 3092,
                    "num" => 200
                ],
                [
                    "wholesaler_id" => 25,
                    "product_id" => 3093,
                    "num" => 100
                ]
            ]
        ];
        $request->setFrom($requestData);
        $request->setBalance(1.0);
        $this->header->setRoute('sales.orderReview');
        $this->header->setSource(SourceEnum::CORE);
        $rawBody = Message::pack($this->header, $request);
        $this->request->setRawBody($rawBody);
        $response = $this->application->handleRequest($this->request);
        $this->assertNotEmpty($response);
        /** @var OrderReviewResponse $data */
        /** @var \service\message\common\ResponseHeader $header */
        list($header, $data) = $response;
        $this->assertEquals(0, $header->getCode());
        $this->assertInstanceOf('service\message\sales\OrderReviewResponse', $data);
    }
}