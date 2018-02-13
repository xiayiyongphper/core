<?php
namespace service\resources\sales\v1;

use common\models\SalesFlatOrder;
use common\models\SalesFlatOrderAddress;
use common\models\SalesOrderStatus;
use framework\components\Date;

use service\components\Tools;
use service\message\common\Order;

use service\message\sales\OrderDetailRequest;
use service\resources\Exception;
use service\resources\ResourceAbstract;


/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2016/1/21
 * Time: 15:09
 */
class orderDetailBrief extends ResourceAbstract
{
    const DEFAULT_PAGE_SIZE = 10;

    public function run($data)
    {
        /** @var \service\message\sales\OrderDetailRequest $request */
        $request = self::request();
        $request->parseFromString($data);
        if($this->isRemote()){
            $this->_initCustomer($request);
        }
        //用户权限校验
        $response = new Order();
        if (!$request->getOrderId()) {
            Exception::orderNotExisted();
        }
        $order = SalesFlatOrder::find()
         ->joinWith('item')
        ->where(['entity_id'=>$request->getOrderId()])->asArray()->one();
        //Tools::log($order,'wangyang.txt');
        if (!$order['entity_id']) {
            Exception::orderNotExisted();
        }


        $address = SalesFlatOrderAddress::find()->where(['order_id'=>$request->getOrderId()])->asArray()->one();

        $date = new Date();
        /** @var SalesOrderStatus $orderStatus */
        $orderStatus = SalesOrderStatus::find()->where(['status'=>$order['status']])->one();
        $orderData = [
            'order_id' => $order['entity_id'],
            'increment_id' => $order['increment_id'],
            'wholesaler_id' => $order['wholesaler_id'],
            'wholesaler_name' => $order['wholesaler_name'],
            'comment' => $order['customer_note'],
            'status' => $order['status'],
            'state' => $order['state'],
            'status_label' => $orderStatus->label,
            'payment_method' => $order['payment_method'],
            'created_at' => $date->date('Y-m-d H:i:s', $order['created_at']),
            'name'=>$address['name'],
            'phone' => $address['phone'],
            'address' => $address['address'],
            'promotions' => isset($order['promotions'])?unserialize($order['promotions']):'',
        ];
        $orderData = array_filter($orderData);
        $subtotal = 0;
        $totalQty = 0;
        $orderItems = [];
        foreach($order['item'] as $_orderItem){
            $orderItems[] = array_filter([
                'item_id'=>$_orderItem['item_id'],
                'product_id'=>$_orderItem['product_id'],
                'name'=>$_orderItem['name'],
                'price'=>$_orderItem['price'],
                'qty'=>$_orderItem['qty'],
                'image'=>$_orderItem['image'],
                'barcode'=>$_orderItem['barcode'],
                'specification'=>$_orderItem['specification'],
                'row_total'=>$_orderItem['row_total'],
                'original_price'=>$_orderItem['original_price'],
                'tags' => isset($_orderItem['tags'])?unserialize($_orderItem['tags']):array(),
                'receipt'=>$_orderItem['receipt'],
            ]);
            $subtotal += $_orderItem['row_total'];
            $totalQty += $_orderItem['qty'];
        }
        if(count($orderItems)>0){
            $orderData['items'] = $orderItems;
        }
        $orderData['totals'] = [
            'base_total' => $subtotal,
            'shipping_amount' => $order['shipping_amount'],
            'discount_amount' => $order['discount_amount'],
            'coupon_discount_amount' => $order['coupon_discount_amount'],
            'grand_total' => $order['grand_total'],
            'total_qty' => $totalQty,
            'balance' => $order['balance'],
			'receipt_total'=>$order['receipt_total'],
        ];
        $response->setFrom(Tools::pb_array_filter($orderData));
        return $response;
    }

    public static function request()
    {
        return new OrderDetailRequest();
    }

    public static function response()
    {
        return new Order();
    }
}
