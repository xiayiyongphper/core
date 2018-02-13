<?php
namespace service\resources\sales\v1;

use common\models\SalesFlatOrder;
use framework\components\Date;
use framework\components\ToolsAbstract;
use service\components\Tools;
use service\message\common\Order;
use service\message\sales\GetCustomerFirstOrderRequest;
use service\resources\Exception;
use service\resources\ResourceAbstract;


/**
 * Created by PhpStorm.
 * User: ryan
 * Date: 2017/07/26
 * Time: 15:09
 */
class customerFirstOrder extends ResourceAbstract
{
    public function run($data)
    {
        /** @var \service\message\sales\GetCustomerFirstOrderRequest $request */
        $request = self::request();
        $request->parseFromString($data);
        $date = new Date();
        $response = new Order();
        $customer_id = $request->getCustomerId();
        $start_date = $request->getStartDate();
        $end_date = $request->getEndDate();
        $start_date = $start_date ? $date->date("Y-m-d H:i:s",$start_date) : '';
        $end_date = $end_date ? $date->date("Y-m-d H:i:s",$end_date." 23:59:59") : '';
        //Tools::log($start_date,'customerFirstOrder.log');
        //Tools::log($end_date,'customerFirstOrder.log');

        $contractor_id = $request->getContractorId();
        $city = $request->getCity();

        if (!$customer_id) {
            throw new \Exception('非法参数',111111);
        }

        /** @var SalesFlatOrder $order */
        $query = SalesFlatOrder::find()
            ->where(['customer_id' => $customer_id]);
        if($start_date){
            $query->andWhere(['>=','created_at',$start_date]);
        }
        if($end_date){
            $query->andWhere(['<=','created_at',$end_date]);
        }
        if($contractor_id){
            $query->andWhere(['contractor_id' => $contractor_id]);
        }
        if($city){
            $query->andWhere(['city' => $city]);
        }

        $query->andWhere(['customer_tag_id' => SalesFlatOrder::CUSTOMER_TAG_ID_NORMAL])
            ->andWhere(['not in','state',SalesFlatOrder::INVALID_ORDER_STATE()])
            ->andWhere(['not in','wholesaler_id',SalesFlatOrder::excludeWholesalerIds()])
            ->andWhere(['not like','wholesaler_name',['t','T','特通渠道','乐来供应链','测试']])
            ->orderBy(['created_at' => SORT_ASC]);

        //Tools::log($query->createCommand()->getRawSql(),'customerFirstOrder.log');
        $order = $query->asArray()->one();

        //Tools::log($order,'customerFirstOrder.log');

        $orderData = [];
        if (!empty($order)) {
            $orderData = [
                'order_id' => $order['entity_id'],
                'created_at' => $date->date('Y-m-d H:i:s', $order['created_at']),
                'contractor_id' => $order['contractor_id'],
                'city' => $order['city'],
            ];
        }

        $response->setFrom(Tools::pb_array_filter($orderData));
        return $response;
    }

    public static function request()
    {
        return new GetCustomerFirstOrderRequest();
    }

    public static function response()
    {
        return new Order();
    }
}
