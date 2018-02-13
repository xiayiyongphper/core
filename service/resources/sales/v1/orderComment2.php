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
class orderComment2 extends ResourceAbstract
{
    const DEFAULT_PAGE_SIZE = 10;
    const TAG_BIT_MAX = 64;

    public static function response()
    {
        return true;
    }

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
        Tools::log('Func:' . __FUNCTION__ . ', L' . __LINE__ . ', $request:' . print_r($request, true), 'debug.txt');
        /** @var SalesFlatOrder $order */
        $order = SalesFlatOrder::find()->where(['entity_id' => $orderId])->one();
        if (!$order || !$order->getPrimaryKey()) {
            Exception::orderNotExisted();
        }

        if ($order->comment()->save()) {
            //创建新评论
            $comment = new SalesFlatOrderComment();
            $comment->wholesaler_id = $request->getWholesalerId();
            $comment->order_id = $request->getOrderId();
            $comment->quality = $request->getQuality();
            $comment->delivery = $request->getDelivery();
            $total = $this->caculate($comment->quality, $comment->delivery);
            $comment->total = $total;
            $comment->comment = $request->getComment();
            $comment->created_at = date('Y-m-d H:i:s');
            //
            $bit = $this->newSetTag($request->getQualityTag(), $request->getDeliveryTag());
            $comment->tag = $bit;
            Tools::log('Func:' . __FUNCTION__ . ', L' . __LINE__ . ', 创建新评论入库$comment:' . print_r($comment, true), 'debug.txt');
            $comment->save();
            // 推送评价事件到MQ
            \framework\components\mq\Order::publishCommentEvent($order->toArray());
        } else {
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

    public static function request()
    {
        return new OrderCommentRequest();
    }

    private function caculate($quality, $delivery)
    {
        return ($quality + $delivery) / 2;
    }

    /**
     * 标签id转存bit格式，返回int存库
     * @date 2017-07-05
     * @param array $quality_tag
     * @param array $delivery_tag
     * @return int
     */
    private function newSetTag($quality_tag = [], $delivery_tag = [])
    {
        Tools::log('Func:' . __FUNCTION__ . ', L' . __LINE__ . ', $quality_tag:' . print_r($quality_tag, true), 'debug.txt');
        Tools::log('Func:' . __FUNCTION__ . ', L' . __LINE__ . ', $delivery_tag:' . print_r($delivery_tag, true), 'debug.txt');
        if (empty($quality_tag) && empty($delivery_tag)) {
            return 0;
        }

        $tag_ids = array_merge($quality_tag, $delivery_tag);
        //TODO 设置默认二进制
        $bit = str_repeat('0', self::TAG_BIT_MAX); //注意长度64需要与数据库定义一致
        foreach ($tag_ids as $id) {
            //TODO 从右边开始位移，计算$id对应下标【$id从1开始, $offset(0-63)】
            $offset = self::TAG_BIT_MAX - $id;
            if ($offset > 0) {
                //TODO 对应下标设为1
                $bit[$offset] = 1;
            }
        }
        Tools::log('Func:' . __FUNCTION__ . ', L' . __LINE__ . ', 二进制$bit:' . $bit, 'debug.txt');
        $bit = bindec($bit);    //需要把二进制转为十进制入库
        Tools::log('Func:' . __FUNCTION__ . ', L' . __LINE__ . ', 新逻辑-set十进制$bit:' . $bit, 'debug.txt');
        return $bit;
    }
}
