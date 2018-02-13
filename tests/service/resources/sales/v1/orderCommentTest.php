<?php
namespace tests\service\resources\sales\v1;

use common\models\SalesFlatOrder;
use framework\message\Message;
use service\message\common\SourceEnum;
use service\message\sales\CreateOrdersResponse;
use service\resources\sales\v1\createOrders;
use service\resources\sales\v1\orderComment;
use tests\service\ApplicationTest;

/**
 * Created by PhpStorm.
 * User: henryzhu
 * Date: 17-1-20
 * Time: 上午10:49
 */
class orderCommentTest extends ApplicationTest
{
    public function getModel()
    {
        return new orderComment();
    }

    public function testRequest()
    {
        $this->assertInstanceOf('service\message\sales\OrderCommentRequest', orderComment::request());
    }

    public function testResponse()
    {
        $this->assertTrue(orderComment::response());
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
    public function testOrderProcessingReceive($createOrderResponse)
    {
        /** @var CreateOrdersResponse $createOrderResponse */
        $orderId = current($createOrderResponse->getOrderId());
        /** @var SalesFlatOrder $order */
        $order = SalesFlatOrder::find()->where(['entity_id' => $orderId])->one();
        $order->setState(SalesFlatOrder::STATE_PROCESSING,SalesFlatOrder::STATUS_PROCESSING_RECEIVE);
        $order->save();
    }


    /**
     * @depends testRun
     */
    public function testOrderReceiptConfirm($createOrderResponse)
    {
        /** @var CreateOrdersResponse $createOrderResponse */
        $orderId = current($createOrderResponse->getOrderId());
        /** @var SalesFlatOrder $order */
        $order = SalesFlatOrder::find()->where(['entity_id' => $orderId])->one();
        $order->receiptConfirm();
        $order->save();
    }


    /**
     * @depends testRun
     */
    public function testOrderComment($createOrderResponse)
    {
        /** @var CreateOrdersResponse $createOrderResponse */
        $orderId = current($createOrderResponse->getOrderId());
        $this->request->setRemote(true);
        $request = orderComment::request();
        $request->setCustomerId($this->customerId);
        $request->setAuthToken($this->authToken);
        $request->setOrderId($orderId);
        $request->setWholesalerId(1);
        $request->setDelivery(5);
        $request->setQuality(5);
        $request->setComment('not that bad!');
        $this->header->setRoute('sales.orderComment');
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