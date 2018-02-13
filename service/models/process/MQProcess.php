<?php

namespace service\models\process;

use Elasticsearch\ClientBuilder;
use framework\core\ProcessInterface;
use framework\core\SWServer;
use framework\components\es\Console;
use framework\components\ToolsAbstract;
use framework\mq\MQAbstract;
use PhpAmqpLib\Message\AMQPMessage;
use service\models\sales\Observer;

/**
 * Created by PhpStorm.
 * User: henryzhu
 * Date: 16-6-2
 * Time: 上午11:12
 */

/**
 * Class MQProcess
 * @package service\models\process
 */
class MQProcess implements ProcessInterface
{
    protected static $flag = false;
    /**
     * @var \Elasticsearch\Client
     */
    protected static $esClient;
    const ACTION_CREATE = 'create';
    const ACTION_UPDATE = 'update';
    const ACTION_DELETE = 'delete';
    const ACTION_INDEX = 'index';

    /**
     * @param mixed $data
     * @param string $action
     */
    private static function sendOrder($data, $action)
    {
        try {
            $path = ToolsAbstract::getESOrderPath();
            if (!self::$flag) {
                if (!file_exists($path)) {
                    mkdir($path, 0777, true);
                }
                self::$flag = true;
            }
            if (isset($data['__id__'])) {
                $id = $data['__id__'];
                $data['action'] = $action;
                $file = sprintf('%s%s.bin', $path . DIRECTORY_SEPARATOR, $id);
                file_put_contents($file, json_encode($data) . PHP_EOL, FILE_APPEND);
            } else {
                ToolsAbstract::log($data, 'sendOrder_failed.log');
            }
        } catch (\Exception $e) {
            ToolsAbstract::logException($e);
        }
    }

    /**
     * @link http://doc.laile.com/pages/viewpage.action?pageId=983081
     * @inheritdoc
     */
    public function run(SWServer $SWServer, \swoole_process $process)
    {
        try {
            ToolsAbstract::getMQ(true)->consume(function ($msg) {
                /** @var  AMQPMessage $msg */
                Console::get()->log($msg->body, null, [__METHOD__]);
                $body = json_decode($msg->body, true);
                ToolsAbstract::log($body, 'mq_process.log');

                $tags = [];
                $key = ToolsAbstract::arrayGetString($body, 'key');
                $data = ToolsAbstract::arrayGetString($body, 'value');
                switch ($key) {
                    case MQAbstract::MSG_ORDER_CREATE:  // 订单创建
                        $tags[] = MQAbstract::MSG_ORDER_CREATE;
                        self::sendOrder($body, self::ACTION_CREATE);
                        break;
                    case MQAbstract::MSG_ORDER_UPDATE:  // 订单更新
                        $tags[] = MQAbstract::MSG_ORDER_UPDATE;
                        self::sendOrder($body, self::ACTION_UPDATE);
                        break;
                    case MQAbstract::MSG_ORDER_REBATE_SUCCESS:  // 返现成功
                        $tags[] = MQAbstract::MSG_ORDER_REBATE_SUCCESS;
                        Observer::updateRebateReturnStatus($data);
                        break;
                    //订单取消事件
                    case MQAbstract::MSG_ORDER_CANCEL:
                        $tags[] = MQAbstract::MSG_ORDER_CANCEL;
                        //退优惠券
                        Observer::returnCoupon($data);
                        //退回每日限购的数量
                        Observer::revertDailyPurchaseHistory($data);
                        //退回用户享受优惠的次数
                        Observer::revertCustomerRulesLimit($data);
                        //取消订单回退当日可用额度
                        Observer::revertBalanceDailyLimit($data);
                        break;
                    //同意取消事件
                    case MQAbstract::MSG_ORDER_AGREE_CANCEL:
                        $tags[] = MQAbstract::MSG_ORDER_AGREE_CANCEL;
                        //退优惠券
                        Observer::returnCoupon($data);
                        //退回每日限购的数量
                        Observer::revertDailyPurchaseHistory($data);
                        //退回用户享受优惠的次数
                        Observer::revertCustomerRulesLimit($data);
                        //取消订单回退当日可用额度
                        Observer::revertBalanceDailyLimit($data);
                        break;
                    //供货商拒单，订单关闭
                    case MQAbstract::MSG_ORDER_CLOSED:
                        $tags[] = MQAbstract::MSG_ORDER_CLOSED;
                        //退优惠券
                        Observer::returnCoupon($data);
                        //退回每日限购的数量
                        Observer::revertDailyPurchaseHistory($data);
                        //退回用户享受优惠的次数
                        Observer::revertCustomerRulesLimit($data);
                        //取消订单回退当日可用额度
                        Observer::revertBalanceDailyLimit($data);
                        break;
                    //超市拒单
                    case MQAbstract::MSG_ORDER_REJECTED_CLOSED:
                        $tags[] = MQAbstract::MSG_ORDER_REJECTED_CLOSED;
                        //退优惠券
                        Observer::returnCoupon($data);
                        //退回每日限购的数量
                        Observer::revertDailyPurchaseHistory($data);
                        //退回用户享受优惠的次数
                        Observer::revertCustomerRulesLimit($data);
                        //取消订单回退当日可用额度
                        Observer::revertBalanceDailyLimit($data);
                        break;
                    //手动退优惠券事件
                    case MQAbstract::MSG_ORDER_MANUAL_RETURN_COUPON:
                        //退优惠券
                        Observer::returnCoupon($data);
                        break;
                    default:
                        $tags[] = MQAbstract::MSG_INVALID_KEY;
                }
                Console::get()->log($msg, null, $tags);
                $msg->delivery_info['channel']->basic_ack($msg->delivery_info['delivery_tag']);
            });
        } catch (\Exception $e) {
            sleep(60);
            ToolsAbstract::logException($e);
        }
    }
}
