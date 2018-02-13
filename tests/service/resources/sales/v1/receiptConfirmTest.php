<?php
namespace tests\service\resources\sales\v1;

use common\models\SalesFlatOrder;
use framework\message\Message;
use service\message\common\SourceEnum;
use service\message\sales\CreateOrdersResponse;
use service\resources\sales\v1\createOrders;
use service\resources\sales\v1\orderComment;
use service\resources\sales\v1\receiptConfirm;
use tests\service\ApplicationTest;

/**
 * Created by PhpStorm.
 * User: henryzhu
 * Date: 17-1-20
 * Time: 上午10:49
 */
class receiptConfirmTest extends ApplicationTest
{
    public function getModel()
    {
        return new receiptConfirm();
    }

    public function testRequest()
    {
        $this->assertInstanceOf('service\message\common\OrderAction', receiptConfirm::request());
    }

    public function testResponse()
    {
        $this->assertInstanceOf('service\message\common\Order', receiptConfirm::response());
    }

    public function testHeader()
    {
        $this->assertInstanceOf('service\message\common\Header', $this->header);
    }

    public function testFrameworkRequest()
    {
        $this->assertInstanceOf('framework\Request', $this->request);
    }

    public function prepareOrderForReceiptConfirm()
    {
        $order = SalesFlatOrder::findOne(['entity_id' => $this->receiptConfirmOrderId]);
        $order->setState(SalesFlatOrder::STATE_PROCESSING, SalesFlatOrder::STATUS_PROCESSING_RECEIVE);
        $order->save();
    }


    public function testOrderReceiptConfirm()
    {
        $this->prepareOrderForReceiptConfirm();
        $this->request->setRemote(true);
        $request = receiptConfirm::request();
        $request->setCustomerId($this->customerId);
        $request->setAuthToken($this->authToken);
        $request->setOrderId($this->receiptConfirmOrderId);
        $request->setWholesalerId(1);
        $this->header->setRoute('sales.receiptConfirm');
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