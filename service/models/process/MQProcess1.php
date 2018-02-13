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
 * Class MQProcess1
 * @package service\models\process
 */
class MQProcess1 implements ProcessInterface
{
    const ACTION_CREATE = 'create';
    const ACTION_UPDATE = 'update';
    const ACTION_DELETE = 'delete';
    const ACTION_INDEX = 'index';

    /**
     * @param SWServer $SWServer
     * @param \swoole_process $process
     */
    public function run(SWServer $SWServer, \swoole_process $process)
    {
        $esClusters = \Yii::$app->params['es_cluster'];
        if (!isset($esClusters['hosts'], $esClusters['size'])) {
            while (true) {
                ToolsAbstract::logException(new \Exception('es cluster config not set', 999));
                sleep(60);
            }
        }
        $hosts = $esClusters['hosts'];
        $client = ClientBuilder::create()
            ->setHosts($hosts)
            ->build();
        $size = $esClusters['size'];//bytes
        $batchSize = 0;//bytes
        while (true) {
            try {
                $files = glob(ToolsAbstract::getESOrderPath() . DIRECTORY_SEPARATOR . "*.bin");
                if (is_array($files) && count($files) > 0) {
                    $params = ['body' => []];
                    foreach ($files as $file) {
                        $fp = fopen($file, 'r');
                        while (!feof($fp)) {
                            $json = fgets($fp);
                            if (!$json) continue;
                            $line = json_decode($json, true);
                            if (is_array($line) && isset($line['index'], $line['type'], $line['body'], $line['action'], $line['__id__'])) {
                                $action = $line['action'];
                                $allowedActions = [
                                    self::ACTION_CREATE,
                                    self::ACTION_UPDATE,
                                ];
                                if (!in_array($action, $allowedActions)) {
                                    continue;
                                }

                                $params['body'][] = [
                                    $action => [
                                        '_index' => $line['index'],
                                        '_type' => $line['type'],
                                        '_id' => $line['__id__']
                                    ]
                                ];

                                if ($action == self::ACTION_UPDATE) {
                                    $params['body'][] = ['doc' => $line['body']];
                                } else if ($action == self::ACTION_CREATE) {
                                    $params['body'][] = $line['body'];
                                }
                                $batchSize = $batchSize + strlen($json);

                                if ($batchSize > $size) {
                                    if ($batchSize < ENV_ES_CLUSTER_BULK_SIZE_MAX) {
                                        $response = $client->bulk($params);
                                        self::processESResponse($response);
                                    } else {
                                        //no bulk request,data will be removed directly
                                        ToolsAbstract::log('over batch size:' . $batchSize);
                                    }
                                    $params = ['body' => []];
                                    $batchSize = 0;
                                }
                            }
                        }
                        fclose($fp);
                    }

                    if ($batchSize > 0) {
                        if ($batchSize < ENV_ES_CLUSTER_BULK_SIZE_MAX) {
                            $response = $client->bulk($params);
                            self::processESResponse($response);
                        } else {
                            //no bulk request,data will be removed directly
                            ToolsAbstract::log('over batch size:' . $batchSize);
                        }
                        $params = ['body' => []];
                        $batchSize = 0;
                    }
                }
                sleep(60);
            } catch (\Exception $e) {
                ToolsAbstract::logException($e);
                sleep(60);
            }
        }
    }

    /**
     * process elastic search bulk update/create response,
     * 处理es批量更新/创建的返回结果
     * 1. success records will be removed
     * 2. fail records will be renamed
     * @param $response
     * @return bool
     */
    protected static function processESResponse($response)
    {
        if (!is_array($response) || !isset($response['errors'], $response['took'], $response['items'])) {
            ToolsAbstract::log($response, 'mq_es_response_error.log');
            return false;
        }
        $errors = $response['errors'];
        $items = $response['items'];
        if ($errors) {
            /***there are errors happened,when trying to bulk update data**/
            ToolsAbstract::log($response, 'mq_es_response_error.log');
        }
        $path = ToolsAbstract::getESOrderPath();
        foreach ($items as $item) {
            ToolsAbstract::log($item, 'mq_es_response.log');
            foreach ($item as $action => $data) {
                if (!isset($data['_index'], $data['_type'], $data['_id'], $data['status'])) {
                    /**
                     * invalid item,to do nothing
                     */
                    continue;
                }

                $id = $data['_id'];
                $file = sprintf('%s%s.bin', $path . DIRECTORY_SEPARATOR, $id);
                if (isset($data['error']) && $data['error']) {
                    /**
                     * error record will be renamed
                     */
                    $newname = sprintf('%s%s.error', $path . DIRECTORY_SEPARATOR, $id);
                    rename($file, $newname);
                } else {
                    /**
                     * success uploaded record should be removed
                     */
                    unlink($file);
                }
            }
        }
        return true;
    }
}
