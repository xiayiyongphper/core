<?php
namespace tests\service\resources\sales\v1;

use framework\message\Message;
use service\message\common\Order;
use service\message\common\SourceEnum;
use service\message\sales\CreateOrdersResponse;
use service\resources\sales\v1\createOrders;
use service\resources\sales\v1\driverOrderDetail;
use service\resources\sales\v1\test;
use tests\service\ApplicationTest;

/**
 * Created by PhpStorm.
 * User: henryzhu
 * Date: 17-1-20
 * Time: 上午10:49
 */
class driverOrderDetailTest extends ApplicationTest
{
    public function getModel()
    {
        return new driverOrderDetail();
    }

    public function testRequest()
    {
        $this->assertInstanceOf('service\message\sales\DriverOrderDetailRequest', driverOrderDetail::request());
    }

    public function testResponse()
    {
        $this->assertInstanceOf('service\message\common\Order', driverOrderDetail::response());
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
        $this->request->setRemote(false);
        $request = createOrders::request();
        $requestData = [
            'customer_id' => $this->customerId,
            'auth_token' => $this->authToken,
            'payment_method' => 3,
            'address' => [
                'name' => 'lala',
                'phone' => '12345678555',
            ],
            'items' => [
                [
                    "wholesaler_id" => 1,
                    "product_id" => 1,
                    "num" => 19
                ],
                [
                    "wholesaler_id" => 1,
                    "product_id" => 2,
                    "num" => 100
                ]
            ]
        ];
        $request->setFrom($requestData);
        $this->header->setRoute('sales.createOrders');
        $this->header->setSource(SourceEnum::CORE);
        $rawBody = Message::pack($this->header, $request);
        $this->request->setRawBody($rawBody);
        $response = $this->application->handleRequest($this->request);
        $this->assertNotEmpty($response);
        /** @var CreateOrdersResponse $data */
        /** @var \service\message\common\ResponseHeader $header */
        list($header, $data) = $response;
        $this->assertEquals(0, $header->getCode());
        $this->assertInstanceOf('service\message\sales\CreateOrdersResponse', $data);
        return $data;
    }

    /**
     * @depends testRun
     */
    public function testDriverOrderDetail($createOrderResponse)
    {
        /** @var CreateOrdersResponse $createOrderResponse */
        $orderId = current($createOrderResponse->getOrderId());
        $this->request->setRemote(false);
        $request = driverOrderDetail::request();
        $request->setOrderId($orderId);
        $this->header->setRoute('sales.driverOrderDetail');
        $this->header->setSource(SourceEnum::CORE);
        $rawBody = Message::pack($this->header, $request);
        $this->request->setRawBody($rawBody);
        $response = $this->application->handleRequest($this->request);
        $this->assertNotEmpty($response);
        /** @var Order $data */
        /** @var \service\message\common\ResponseHeader $header */
        list($header, $data) = $response;
        $this->assertEquals(0, $header->getCode());
    }
}