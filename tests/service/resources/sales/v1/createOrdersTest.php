<?php
namespace tests\service\resources\sales\v1;

use common\models\SalesFlatOrder;
use framework\components\ToolsAbstract;
use framework\message\Message;
use service\message\common\Order;
use service\message\common\SourceEnum;
use service\message\sales\CreateOrdersResponse;
use service\resources\sales\v1\cancel;
use service\resources\sales\v1\createOrders;
use service\resources\sales\v1\orderComment;
use service\resources\sales\v1\reorder;
use tests\service\ApplicationTest;

/**
 * Created by PhpStorm.
 * User: henryzhu
 * Date: 17-1-20
 * Time: 上午11:22
 */
class createOrdersTest extends ApplicationTest
{
    public function getModel()
    {
        return new createOrders();
    }

    public function testModel()
    {
        $this->assertInstanceOf('service\resources\sales\v1\createOrders', $this->model);
    }

    public function testRequest()
    {
        $this->assertInstanceOf('service\message\sales\CreateOrdersRequest', createOrders::request());
    }

    public function testResponse()
    {
        $this->assertInstanceOf('service\message\sales\CreateOrdersResponse', createOrders::response());
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
}