<?php
namespace service\models;

use framework\core\ProcessInterface;
use framework\core\SWServer;
use framework\components\ToolsAbstract;
use service\components\Logger;
use service\components\Tools;

/**
 * Created by PhpStorm.
 * User: henryzhu
 * Date: 16-6-2
 * Time: 上午11:12
 */
class Process implements ProcessInterface
{
    /**
     * @inheritdoc
     */
    public function run(SWServer $SWServer, \swoole_process $process)
    {
        $redis = ToolsAbstract::getRedis();
        $message_key = ToolsAbstract::getRedisMsgQueueKey();
        while (true) {
            try {
                //Tools::log('=============', 'wangyang.txt');
                //Tools::log(self::getRedisMsgQueueKey(), 'wangyang.txt');
                //Tools::log($message_key, 'wangyang.txt');
                //Tools::log($redis->lLen($message_key), 'wangyang.txt');
                $event = $redis->rPop($message_key);
                if ($event) {
                    Tools::log('=============', 'message.txt');
                    Tools::log($event, 'message.txt');
                    $data = json_decode($event, true);
                    Tools::log($data, 'message.txt');
                    Tools::log('=============', 'message.txt');
                    $event_name = $data['name'];
                    Tools::log($event_name, 'message.txt');
                    $events_all = \Yii::$app->params['events'];
                    $event = isset($events_all[$event_name]) ? $events_all[$event_name] : '';
                    if ($event) {
                        foreach ($event as $key => $value) {
                            if (isset($value['class'])) {
                                Tools::log('##############', 'message.txt');
                                Tools::log($event, 'message.txt');
                                Tools::log('##############', 'message.txt');
                                $model = new $value['class']();
                                Tools::log($model, 'message.txt');
                                try {
                                    $model->{$value['method']}($data['data']);
                                } catch (\Exception $e) {
                                    Tools::log($e->getMessage(), 'message.txt');
                                    Tools::log($e->getTraceAsString(), 'message.txt');
                                }
                            } else {
                                Tools::log('command error', 'message.txt');
                            }
                        }
                    } else {
                        continue;
                    }
                } else {
                    if ($redis->lLen($message_key) == 0) {
                        sleep(1);
                    }
                }
            } catch (\Exception $e) {
                Tools::logException($e);
            }
        }
    }
}
