<?php
namespace console\controllers;

use common\models\SalesFlatOrder;
use framework\components\cache\contractor\LeContractor;
use framework\components\mq\Order;
use service\message\core\ConfigRequest;
use service\resources\core\v1\config;
use yii\console\Controller;

/**
 * Site controller
 */
class TestController extends Controller
{
    /**
     * 用户ID
     * @var integer
     */
    protected $customerId = 35;

    /**
     * TOKEN
     * @var string
     */
    protected $authToken = 'KBovpuxTtPUbhq28';

    public function actionIndex()
    {
        $customer = SalesFlatOrder::find()->limit(12)->count();
        print_r($customer);
    }

    public function actionManualRebate()
    {
        $maxId = 0;
        for ($i = 0; $i < 200; $i++) {
            $collection = SalesFlatOrder::find()
                ->where(['>', 'created_at', '2017-06-07 00:00:00'])
                ->andWhere(['in', 'status', ['pending_comment', 'complete']])
                ->andWhere(['rebate_return_status' => 0])
                ->andWhere(['>', 'entity_id', $maxId])
                ->asArray()
                ->limit(50)
                ->orderBy(['entity_id' => SORT_ASC])
                ->all();
            foreach ($collection as $order) {
                Order::publishOrderManualRebateEvent($order);
                echo $order['entity_id'] . PHP_EOL;
                $maxId = $order['entity_id'];
            }
        }
    }

}
