<?php

namespace service\models\sales;

use common\models\DataflowBatchImportFile;
use common\models\LeImportLog;
use common\models\SalesFlatOrder;
use common\models\SalesFlatOrderAddress;
use common\models\SalesFlatOrderItem;
use common\models\SalesFlatOrderStatusHistory;
use common\models\salesrule\Rule;
use common\models\salesrule\Usage;
use common\models\salesrule\UserCoupon;
use common\redis\Keys;
use framework\components\Date;
use framework\components\es\Order;
use framework\components\mq\Order as MQOrder;
use framework\components\ToolsAbstract;
use service\components\Events;
use service\components\Proxy;
use service\components\Tools;
use service\events\ServiceEvent;
use service\message\common\Header;
use service\message\common\SourceEnum;
use service\message\customer\CustomerResponse;
use service\message\customer\RemoveCartItemsRequest;
use service\message\merchant\reduceQtyRequest;


/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2016/1/29
 * Time: 17:23
 */
class Observer
{
    /**
     * 生成订单，锁定库存，异常直接传递到上层
     * @param ServiceEvent $event
     * @throws \Exception
     */
    public static function subtractQuoteInventory(ServiceEvent $event)
    {
        $data = $event->getEventData();
        $customer = $event->getCustomer();
        //sending async to merchant system,process subtract inventory
        $header = new Header();
        $header->setSource(SourceEnum::CORE);
        $header->setTraceId($event->getTraceId());
        $header->setVersion(1);
        $header->setRoute('merchant.reduceQty');
        $request = new reduceQtyRequest();

        $products = [];
        foreach ($data as $value) {
            foreach ($value as $item) {
                $products[] = [
                    'product_id' => $item['product_id'],
                    'wholesaler_id' => $item['wholesaler_id'],
                    'num' => $item['num'],
                    'type' => isset($item['type']) ? $item['type'] : ''
                ];
            }
        }
        $requestData = [
            'auth_token' => $customer->getAuthToken(),
            'customer_id' => $customer->getCustomerId(),
        ];
        if (count($products) > 0) {
            Tools::log($products);
            $requestData['products'] = $products;
        }
        $request->setFrom($requestData);
        Proxy::sendRequest($header, $request);
    }

    /**
     * 生成订单失败，库存退回，异常直接传递到上层
     * @param ServiceEvent $event
     * @throws \Exception
     */
    public static function revertQuoteInventory(ServiceEvent $event)
    {
        $data = $event->getEventData();
        $customer = $event->getCustomer();
        //sending async to merchant system,process revert inventory
        $header = new Header();
        $header->setSource(SourceEnum::CORE);
        $header->setTraceId($event->getTraceId());
        $header->setVersion(1);
        $header->setRoute('merchant.reduceQty');
        $request = new reduceQtyRequest();

        $products = [];
        foreach ($data as $value) {
            foreach ($value as $item) {
                $products[] = [
                    'product_id' => $item['product_id'],
                    'wholesaler_id' => $item['wholesaler_id'],
                    'num' => -$item['num'],
                    'type' => isset($item['type']) ? $item['type'] : ''
                ];
            }
        }
        $requestData = [
            'auth_token' => $customer->getAuthToken(),
            'customer_id' => $customer->getCustomerId(),
        ];
        if (count($products) > 0) {
            Tools::log($products);
            $requestData['products'] = $products;
        }
        $request->setFrom($requestData);
        Proxy::sendRequest($header, $request);
    }

    /**
     * 订单取消，关闭，拒单库存退回，阻断异常不能影响主流程
     * @deprecated after 2017-5-31 11:04
     * @param SalesFlatOrder $order
     */
    public static function revertOrderInventory(SalesFlatOrder $order)
    {
        try {
            $header = new Header();
            $header->setSource(SourceEnum::CORE);
            $header->setVersion(1);
            $header->setRoute('merchant.reduceQty');
            $request = new reduceQtyRequest();
            /** @var SalesFlatOrderItem $item */
            $products = [];
            foreach ($order->getItemsCollection(false) as $item) {
                $products[] = [
                    'product_id' => $item->product_id,
                    'wholesaler_id' => $order->wholesaler_id,
                    'num' => -$item->qty,
                ];
            }
            $requestData = [
                'auth_token' => $order->customer_id,//内网接口无需验证
                'customer_id' => $order->customer_id,
            ];

            if (count($products) > 0) {
                Tools::log($products);
                $requestData['products'] = $products;
            }
            $request->setFrom($requestData);
            Proxy::sendRequest($header, $request);
        } catch (\Exception $e) {
            ToolsAbstract::logException($e);
        }

    }


    /**
     * 部分确认收货，库存退回，阻断异常不能影响主流程
     * @param SalesFlatOrder $order
     * @throws \Exception
     */
    public static function revertOrderPartialInventory(SalesFlatOrder $order)
    {
        try {
            $header = new Header();
            $header->setSource(SourceEnum::CORE);
            $header->setVersion(1);
            $header->setRoute('merchant.reduceQty');
            $request = new reduceQtyRequest();
            /** @var SalesFlatOrderItem $item */
            $products = [];
            foreach ($order->getItemsCollection(false) as $item) {
                if (!$item->receipt) {
                    $products[] = [
                        'product_id' => $item->product_id,
                        'wholesaler_id' => $order->wholesaler_id,
                        'num' => -$item->qty,
                    ];
                }
            }
            $requestData = [
                'auth_token' => $order->customer_id,//内网接口无需验证
                'customer_id' => $order->customer_id,
            ];

            if (count($products) > 0) {
                Tools::log($products);
                $requestData['products'] = $products;
            }
            $request->setFrom($requestData);
            Proxy::sendRequest($header, $request);
        } catch (\Exception $e) {
            ToolsAbstract::logException($e);
        }
    }

    public static function removeOrderItems(ServiceEvent $event)
    {
        $data = $event->getEventData();
        $data = $data['store_products'];
        $customer = $event->getCustomer();
        if (!is_array($data) || !($customer instanceof CustomerResponse)) {
            return false;
        }

        $header = new Header();
        $header->setSource(SourceEnum::CORE);
        $header->setTraceId($event->getTraceId());
        $header->setVersion(1);
        $header->setRoute('merchant.removeCartItems');
        $request = new RemoveCartItemsRequest();
        $products = [];
        foreach ($data as $value) {
            foreach ($value as $item) {
                $products[] = [
                    'product_id' => $item['product_id'],
                    'wholesaler_id' => $item['wholesaler_id'],
                    'type' => isset($item['type']) ? $item['type'] : 0
                ];
            }
        }
        $requestData = [
            'auth_token' => $customer->getAuthToken(),
            'customer_id' => $customer->getCustomerId(),
        ];
        if (count($products) > 0) {
            Tools::log($products);
            $requestData['products'] = $products;
        }
        $request->setFrom($requestData);
        Proxy::sendRequest($header, $request);
        return true;
    }

    /**
     * 此处的事件触发点为：订单数据保存完成之后，并且确认有实质订单状态变更时会触发该事件。
     * 相应改动请同步crontab项目
     * @param ServiceEvent $event
     * @return $this
     */
    public static function orderStateChanged(ServiceEvent $event)
    {
        ToolsAbstract::log(__METHOD__, 'orderStateChanged.log');
        $data = $event->getEventData();
        /* @var $order SalesFlatOrder */
        $order = $data['order'];
//        $oriStatus = $data['status'];
//        $oriState = $data['state'];
//        $customerId = $order->customer_id;
//        $wholesalerId = $order->wholesaler_id;
        $orderId = $order->getPrimaryKey();
        ToolsAbstract::log($orderId, 'orderStateChanged.log');
//        $events = [];
        switch ($order->status) {
            //新订单
            case SalesFlatOrder::STATUS_PROCESSING:
//                $name = Events::EVENT_ORDER_NEW;
//                $eventName = Events::getMerchantEventName($name);
//                $events[$eventName] = [
//                    'name' => $name,
//                    'data' => [
//                        'order_id' => $orderId,
//                        'wholesaler_id' => $wholesalerId
//                    ]
//                ];
                // 新订单通知customer标记 by zgr
//                $eventName = Events::getCustomerEventName($name);
//                $events[$eventName] = [
//                    'name' => $name,
//                    'data' => $order->toArray(),
//                ];

                /* 传递商品id、数量等信息，目前用于秒杀商品 V2.6.6 zqy */
                $productList = [];
                if (!empty($order->getItems()) && is_array($order->getItems())) {
                    /** @var SalesFlatOrderItem $item */
                    foreach ($order->getItems() as $item) {
                        $productList[$item['item_id']] = [
                            'product_id' => $item['product_id'],
                            'qty' => $item['qty'],
                            'type' => !empty($item['product_type']) ? $item['product_type'] : 0,
                            'activity_id' => !empty($item['activity_id']) ? $item['activity_id'] : 0,
                            'lsin' => !empty($item['lsin']) ? $item['lsin'] : '',
                            'brand' => !empty($item['brand']) ? $item['brand'] : '',
                            'first_category_id' => !empty($item['first_category_id']) ? $item['first_category_id'] : 0,
                            'second_category_id' => !empty($item['second_category_id']) ? $item['second_category_id'] : 0,
                            'third_category_id' => !empty($item['third_category_id']) ? $item['third_category_id'] : 0,
                        ];
                    }
                }
                /** version版本为1.0 @since v3.0，相应改动请同步crontab项目 */
                MQOrder::publishOrderNewEvent($order->toArray(), ['productList' => $productList], '1.0');
                // 上报到es
                //Console::get()->log($order->toArray(), null, ['order']);
                // 增加用户每日钱包限额
                self::addBalanceDailyLimit($order);
                break;
            //申请取消
            case SalesFlatOrder::STATUS_HOLDED:
//                $name = Events::EVENT_ORDER_APPLY_CANCEL;
//                $eventName = Events::getMerchantEventName($name);
//                $events[$eventName] = [
//                    'name' => $name,
//                    'data' => [
//                        'order_id' => $orderId,
//                        'wholesaler_id' => $wholesalerId
//                    ]
//                ];
                MQOrder::publishOrderApplyCancelEvent($order->toArray());
                break;
            //商家接单
            case SalesFlatOrder::STATUS_PROCESSING_RECEIVE:
                //只有商家系统能发布确认订单事件
                break;
            // 订单取消
            case SalesFlatOrder::STATUS_CANCELED:
                //同意取消订单只可能是由merchant系统发布事件，当订单状态为cancel时，只发布订单取消时间
                MQOrder::publishCancelEvent($order->toArray(), ['productList' => self::getOrderProducts($order)]);
                // 订单取消通知customer标记 by zgr
                /*
                $name = Events::EVENT_ORDER_CANCEL;
                $eventName = Events::getCustomerEventName($name);
                $events[$eventName] = [
                    'name' => $name,
                    'data' => $order->toArray(),
                ];*/
                // 取消订单回退当日可用额度
//                self::reduceBalanceDailyLimit($order);
                //取消订单退回已购买数量
//                self::reduceDailyPurchaseHistory($order);
//                self::revertOrderInventory($order);
//                self::reduceCustomerRulesLimit($order);
                break;
            // 订单完成
            case SalesFlatOrder::STATUS_COMPLETE:
                break;
            case SalesFlatOrder::STATUS_CLOSED:
                // 供货商拒单，该事件只能由供货商系统触发。
                break;
            // 超市拒收
            case SalesFlatOrder::STATUS_REJECTED_CLOSED:
//                $name = Events::EVENT_ORDER_REJECT;
//                $eventName = Events::getMerchantEventName($name);
//                $events[$eventName] = [
//                    'name' => $name,
//                    'data' => [
//                        'order_id' => $orderId,
//                        'wholesaler_id' => $wholesalerId
//                    ]
//                ];
                // 超市拒收通知customer标记 by zgr
//                $name = Events::EVENT_ORDER_REJECT;
//                $eventName = Events::getCustomerEventName($name);
//                $events[$eventName] = [
//                    'name' => $name,
//                    'data' => $order->toArray(),
//                ];
                MQOrder::publishRejectedClosedEvent($order->toArray(), ['productList' => self::getOrderProducts($order)]);
                // 取消订单回退当日可用额度
//                self::reduceBalanceDailyLimit($order);
                //取消订单退回已购买数量
//                self::reduceDailyPurchaseHistory($order);
//                self::revertOrderInventory($order);
                //self::revertOrderCoupon($order);
//                self::reduceCustomerRulesLimit($order);
                break;
            // 超市签收,待评价
            case SalesFlatOrder::STATUS_PENDING_COMMENT:
                // 订单完成通知customer,处理返现的问题
                /*
                $name = Events::EVENT_ORDER_PENDING_COMMENT;
                $eventName = Events::getCustomerEventName($name);
                $events[$eventName] = [
                    'name' => $name,
                    'data' => $order->toArray(),
                ];
                // 订单完成通知merchant
                $name = Events::EVENT_ORDER_PENDING_COMMENT;
                $eventName = Events::getMerchantEventName($name);
                $events[$eventName] = [
                    'name' => $name,
                    'data' => [
                        'order_id' => $orderId,
                        'wholesaler_id' => $wholesalerId
                    ]
                ];*/
                MQOrder::publishPendingCommentEvent($order->toArray());
                break;
            case SalesFlatOrder::STATUS_WAITING_REFUND:
            case SalesFlatOrder::STATUS_REFUND:
            default:
        }
//        ToolsAbstract::log($events, 'orderStateChanged.log');
//        if (count($events) > 0) {
//            foreach ($events as $eventName => $event) {
//                Proxy::sendMessage($eventName, $event);
//            }
//        }

    }

    /**
     * 获取订单商品列表信息
     * @since V2.6.7
     * @author zqy
     * @param SalesFlatOrder $order
     * @return array
     */
    private static function getOrderProducts(SalesFlatOrder $order)
    {
        $productList = [];
        if ($items = $order->getItemsCollection()) {
            /** @var SalesFlatOrderItem $item */
            foreach ($items as $item) {
                $productList[$item['item_id']] = [
                    'product_id' => $item['product_id'],
                    'qty' => $item['qty'],
                    'type' => !empty($item['product_type']) ? $item['product_type'] : 0,
                    'activity_id' => !empty($item['activity_id']) ? $item['activity_id'] : 0,
                ];
            }
        }
        return $productList;
    }

    /**
     * $data = [
     * 'transaction_no' => $transaction_no,
     * 'customer_id' => $customer_id,
     * 'title' => $title,
     * 'action' => $action,
     * 'type' => $type,
     * 'amount' => $amount,
     * 'balance' => $balance,
     * 'order_id' => $order_id,
     * 'order_no' => $order_no,
     * 'created_at' => date('Y-m-d H:i:s',time()),
     * ];
     *
     * @param array $data
     */
    public static function balanceChange($data)
    {
        Tools::log(__METHOD__, 'balanceChange.log');
        Tools::log($data, 'balanceChange.log');

        $order_id = $data['order_id'];
        /* @var $order SalesFlatOrder */
        $order = SalesFlatOrder::findOne(['entity_id' => $order_id]);
        //Tools::log($order->toArray(), 'balanceChange.log');
        $order->addStatusHistoryComment($data['comment']);
        $order->save();
    }

    /**
     * 司机客户端发货成功,送达成功,都来加状态
     *
     * 'data' => [
     * 'driver_order' => $order->toArray(),
     * 'driver'=> $driver->toArray(),// 送达时没有这个字段，不要用它
     * 'comment' => "供货商已发货，司机电话".$driver->phone,
     * ],
     *
     * @param array $data
     */
    public static function logisticsStatusChange($data)
    {
        Tools::log(__METHOD__, 'logisticsStatusChange.log');
        Tools::log($data, 'logisticsStatusChange.log');

        $order_id = $data['driver_order']['order_id'];
        /* @var $order SalesFlatOrder */
        $order = SalesFlatOrder::findOne(['entity_id' => $order_id]);
        $order->addStatusHistoryComment($data['comment']);
        $order->save();
    }

    //记录用户享受优惠活动次数 到redis,+1
    public static function addCustomerRulesLimit(ServiceEvent $event)
    {
        $data = $event->getEventData();
        $rules = $data['applied_rules'];
        $customer = $event->getCustomer();
        if (!is_array($data) || !($customer instanceof CustomerResponse)) {
            return false;
        }

        $redis = Tools::getRedis();
        $customer_id = $customer->getCustomerId();
        foreach ($rules as $rule) {
            /** @var Rule $rule */
            if ($rule->rule_uses_limit > 0) {
                $key = Keys::getEnjoyTimesKey($customer_id, $rule->rule_id);
                if ($redis->exists($key)) {
                    $redis->incr($key);
                } else {
                    $expireTime = $rule->to_date ? strtotime($rule->to_date) - time() : 0;
                    $redis->set($key, 1, $expireTime);
                }
            }

            if ($rule->rule_uses_daily_limit > 0) {
                $dailyKey = Keys::getEnjoyDailyTimesKey($customer_id, $rule->rule_id);
                if ($redis->exists($dailyKey)) {
                    $redis->incr($dailyKey);
                } else {
                    $redis->set($dailyKey, 1, 86400);   // 艹，1天总该过期了吧？
                }
            }
        }
    }

    /**
     * 记录用户享受优惠活动次数 到redis,-1
     * @param SalesFlatOrder $order
     */
    public static function reduceCustomerRulesLimit(SalesFlatOrder $order)
    {
        try {

            $rule_ids = $order->applied_rule_ids;
            if (empty($rule_ids)) {
                return;
            }

            $rule_ids = explode(',', $rule_ids);
            $now = date('Y-m-d H:i:s');
            $rules = Rule::find()
                ->where(['rule_id' => $rule_ids])
                ->andWhere(['is_active' => 1])
                ->andWhere(['<=', 'from_date', $now])
                ->andWhere(['>=', 'to_date', $now])
                ->andWhere(['is_del' => 0])
                ->andWhere(['coupon_type' => Rule::COUPON_TYPE_NO_COUPON])
                ->andWhere(['>', 'rule_uses_limit', 0])
                ->all();

            $redis = Tools::getRedis();
            $customer_id = $order->customer_id;
            foreach ($rules as $rule) {
                Tools::log('reduceCustomerRulesLimit > rule_id===' . $rule->rule_id, 'hl.log');
                $key = Keys::getEnjoyTimesKey($customer_id, $rule->rule_id);
                $times = $redis->get($key);
                if ($times > 0) {
                    /*$times = intval($times) - 1;
                    $expire_time = $rule->to_date ? strtotime($rule->to_date) - time() : 0;
                    $redis->set($key,$times,$expire_time);*/
                    $redis->decr($key);
                }
            }
        } catch (\Exception $e) {
            ToolsAbstract::logException($e);
            Tools::log($order, 'reduceCustomerRulesLimit_exception.log');
        } catch (\Error $e) {
            ToolsAbstract::logException($e);
            Tools::log($order, 'reduceCustomerRulesLimit_exception.log');
        }
    }

    /**
     * 记录用户享受活动的次数
     * @param $data
     */
    public static function revertCustomerRulesLimit($data)
    {
        try {
            if (isset($data['order'], $data['order']['entity_id'], $data['order']['customer_id'])) {
                /** @var SalesFlatOrder $order */
                $order = SalesFlatOrder::findOne(['entity_id' => $data['order']['entity_id'], 'customer_id' => $data['order']['customer_id']]);
                if ($order) {
                    self::reduceCustomerRulesLimit($order);
                } else {
                    Tools::log('order no existed', 'revertCustomerRulesLimit_exception.log');
                }
            }
        } catch (\Exception $e) {
            ToolsAbstract::logException($e);
            Tools::log($data, 'revertCustomerRulesLimit_exception.log');
        } catch (\Error $e) {
            ToolsAbstract::logException($e);
            Tools::log($order, 'reduceCustomerRulesLimit_exception.log');
        }
    }

    /**
     * 更新订单的返现状态
     *
     * @param $data
     * @return boolean
     */
    public static function updateRebateReturnStatus($data)
    {
        if (!empty($data['order']['entity_id'])) {
            $orderModel = SalesFlatOrder::findOne(['entity_id' => $data['order']['entity_id']]);
            if (!$orderModel) {
                return false;
            }

            if ($orderModel->rebate_return_status == SalesFlatOrder::REBATE_RETURN_STATUS_RETURN) {
                return true;
            }

            $orderModel->rebate_return_status = SalesFlatOrder::REBATE_RETURN_STATUS_RETURN;
            return $orderModel->save();
        }
        return false;
    }

    /**
     * @param ServiceEvent $event
     * @return bool
     */
    public static function dailyPurchaseHistory(ServiceEvent $event)
    {
        $data = $event->getEventData();
        $data = $data['store_products'];
        $customer = $event->getCustomer();
        if (!is_array($data) || !($customer instanceof CustomerResponse)) {
            return false;
        }
        $key = Keys::getDailyPurchaseHistory($customer->getCustomerId(), $customer->getCity());
        $redis = Tools::getRedis();
        $products = [];
        foreach ($data as $value) {
            foreach ($value as $item) {
                $products[$item['product_id']] = $item['num'];
            }
        }
        $date = new Date();
        $ttl = strtotime($date->date('Y-m-d 00:00:00', strtotime("+1 day"))) - $date->timestamp();
        if (!$redis->exists($key)) {
            $redis->hMset($key, $products);
            $redis->expire($key, $ttl);
        } else {
            foreach ($products as $productId => $qty) {
                $redis->hIncrBy($key, $productId, $qty);
            }
            if ($redis->ttl($key) == -1) {
                $redis->expire($key, $ttl);
            }
        }
        return true;
    }

    /**
     * @param SalesFlatOrder $order
     */
    public static function reduceDailyPurchaseHistory($order)
    {
        try {
            $key = Keys::getDailyPurchaseHistory($order->customer_id, $order->city);
            $redis = Tools::getRedis();
            $products = [];
            /** @var SalesFlatOrderItem $item */
            foreach ($order->getItemsCollection(false) as $item) {
                $products[$item->product_id] = $item->qty;
            }
            $date = new Date();
            $ttl = strtotime($date->date('Y-m-d 00:00:00', strtotime("+1 day"))) - $date->timestamp();
            if ($redis->exists($key)) {
                foreach ($products as $productId => $qty) {
                    $current = $redis->hIncrBy($key, $productId, -$qty);
                    if ($current <= 0) {
                        $redis->hDel($key, $productId);
                    }
                }
                if ($redis->ttl($key) == -1) {
                    $redis->expire($key, $ttl);
                }
            }
        } catch (\Exception $e) {
            Tools::logException($e, 'Exception_reduceDailyPurchaseHistory.log');
        }

    }

    /**
     * 通过消息队列退回每日限购数量
     * @param $data
     */
    public static function revertDailyPurchaseHistory($data)
    {
        try {
            if (isset($data['order'], $data['order']['entity_id'], $data['order']['customer_id'])) {
                /** @var SalesFlatOrder $order */
                $order = SalesFlatOrder::findOne(['entity_id' => $data['order']['entity_id'], 'customer_id' => $data['order']['customer_id']]);
                self::reduceDailyPurchaseHistory($order);
            }
        } catch (\Exception $e) {
            ToolsAbstract::logException($e);
            Tools::log($data, 'revertDailyPurchaseHistory_exception.log');
        }
    }

    /**
     * @param SalesFlatOrder $order
     */
    protected static function addBalanceDailyLimit($order)
    {
        try {
            $redis = Tools::getRedis();
            $key = Keys::getBalanceDailyLimitKey($order->customer_id);

            $date = new Date();
            $ttl = strtotime($date->date('Y-m-d 00:00:00', strtotime("+1 day"))) - $date->timestamp();
            if (!$redis->exists($key)) {
                // 不存在则新建
                $redis->incrByFloat($key, $order->balance);
                $redis->expire($key, $ttl);
            } else {
                $redis->incrByFloat($key, $order->balance);
                if ($redis->ttl($key) == -1) {
                    $redis->expire($key, $ttl);
                }
            }
        } catch (\Exception $e) {
            Tools::logException($e, 'Exception_addBalanceDailyLimit.log');
        }
    }

    /**
     * @param SalesFlatOrder $order
     */
    protected static function reduceBalanceDailyLimit($order)
    {
        try {
            $redis = Tools::getRedis();
            $key = Keys::getBalanceDailyLimitKey($order->customer_id);

            $date = new Date();
            $ttl = strtotime($date->date('Y-m-d 00:00:00', strtotime("+1 day"))) - $date->timestamp();
            if (!$redis->exists($key)) {
                // 不存在则不管

            } else {
                $amount = $redis->get($key);
                if ($amount > $order->balance) {
                    $redis->incrByFloat($key, -$order->balance);
                    if ($redis->ttl($key) == -1) {
                        $redis->expire($key, $ttl);
                    }
                } else {
                    // 减到0
                    $redis->del($key);
                }
            }
        } catch (\Exception $e) {
            Tools::logException($e, 'Exception_reduceBalanceDailyLimit.log');
        }
    }

    /**
     * 通过消息队列退回每日限购数量
     * @param $data
     */
    public static function revertBalanceDailyLimit($data)
    {
        try {
            if (isset($data['order'], $data['order']['entity_id'], $data['order']['customer_id'])) {
                /** @var SalesFlatOrder $order */
                $order = SalesFlatOrder::findOne(['entity_id' => $data['order']['entity_id'], 'customer_id' => $data['order']['customer_id']]);
                self::reduceBalanceDailyLimit($order);
            }
        } catch (\Exception $e) {
            ToolsAbstract::logException($e);
            Tools::log($data, 'revertDailyPurchaseHistory_exception.log');
        }
    }

    /**
     * report order data to elastic search cluster,where there is any order status changed
     * @param bool $insert
     * @param array $attributes
     * @param array $data
     * @return bool
     */
    public static function reportToElasticSearch($insert, $attributes, $data)
    {
        try {
            Tools::log(__METHOD__, 'reportToElasticSearch.log');
            /**
             * only when the message queue is enabled,and the order data will be report to our elastic search cluster
             */
            /*if (!defined('ENV_ENABLE_MQ') || ENV_ENABLE_MQ == 0) {
                return false;
            }*/
            list($orderId, $histories, $items, $address) = $data;
            /** @var SalesFlatOrderAddress $address */
            /** @var SalesFlatOrderStatusHistory $history */
            Tools::log('action:' . ($insert ? 'insert' : 'update') . $orderId, 'reportToElasticSearch.log');
            //Tools::log($isNewRecord,'reportToElasticSearch.log');
            if ($insert) {
                /***
                 * assume that all the data we need is in normal status,so we can simply use it.
                 */
                /** @var SalesFlatOrderItem $item */
                foreach ($items as $item) {
                    $attributes['item'][] = $item->toArray();
                }

                foreach ($histories as $history) {
                    $attributes['history'][] = $history->toArray();
                }

                $attributes['address'] = $address->toArray();
                Tools::log($attributes, 'reportToElasticSearch.log');
                Order::get()->create($orderId, $attributes);
            } else {
                /**
                 * if there is any order attributes changed
                 */
                if (!is_array($attributes)) {
                    $attributes = [];
                }

                /**
                 * if there is any order item attributes changed
                 */
                if (count($items) > 0) {
                    $allItems = SalesFlatOrderItem::find()->where(['order_id' => $orderId])->asArray()->all();
                    foreach ($allItems as $key => $allItem) {
                        /** @var SalesFlatOrderItem $item */
                        foreach ($items as $item) {
                            /**
                             * update all order items with the changed order item if there are have the same item id
                             */
                            if ($allItem['item_id'] == $item->item_id) {
                                $allItem[$key] = $item->toArray();
                            }
                        }
                    }
                    $attributes['item'] = $allItems;
                }

                /**
                 * if there is any order status history changed
                 */
                if (count($histories) > 0) {
                    /**
                     * cause the order status history can only be append new status history,so we just put the changed status history at the end of the list.
                     */
                    $allStatusHistory = SalesFlatOrderStatusHistory::find()->where(['parent_id' => $orderId])->asArray()->all();
                    $attributes['history'] = $allStatusHistory;
                }

                /**
                 * if there is order address attributes changed
                 */
                if (isset($address)) {
                    $attributes['address'] = $address->toArray();
                }
                Tools::log($attributes, 'reportToElasticSearch.log');
                Order::get()->update($orderId, $attributes);
            }
        } catch (\Exception $e) {
            ToolsAbstract::logException($e);
        }
    }

    /**
     * @param $data
     * Author Jason Y. wang
     * 发送单个优惠券
     */
    public function send_coupon($data)
    {

        $redis = Tools::getRedis();

        //Tools::log($data);
        $profile_id = $data['dataflow_batch_import_file_id'];
        // 读取Profile和import_log
        $profile = DataflowBatchImportFile::findOne(['entity_id' => $profile_id]);
        $import_log = LeImportLog::findOne(['dataflow_id' => $profile_id]);
        if (!$profile || !$import_log) {
            Tools::log('===================', 'send_coupon_error.log');
            Tools::log('Can\'t find profile_id:' . $profile_id, 'send_coupon_error.log');
            Tools::log($data, 'send_coupon_error.log');
            Tools::log('===================', 'send_coupon_error.log');
            return;
        }

        //Tools::log($profile->toArray());
        //Tools::log($import_log->toArray());
        //Tools::log($profile->message);
        //Tools::log($import_log->other);

        // 读取import_log的other字段，里面有解析好要处理的行
        $rows = unserialize($import_log->other);
        if (!$rows || !is_array($rows) || count($rows) < 1) {
            Tools::log('===================', 'send_coupon_error.log');
            Tools::log('Unserialize data error:', 'send_coupon_error.log');
            Tools::log($import_log->other, 'send_coupon_error.log');
            Tools::log('===================', 'send_coupon_error.log');
            return;
        }


        $rule_cache = [];
        foreach ($rows as $key => $row) {
            if (!isset($row['rule_id'])) {
                $rows[$key]['success'] = 0;
                $rows[$key]['message'] = 'rule_id is not set!';
            }
            if (!isset($row['customer_id'])) {
                $rows[$key]['success'] = 0;
                $rows[$key]['message'] = 'customer_id is not set!';
            }

            // 读取rule
            $rule_id = $row['rule_id'];
            $customer_id = $row['customer_id'];
            if (!isset($rule_cache[$rule_id])) {
                $rule_cache[$rule_id] = Rule::getCouponRuleByRuleId($rule_id, [Rule::RULE_COUPON, Rule::RULE_COUPON_SEND, Rule::RULE_COUPON_SHOW]);
            }
            /** @var Rule $rule */
            $rule = $rule_cache[$rule_id];

            // rule在有效期内
            if ($rule) {
                //发送优惠券
                try {
                    $result = Rule::getCoupon($rule, $customer_id, UserCoupon::COUPON_SOURCE_SYSTEM);
                    if ($result) {
                        //领取成功记录到redis
                        //领取缓存
                        $couponKey = UserCoupon::COUPON_KEY_PREFIX . $rule_id;
                        $redis->hIncrBy($couponKey, $customer_id, 1);

                        $off = array_values(array_filter(explode(',', $rule->discount_amount)));
                        $off = array_pop($off);
                        $promotion = '';
                        if ($rule->simple_action == Rule::BY_FIXED_ACTION) {
                            $promotion = $off . '元';//满额减
                        } else if ($rule->simple_action == Rule::BY_PERCENT_ACTION) {
                            $promotion = ($off / 10) . '折';//满额折
                        } else if ($rule->simple_action == Rule::BUY_X_GET_Y_FREE_ACTION) {
                            $promotion = '满赠';
                        }

                        //新优惠券推送
                        $name = Events::EVENT_COUPON_NEW;
                        $eventName = Events::getCustomerEventName($name);
                        $event = [
                            'name' => $name,
                            'data' => [
                                'promotion' => $promotion,
                                'customer_id' => $customer_id,
                                'rule' => $rule->toArray(),
                            ]
                        ];
                        Proxy::sendMessage($eventName, $event);
                        // 记录成功
                        $rows[$key]['success'] = 1;
                        $rows[$key]['message'] = 'OK!';
                    }
                } catch (\Exception $e) {
                    $message = 'send coupon failed:rule_id=' . $rule_id . ' customer_id:' . $customer_id . ' exception:' . $e->getMessage();
                    $rows[$key]['success'] = 0;
                    $rows[$key]['message'] = $message;
                    Tools::log($message, 'coupon_put.log');
                }
            } else {
                $message = 'rule not valid:rule_id=' . $rule_id . ' customer_id:' . $customer_id;
                $rows[$key]['success'] = 0;
                $rows[$key]['message'] = $message;
                Tools::log($message, 'coupon_put.log');
            }
        }

        // 最后统计成功次数，更新Profile和import_log
        $message = unserialize($profile->message);
        $message['success'] = 0;
        $message['failure'] = 0;
        foreach ($rows as $row) {
            if ($row['success']) {
                $message['success']++;
            } else {
                $message['failure']++;
            }
        }
        $profile->message = serialize($message);
        $profile->status = 'complete';
        $date = new Date();
        $profile->complete_at = $date->gmtDate();
        $profile->save();

        // 更新import_log
        //Tools::log($rows);
        $import_log->success = $message['success'];
        $import_log->result = serialize($rows);
        $import_log->status = 2;
        $import_log->save();

    }

    /**
     * 改为使用消息队列响应
     * @deprecated after 2017-05-31 11:21 by henryzhuy 修改为使用消息队列响应，使用returnCoupon
     * @param SalesFlatOrder $order
     * @return bool
     */
    public static function revertOrderCoupon(SalesFlatOrder $order)
    {
        try {
            $couponId = $order->coupon_id;
            ToolsAbstract::log('coupon_id:' . $couponId);
            //客户端选择不使用优惠券，验证失败
            if ($couponId <= 0) {
                return false;
            }
            /** @var UserCoupon $coupon */
            $coupon = UserCoupon::findOne(['entity_id' => $couponId]);
            //没有查询到优惠券信息，优惠券无效，验证失败
            if (!$coupon) {
                return false;
            }
            //非法优惠券，验证失败
            if ($coupon->customer_id != $order->customer_id) {
                return false;
            }

            $coupon->state = UserCoupon::USER_COUPON_UNUSED;
            $usage = new Usage();
            $usage->order_id = $order->getPrimaryKey();
            $usage->status = Usage::COUPON_RETURN;
            $coupon->usage = $usage;
            $coupon->save();

            /* 修改优惠券返回字段 */
            if ($order->coupon_return_status != SalesFlatOrder::COUPON_RETURN_STATUS_RETURN) {
                $order->coupon_return_status = SalesFlatOrder::COUPON_RETURN_STATUS_RETURN;
                $order->save();
            }
        } catch (\Exception $e) {
            ToolsAbstract::logException($e);
        }
    }

    /**
     * 退优惠券事件
     * @link http://doc.laile.com/pages/viewpage.action?pageId=983081
     * @param $data
     * @return bool
     */
    public static function returnCoupon($data)
    {
        try {
            if (!isset($data['order'])) {
                //非法消息
                return false;
            }
            $orderData = $data['order'];

            $orderId = $orderData['entity_id'];
            $customerId = $orderData['customer_id'];
            /** @var SalesFlatOrder $order */
            $order = SalesFlatOrder::findOne(['entity_id' => $orderId, 'customer_id' => $customerId]);
            if ($order) {
                //退优惠券
                $couponId = $order->coupon_id;
                //客户端选择不使用优惠券，验证失败
                if ($couponId <= 0) {
                    return false;
                }
                /** @var UserCoupon $coupon */
                $coupon = UserCoupon::findOne(['entity_id' => $couponId]);
                //没有查询到优惠券信息，优惠券无效，验证失败
                if (!$coupon) {
                    return false;
                }
                //非法优惠券，验证失败
                if ($coupon->customer_id != $order->customer_id) {
                    return false;
                }
                //只有当优惠券处于已使用的状态，才考虑退回
                if ($coupon->state == UserCoupon::USER_COUPON_USED) {
                    $coupon->state = UserCoupon::USER_COUPON_UNUSED;
                    $usage = new Usage();
                    $usage->order_id = $order->getPrimaryKey();
                    $usage->status = Usage::COUPON_RETURN;
                    $coupon->usage = $usage;
                    $coupon->save();
                }

                //只有当当前订单字段的状态不等于已退回的时候，才考虑更新该字段，并保存
                if ($order->coupon_return_status != SalesFlatOrder::COUPON_RETURN_STATUS_RETURN) {
                    $order->coupon_return_status = SalesFlatOrder::COUPON_RETURN_STATUS_RETURN;
                    $order->save();
                }
            }
        } catch (\Exception $e) {
            ToolsAbstract::logException($e);
            ToolsAbstract::log($data, 'returnCoupon_exception.log');
        }
    }

    /*
     * slim项目向swoole项目发的message处理
     */
    public static function slim_to_swoole_message($data)
    {
        Tools::log('===================', 'slimToSwooleMessage.log');
        Tools::log($data, 'slimToSwooleMessage.log');
        if (!isset($data['sys_name'])
            || !isset($data['event_name'])
            || !isset($data['event_data'])
        ) {
            Tools::log('param error!', 'slimToSwooleMessage.log');
            Tools::log('===================', 'slimToSwooleMessage.log');
            return;
        }

        $sys_name = $data['sys_name'];
        $name = $data['event_name'];
        $event = $data['event_data'];

        if ($sys_name == Events::CORE_SYS_NAME) {
            $eventName = Events::getCoreEventName($name);
        } elseif ($sys_name == Events::CUSTOMER_SYS_NAME) {
            $eventName = Events::getCustomerEventName($name);
        } elseif ($sys_name == Events::MERCHANT_SYS_NAME) {
            $eventName = Events::getMerchantEventName($name);
        } elseif ($sys_name == Events::ROUTE_SYS_NAME) {
            $eventName = Events::getRouteEventName($name);
        } else {
            Tools::log('sys name error:' . $sys_name, 'slimToSwooleMessage.log');
            Tools::log('===================', 'slimToSwooleMessage.log');
            return;
        }

        // 代发消息
        Proxy::sendMessage($eventName, $event);
        Tools::log('===================', 'slimToSwooleMessage.log');

    }

    /**
     * 订单通过core_msg上报到消息队列,
     * 通过core_msg事件过来的都是属于更新操作。默认更新订单的所有数据除了地址信息
     * @param $data
     * @return bool
     */
    public static function esOrderReport($data)
    {
        try {
            if (!isset($data['order_id'])) {
                //非法消息
                return false;
            }
            $orderId = $data['order_id'];

            /** @var SalesFlatOrder $order */
            $order = SalesFlatOrder::findOne(['entity_id' => $orderId]);

            /**
             * if there is any order attributes changed
             */
            $attributes = $order->toArray();

            /**
             * if there is any order item attributes changed
             */
            /**
             * update all order items with the changed order item if there are have the same item id
             */
            $allItems = SalesFlatOrderItem::find()->where(['order_id' => $orderId])->asArray()->all();
            $attributes['item'] = $allItems;


            /**
             * if there is any order status history changed
             */
            /**
             * cause the order status history can only be append new status history,so we just put the changed status history at the end of the list.
             */
            $allStatusHistory = SalesFlatOrderStatusHistory::find()->where(['parent_id' => $orderId])->asArray()->all();
            $attributes['history'] = $allStatusHistory;

            Tools::log($attributes, 'reportToElasticSearchFromMsg.log');
            Order::get()->update($orderId, $attributes);
        } catch (\Exception $e) {
            ToolsAbstract::logException($e);
        }
    }
}