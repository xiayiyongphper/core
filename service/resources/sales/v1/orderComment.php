<?php
namespace service\resources\sales\v1;

use common\models\SalesFlatOrder;
use common\models\SalesFlatOrderAddress;
use common\models\SalesFlatOrderComment;
use common\models\SalesOrderStatus;
use framework\components\Date;
use service\components\Proxy;
use service\components\Tools;
use service\message\common\Order;
use service\message\customer\CustomerResponse;
use service\message\sales\OrderCommentRequest;
use service\message\sales\OrderDetailRequest;
use service\resources\Exception;
use service\resources\ResourceAbstract;


/**
 * Class orderComment
 * @package service\resources\sales\v1
 */
class orderComment extends ResourceAbstract
{
    const DEFAULT_PAGE_SIZE = 10;

    public function run($data)
    {
        /** @var \service\message\sales\OrderCommentRequest $request */
        $request = self::request();
        $request->parseFromString($data);
        $orderId = $request->getOrderId();
        $customer = $this->_initCustomer($request);
        $customerId = $customer->getCustomerId();
        if (!$customerId) {
            Exception::customerNotExisted();
        }
        /** @var SalesFlatOrder $order */
        $order = SalesFlatOrder::find()->where(['entity_id' => $orderId])->one();
        if (!$order||!$order->getPrimaryKey()) {
            Exception::orderNotExisted();
        }

        if($order->comment()->save()){
            //创建新评论
            $comment = new SalesFlatOrderComment();
            $comment->wholesaler_id = $request->getWholesalerId();
            $comment->order_id = $request->getOrderId();
            $comment->quality = $request->getQuality();
            $comment->delivery = $request->getDelivery();
            $total = $this->caculate($comment->quality,$comment->delivery);
            $comment->total = $total;
            $comment->comment = $request->getComment();
            $comment->created_at = date('Y-m-d H:i:s');
            $comment->save();

            // 推送评价事件到MQ
            \framework\components\mq\Order::publishCommentEvent($order->toArray());
        }else{
            Exception::salesOrderCanNotReview();
        }
        $responseData = [
            'status' => $order->status,
            'status_label' => $order->getStatusLabel(),
        ];
        $response = new Order();
        $response->setFrom(Tools::pb_array_filter($responseData));
        return $response;
    }

    private function caculate($quality,$delivery){
        return ($quality+$delivery)/2;
    }

    public static function request()
    {
        return new OrderCommentRequest();
    }

    public static function response()
    {
        return true;
    }
}
