<?php
namespace console\controllers;

use common\models\SalesFlatOrder;
use Elasticsearch\ClientBuilder;
use framework\components\es\Collectd;
use framework\components\es\Console;
use framework\components\es\Order;
use framework\components\es\Timeline;
use framework\components\ToolsAbstract;
use service\message\common\KeyValueItem;
use Yii;
use yii\console\Controller;

/**
 * Site controller
 */
class EsController extends Controller
{
    protected $client;
    protected $hosts;

    protected function getClient()
    {
        $esClusters = \Yii::$app->params['es_cluster'];
        if (!isset($esClusters['hosts'], $esClusters['size'])) {
            ToolsAbstract::logException(new \Exception('es cluster config not set', 999));
        }
        $this->hosts = $hosts = $esClusters['hosts'];
        if (!$this->client) {
            $this->client = ClientBuilder::create()
                ->setHosts($this->hosts)
                ->build();
        }
        return $this->client;
    }

    public function actionInit()
    {
        for($i = 16;$i<55;$i++){
            Timeline::get()->createIndex(".timeline-2017-{$i}");
            Console::get()->createIndex(".console-2017-{$i}");
            Collectd::get()->createIndex(".collectd-2017-{$i}");
        }
    }

    public function actionOrderInit()
    {
        $hosts = [
            'admin:hzXRDDAZvM@121.201.110.245:19200',
        ];
        Order::get()->deleteIndex(null, $hosts);
        Order::get()->createIndex(null, $hosts);
    }

    public function actionTest()
    {
        $item = new KeyValueItem();
        $item->setKey('s234');
        $item->setValue('123');
        print_r($item->toArray());
    }

    /**
     *ES订单与数据库订单一致性检查
     */
    public function actionCheckOrderConsistency()
    {
        $query = SalesFlatOrder::find();
        echo $query->count() . PHP_EOL;
        $hosts = [
            'admin:hzXRDDAZvM@121.201.110.245:19200',
        ];
        $body = [
            'query' => [
                'match' => [
                    'wholesaler_id' => 35
                ]
            ]
        ];
        Order::get()->search($body, $hosts);
    }

    public function actionAppRequestLogExport()
    {

    }
}
