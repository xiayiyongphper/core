<?php
namespace console\controllers;

use yii\console\Controller;
use service\models\UniqueOrderId;
use framework\resources\ApiAbstract;
use framework\components\ToolsAbstract;

/**
 * Site controller
 */
class TestOrderIdController extends Controller
{
    public function actionRun()
    {
        $arr = array();
        $task_id = ApiAbstract::getWorkerId();
        $uniqueIdClass = new UniqueOrderId(1,$task_id);
        for($i=0;$i<1000;$i++){
            $arr []= $uniqueIdClass->nextId();
        }

        ToolsAbstract::log($arr,'order_id.log');
        ToolsAbstract::log(count($arr),'order_id.log');
        $arr = array_unique($arr);
        ToolsAbstract::log(count($arr),'order_id.log');
    }

}
