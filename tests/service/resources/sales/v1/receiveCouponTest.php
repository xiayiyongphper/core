<?php
namespace tests\service\resources\sales\v1;

use common\models\salesrule\UserCoupon;
use framework\message\Message;
use service\message\common\SourceEnum;
use service\resources\sales\v1\receiveCoupon;
use tests\service\ApplicationTest;

/**
 * Created by PhpStorm.
 * User: henryzhu
 * Date: 17-1-20
 * Time: 上午10:49
 */
class receiveCouponTest extends ApplicationTest
{
    public function getModel()
    {
        return new receiveCoupon();
    }

    public function testRequest()
    {
        $this->assertInstanceOf('service\message\core\ReceiveCouponRequest', receiveCoupon::request());
    }

    public function testResponse()
    {
        $this->assertTrue(receiveCoupon::response());
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
        $request = receiveCoupon::request();
        $request->setCustomerId($this->customerId);
        $request->setAuthToken($this->authToken);
        $request->setCoupon('11111');
//        $request->setRuleId(133);
        //删除已有的优惠券，重新领取
        $coupon = UserCoupon::findOne(['customer_id' => $this->customerId, 'rule_id' => 133]);
        $coupon->delete();
        $this->header->setRoute('sales.receiveCoupon');
        $this->header->setSource(SourceEnum::CORE);
        $rawBody = Message::pack($this->header, $request);
        $this->request->setRawBody($rawBody);
        $response = $this->application->handleRequest($this->request);
        $this->assertNotEmpty($response);
        /** @var bool $data */
        /** @var \service\message\common\ResponseHeader $header */
        list($header, $data) = $response;
        if ($header->getCode() > 0) {
            $this->assertEquals(38003, $header->getCode());
        } else {
            $this->assertEquals(0, $header->getCode());
        }

    }
}