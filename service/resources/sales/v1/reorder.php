<?php
namespace service\resources\sales\v1;

use common\models\SalesFlatOrder;
use service\components\Proxy;
use service\components\Tools;
use service\message\common\Header;
use service\message\common\OrderAction;
use service\message\common\SourceEnum;
use service\message\common\UniversalResponse;
use service\message\customer\UpdateCartItemsRequest;


use service\resources\Exception;
use service\resources\ResourceAbstract;


/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2016/1/21
 * Time: 15:09
 */
class reorder extends ResourceAbstract
{
    const DEFAULT_PAGE_SIZE = 10;

    public function run($data)
    {

        /** @var OrderAction $request */
        $request = self::request();
        $request->parseFromString($data);
        $this->_initCustomer($request);
        //接口验证用户
        $header = new Header();
        $header->setSource(SourceEnum::CORE);
        $header->setTraceId($this->getTraceId());
        $header->setVersion(1);
        $header->setRoute('merchant.updateItems');
        $order = SalesFlatOrder::find()
            ->joinWith('item')
            ->where(['entity_id'=>$request->getOrderId()])
            ->asArray()
            ->one();
        if(!$order){
            Exception::orderNotExisted();
        }
        $updateCartItemsRequest = new UpdateCartItemsRequest();
        $products = [];
        $requestData = [
            'auth_token' => $request->getAuthToken(),
            'customer_id' => $request->getCustomerId(),
        ];
        foreach($order['item'] as $item){
            $products[] = [
                'wholesaler_id'=>$item['wholesaler_id'],
                'product_id'=>$item['product_id'],
                'num'=>intval($item['qty']),
            ];
        }
        Tools::log($products);
        if (count($products) > 0) {
            $requestData['products'] = $products;
        }
        $updateCartItemsRequest->setFrom(Tools::pb_array_filter($requestData));
        $message = Proxy::sendRequest($header,$updateCartItemsRequest);
        return $message;
    }

    public static function request()
    {
        return new OrderAction();
    }

    public static function response()
    {
        return new UniversalResponse();
    }
}