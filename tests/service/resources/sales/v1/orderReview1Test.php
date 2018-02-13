<?php
namespace tests\service\resources\sales\v1;

use framework\message\Message;
use service\message\common\SourceEnum;
use service\message\sales\OrderReviewResponse;
use service\resources\sales\v1\orderReview1;
use tests\service\ApplicationTest;

/**
 * Created by PhpStorm.
 * User: henryzhu
 * Date: 17-1-17
 * Time: 下午6:02
 */
class orderReview1Test extends ApplicationTest
{
    public function getModel()
    {
        return new orderReview1();
    }

    public function testModel()
    {
        $this->assertInstanceOf('service\resources\sales\v1\orderReview1', $this->model);
    }

    public function testRequest()
    {
        $this->assertInstanceOf('service\message\sales\OrderReviewRequest', orderReview1::request());
    }

    public function testResponse()
    {
        $this->assertInstanceOf('service\message\sales\OrderReviewResponse', orderReview1::response());
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
        $request = orderReview1::request();
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
        $this->header->setRoute('sales.orderReview1');
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