<?php

namespace service\models;

use framework\components\TStringFuncFactory;
use framework\message\Message;
use service\components\Tools;
use service\message\common\Header;
use service\message\common\OrderAction;
use service\message\common\SourceEnum;
use service\message\contractor\ContractorAuthenticationRequest;
use service\message\core\ConfigRequest;
use service\message\core\getHomeActivityRequest;
use service\message\core\HomeRequest;
use service\message\core\orderManageRequest;
use service\message\core\orderManageResponse;
use service\message\core\ReceiveCouponRequest;
use service\message\customer\LoginRequest;
use service\message\merchant\getAreaCategoryRequest;
use service\message\sales\CreateOrdersRequest;
use service\message\sales\getContractorCouponHistoryRequest;
use service\message\sales\GetCumulativeReturnDetailRequest;
use service\message\sales\getCustomerCouponListRequest;
use service\message\sales\OrderCollectionRequest;
use service\message\sales\OrderDetailRequest;
use service\message\sales\OrderNumberRequest;
use service\message\sales\OrderReviewRequest;
use service\message\sales\OrderStatusHistoryRequest;
use service\models\client\ClientAbstract;
use service\message\sales\GetCustomerFirstOrderRequest;
use service\message\sales\OrderCommentTagRequest;
use service\message\sales\OrderCommentRequest;
use service\message\core\AllSaleRuleRequest;
use service\message\core\AllSaleRuleResponse;

/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2016/1/8
 * Time: 12:01
 */
class SOAClient extends ClientAbstract
{
    protected $_responseClass;
    protected $_customerId = 1089;
    protected $_authToken = '1sGbxzEFSH2vdEkp';

    public function test()
    {
        $loginRequest = new LoginRequest();
        $loginRequest->setUsername('henryzhu');
        $loginRequest->setPassword(md5('111111'));

        $header = new Header();
        $header->setSource(SourceEnum::CORE);
        $header->setVersion(1);
        $header->setRoute('customers.login');
        $this->send(Message::pack($header, $loginRequest));
    }

    public function home()
    {
        $this->_responseClass = 'service\message\core\HomeResponse';
        $homeReq = new HomeRequest();
        $homeReq->setCustomerId(35);
        $homeReq->setAuthToken($this->_authToken);
        $header = new Header();
        $header->setSource(SourceEnum::CORE);
        $header->setVersion(1);
        $header->setRoute('core.home');
        $data = Message::pack($header, $homeReq);
        Tools::logToFile($data, 'home.dat');
        $this->send($data);
    }

    public function orderCollection()
    {
        $this->_responseClass = 'service\message\sales\OrderCollectionResponse';
        $request = new OrderCollectionRequest();
        $request->setCustomerId($this->_customerId);
        $request->setAuthToken($this->_authToken);
        $request->setState('all');
        $request->setPage(30);
        $header = new Header();
        $header->setSource(SourceEnum::CORE);
        $header->setVersion(1);
        $header->setRoute('sales.orderCollection');
        $this->send(Message::pack($header, $request));
    }

    public function orderDetail()
    {
        $this->_responseClass = 'service\message\common\Order';
        $request = new OrderDetailRequest();
        $request->setCustomerId(1106);
        $request->setAuthToken('KboAeItcIrMDbWBI');
        $request->setOrderId(234971);
        $header = new Header();
        $header->setSource(SourceEnum::CORE);
        $header->setVersion(1);
        $header->setRoute('sales.orderDetail');
        $this->send(Message::pack($header, $request));
    }

    public function orderStatusHistory()
    {
        $this->_responseClass = 'service\message\common\Order';
        $request = new OrderStatusHistoryRequest();
        $request->setCustomerId($this->_customerId);
        $request->setAuthToken($this->_authToken);
        $request->setOrderId(601431);
        $header = new Header();
        $header->setSource(SourceEnum::CORE);
        $header->setVersion(1);
        $header->setRoute('sales.orderStatusHistory');
        $this->send(Message::pack($header, $request));
    }

    public function orderReview()
    {
        $this->_responseClass = 'service\message\sales\OrderReviewResponse';
        $requestData = [
            'customer_id' => $this->_customerId,
            'auth_token' => $this->_authToken,
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
        $request = new OrderReviewRequest();
        $request->setFrom($requestData);
        $header = new Header();
        $header->setSource(SourceEnum::CORE);
        $header->setVersion(1);
        $header->setRoute('sales.orderReview');
        $this->send(Message::pack($header, $request));
    }

    public function orderReview1()
    {
        $this->_responseClass = 'service\message\sales\OrderReviewResponse';
        $requestData = [
            'customer_id' => $this->_customerId,
            'auth_token' => $this->_authToken,
            'items' => [
//                [
//                    'wholesaler_id' => 3,
//                    'product_id' => 114,
//                    'num' => 2,
//                ],
                [
                    'wholesaler_id' => 5,
                    'product_id' => 3,
                    'num' => 10,
                ],
//                [
//                    'wholesaler_id' => 33,
//                    'product_id' => 4688,
//                    'num' => 5,
//                ],
//                [
//                    'wholesaler_id' => 33,
//                    'product_id' => 2147483650,
//                    'num' => 2,
//                    'type' => 1
//                ],
//                [
//                    'wholesaler_id' => 33,
//                    'product_id' => 2147483707,
//                    'num' => 2,
//                    'type' => 1
//                ],
            ],
//            'coupon_id' => 52
        ];
        $request = new OrderReviewRequest();
        $request->setFrom($requestData);
        $header = new Header();
        $header->setSource(SourceEnum::CORE);
        $header->setVersion(1);
        $header->setRoute('sales.orderReview1');
        $this->send(Message::pack($header, $request));
    }

    public function createOrders()
    {
        $this->_responseClass = 'service\message\sales\CreateOrdersResponse';
        $requestData = [
            'customer_id' => $this->_customerId,
            'auth_token' => $this->_authToken,
            'payment_method' => 3,
            'address' => [
                'name' => 'lala',
                'phone' => '12345678555',
            ],
            'items' => [
                [
                    "wholesaler_id" => 1,
                    "product_id" => 3,
                    "num" => 1
                ],
                [
                    "wholesaler_id" => 1,
                    "product_id" => 4,
                    "num" => 1
                ]
            ]
        ];
        $request = new CreateOrdersRequest();
        $request->setFrom($requestData);
        $header = new Header();
        $header->setSource(SourceEnum::CORE);
        $header->setVersion(1);
        $header->setRoute('sales.createOrders');
        $this->send(Message::pack($header, $request));
    }

    public function createOrders1()
    {
        $this->_responseClass = 'service\message\sales\CreateOrdersResponse';
        $requestData = [
            'customer_id' => $this->_customerId,
            'auth_token' => $this->_authToken,
            'payment_method' => 3,
            'address' => [
                'name' => 'lala',
                'phone' => '14745678555',
            ],
            'items' => [
                [
                    "wholesaler_id" => 3,
                    "product_id" => 5,
                    "num" => 10
                ],
                [
                    "wholesaler_id" => 3,
                    "product_id" => 6,
                    "num" => 2
                ]
            ]
        ];
        $request = new CreateOrdersRequest();
        $request->setFrom($requestData);
        $header = new Header();
        $header->setSource(SourceEnum::CORE);
        $header->setVersion(1);
        $header->setAppVersion('V2.9');
        $header->setDeviceId(md5(rand(100000,999999)));
        $header->setRoute('sales.createOrders1');
        $this->send(Message::pack($header, $request));
    }

    public function orderCancel()
    {
        $this->_responseClass = 'service\message\common\Order';
        $request = new OrderAction();
        $request->setCustomerId($this->_customerId);
        $request->setAuthToken($this->_authToken);
        $request->setOrderId(234997);
        $header = new Header();
        $header->setSource(SourceEnum::CORE);
        $header->setVersion(1);
        $header->setRoute('sales.cancel');
        $this->send(Message::pack($header, $request));
    }

    public function receiptConfirmPartial()
    {
        $this->_responseClass = 'service\message\common\Order';
        $request = new OrderAction();
        $request->setCustomerId($this->_customerId);
        $request->setAuthToken($this->_authToken);
        $request->setWholesalerId(25);
        $request->setOrderId(191);
        $request->setPartial(true);
        $data = [
            'products' => [
                ['product_id' => 34],
                ['product_id' => 72],
            ]
        ];
        $request->setFrom($data);
        $header = new Header();
        $header->setSource(SourceEnum::CORE);
        $header->setVersion(1);
        $header->setRoute('sales.receiptConfirmPartial');
        $this->send(Message::pack($header, $request));
    }

    public function receiveCoupon()
    {
        $request = new ReceiveCouponRequest();
        $request->setCustomerId(35);
        $request->setAuthToken('KBovpuxTtPUbhq28');
        $request->setRuleId(140);
        $header = new Header();
        $header->setSource(SourceEnum::CORE);
        $header->setVersion(1);
        $header->setRoute('sales.receiveCoupon');
        $this->send(Message::pack($header, $request));
    }

    public function receiptConfirm()
    {
        $this->_responseClass = 'service\message\common\Order';
        $request = new OrderAction();
        $request->setCustomerId(35);
        $request->setAuthToken('KBovpuxTtPUbhq28');
        $request->setOrderId(234689);
        $header = new Header();
        $header->setSource(SourceEnum::CORE);
        $header->setVersion(1);
        $header->setRoute('sales.receiptConfirm');
        $this->send(Message::pack($header, $request));
    }

    public function getCumulativeReturnDetail()
    {
        $this->_responseClass = 'service\message\sales\GetCumulativeReturnDetailResponse';
        $request = new GetCumulativeReturnDetailRequest();
        $request->setCustomerId(35);
        $request->setAuthToken('KBovpuxTtPUbhq28');
        $request->setType(3);
        $header = new Header();
        $header->setSource(SourceEnum::CORE);
        $header->setVersion(1);
        $header->setRoute('sales.GetCumulativeReturnDetail');
        $this->send(Message::pack($header, $request));
    }

    public function getCumulativeReturnDetail2()
    {
        $this->_responseClass = 'service\message\sales\GetCumulativeReturnDetailResponse';
        $request = new GetCumulativeReturnDetailRequest();
        $request->setCustomerId(35);
        $request->setAuthToken('KBovpuxTtPUbhq28');
        $request->setType(3);
        $header = new Header();
        $header->setSource(SourceEnum::CORE);
        $header->setVersion(1);
        $header->setRoute('sales.GetCumulativeReturnDetail2');
        $this->send(Message::pack($header, $request));
    }

    public function revokeCancel()
    {
        $this->_responseClass = 'service\message\common\Order';
        $request = new OrderAction();
        $request->setCustomerId($this->_customerId);
        $request->setAuthToken($this->_authToken);
        $request->setOrderId(601431);
        $header = new Header();
        $header->setSource(SourceEnum::CORE);
        $header->setVersion(1);
        $header->setRoute('sales.revokeCancel');
        $this->send(Message::pack($header, $request));
    }

    public function decline()
    {
        $this->_responseClass = 'service\message\common\Order';
        $request = new OrderAction();
        $request->setCustomerId($this->_customerId);
        $request->setAuthToken($this->_authToken);
        $request->setOrderId(601431);
        $header = new Header();
        $header->setSource(SourceEnum::CORE);
        $header->setVersion(1);
        $header->setRoute('sales.decline');
        $this->send(Message::pack($header, $request));
    }

    public function reorder()
    {
        $this->_responseClass = '';
        $request = new OrderAction();
        $request->setCustomerId($this->_customerId);
        $request->setAuthToken($this->_authToken);
        $request->setOrderId(601431);
        $header = new Header();
        $header->setSource(SourceEnum::CORE);
        $header->setVersion(1);
        $header->setRoute('sales.reorder');
        $this->send(Message::pack($header, $request));
    }

    public function config()
    {
        $this->_responseClass = 'service\message\core\ConfigResponse';
        $request = new ConfigRequest();
        $request->setVer(1);
        $request->setSystem(1);
        $request->setChannel(1);
        $header = new Header();
        $header->setSource(SourceEnum::CORE);
        $header->setVersion(1);
        $header->setDeviceId('a16022150505250505');
        $header->setRoute('core.config');
        $this->send(Message::pack($header, $request));
    }

    public function homeActivity()
    {
        $this->_responseClass = 'service\message\core\getHomeActivityResponse';
        $request = new getHomeActivityRequest();
        $request->setCustomerId($this->_customerId);
        $request->setAuthToken($this->_authToken);
        $header = new Header();
        $header->setSource(SourceEnum::ANDROID_SHOP);
        $header->setVersion(1);
        $header->setAppVersion('2.9');
        $header->setDeviceId('a16022150505250505');
        $header->setRoute('core.getHomeActivity');
        $this->send(Message::pack($header, $request));
    }


    public function getCategory()
    {
        $this->_responseClass = 'service\message\common\CategoryNode';
        $request = new getAreaCategoryRequest();
        $request->setCustomerId($this->_customerId);
        $request->setAuthToken($this->_authToken);
        $header = new Header();
        $header->setSource(SourceEnum::CORE);
        $header->setVersion(1);
        $header->setRoute('core.getCategory');
        $this->send(Message::pack($header, $request));
    }

    public function systemMessage()
    {
        swoole_timer_tick(1, function () {
            $data = [
                'class' => 'service',
                'method' => 'name',
                'time' => time(),
            ];
            $this->send(Message::packJson($data));
        });
    }

    public function getAvailableCityList()
    {
        $this->_responseClass = 'service\message\core\AvailableCityListResponse';
        $header = new Header();
        $header->setSource(SourceEnum::CORE);
        $header->setVersion(1);
        $header->setRoute('core.getAvailableCityList');
        $this->send(Message::pack($header, false));
    }

    public function orderManage()
    {
        $this->_responseClass = 'service\message\contractor\ManageResponse';
        $request = new ContractorAuthenticationRequest();
        $request->setContractorId(17);
        $request->setAuthToken('BDEbhnbGNUTbReEG');
        $request->setCity(441800);

        $header = new Header();
        $header->setSource(SourceEnum::CORE);
        $header->setVersion(1);
        $header->setRoute('sales.orderManageEntry');
        $this->send(Message::pack($header, $request));
    }

    public function orderCountStatus()
    {
        $this->_responseClass = 'service\message\sales\OrderNumberResponse';
        $request = new OrderNumberRequest();
        $request->setCustomerId(1068);
        $request->setAuthToken('4nBzrP1MRQ1B3CTQ');

        $header = new Header();
        $header->setSource(SourceEnum::CORE);
        $header->setVersion(1);
        $header->setRoute('sales.orderCountStatus');
        $this->send(Message::pack($header, $request));
    }

    public function orderCommentTag()
    {
        //订单评价标签列表
        $this->_responseClass = 'service\message\sales\OrderCommentTagResponse';
        $request = new OrderCommentTagRequest();
        $request->setCustomerId(1068);
        $request->setAuthToken('4nBzrP1MRQ1B3CTQ');

        $header = new Header();
        $header->setSource(SourceEnum::CORE);
        $header->setVersion(1);
        $header->setRoute('sales.orderCommentTag');
        $this->send(Message::pack($header, $request));
    }

    public function orderComment2()
    {
        //订单评价
        $this->_responseClass = '';
        $request = new OrderCommentRequest();
        $request->setCustomerId(1068);
        $request->setAuthToken('4nBzrP1MRQ1B3CTQ');
        $request->setWholesalerId(2);
        $request->setOrderId(191);
        $request->setQuality(3);
        $request->setDelivery(4);
        $request->setComment('服务态度挺好的');
        $request->appendQualityTag(2);
        $request->appendQualityTag(3);
        $request->appendDeliveryTag(25);
        $request->appendDeliveryTag(26);
        $request->appendDeliveryTag(60);
        $request->appendDeliveryTag(62);
        $request->appendDeliveryTag(64);

        $header = new Header();
        $header->setSource(SourceEnum::CORE);
        $header->setVersion(1);
        $header->setRoute('sales.orderComment2');
        $this->send(Message::pack($header, $request));
    }

    public function orderCommentReview()
    {
        //查看订单评价
        $this->_responseClass = 'service\message\sales\OrderCommentReviewResponse';
        $request = new OrderCommentReviewRequest();
        $request->setCustomerId(1068);
        $request->setAuthToken('4nBzrP1MRQ1B3CTQ');
        $request->setWholesalerId(2);
        $request->setOrderId(191);

        $header = new Header();
        $header->setSource(SourceEnum::CORE);
        $header->setVersion(1);
        $header->setRoute('sales.orderCommentReview');
        $this->send(Message::pack($header, $request));
    }

    public function allSaleRule()
    {
        //获取供应商全部优惠信息
        $this->_responseClass = 'service\message\core\AllSaleRuleResponse';
        $request = new AllSaleRuleRequest();
        $request->appendWholesalerId(31);

        $header = new Header();
        $header->setSource(SourceEnum::CORE);
        $header->setVersion(1);
        $header->setRoute('sales.allSaleRule');
        $this->send(Message::pack($header, $request));
    }

    public function getCustomerFirstOrder(){
        $this->_responseClass = 'service\message\common\Order';
        $request = new GetCustomerFirstOrderRequest();
        $request->setCustomerId(1106);
        //$request->setContractorId(28);
        $request->setCity(441200);

        $header = new Header();
        $header->setSource(SourceEnum::CORE);
        $header->setVersion(1);
        $header->setRoute('sales.customerFirstOrder');
        $this->send(Message::pack($header, $request));
    }

    public function getContractorCouponSendHistory(){
        $this->_responseClass = 'service\message\sales\getContractorCouponHistoryResponse';
        $request = new getContractorCouponHistoryRequest();
        $request->setContractorId(3);
        $request->setAuthToken('WssBBlfWlriQ67eD');
//        $request->setDate('2017-12');
        $request->setFilterContractorId(3);
        $request->setUsed(1);

        $header = new Header();
        $header->setSource(SourceEnum::IOS_CONTRACTOR);
        $header->setVersion(1);
        $header->setRoute('sales.getContractorCouponSendHistory');
        $this->send(Message::pack($header, $request));
    }

    public function onConnect($client)
    {
        echo "client connected" . PHP_EOL;
        $this->getContractorCouponSendHistory();
        //$this->createOrders();
//        $this->home();
//        $this->getAvailableCityList();
//        $this->orderDetail();
//        $this->orderCollection();
//        $obj = $this;
//        swoole_timer_tick(100,function ()use($obj){
//            $obj->orderReview();
//        });

//        $this->orderStatusHistory();
//        $this->orderCancel();
//        $this->revokeCancel();
//        $this->decline();
//        $this->reorder();
//        $this->systemMessage();
//        $this->homeActivity();
//        $this->orderCountStatus();
//        $this->orderCommentTag();
//        $this->orderComment2();
//        $this->orderCommentReview();
//	      $this->receiptConfirm();
//        $this->getCumulativeReturnDetail();
//        $this->getCumulativeReturnDetail2();
//        $this->allSaleRule();
//        $this->createOrders1();
//        $this->receiveCoupon();
//        $this->orderReview1();
//        $this->orderDetail();
        //$this->getCustomerFirstOrder();
    }

    public function onReceive($client, $data)
    {
        $message = new Message();
        $message->unpackResponse($data);
        $responseClass = $this->_responseClass;

        if ($message->getHeader()->getCode() > 0) {
            echo sprintf('程序执行异常：%s', $message->getHeader()->getMsg()) . PHP_EOL;
        } else {
            if (TStringFuncFactory::create()->strlen($message->getPackageBody()) > 0) {
                $response = new $responseClass();
                $response->parseFromString($message->getPackageBody());
                //print_r($responseClass);
//                print_r($message->getPackageBody());
                echo PHP_EOL;
                print_r($response->toArray());
            } else {
                print_r('返回值为空');
            }
        }
    }
}