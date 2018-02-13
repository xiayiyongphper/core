<?php
namespace service\resources\sales\v1;

use service\components\Tools;
use service\message\sales\GreyListRequest;
use service\message\sales\GreyListRule;
use service\message\sales\GreyListResponse;
use service\resources\ResourceAbstract;
use framework\components\ToolsAbstract;
use common\models\SalesFlatOrder;

class greyList extends ResourceAbstract
{
    public function run($data)
    {
        /** @var GreyListRequest $request */
        $request = self::request();
        $request->parseFromString($data);
        $response = self::response();

        $rules = $request->getRules();

        $grey_list = array();
        /** @var GreyListRule $rule */
        foreach ($rules as $rule){
            $days = intval($rule->getDays());
            $seckill_times = intval($rule->getSeckillTimes());
            $end_time = ToolsAbstract::getDate()->date('Y-m-d 00:00:00');//今天凌晨
            $start_time = ToolsAbstract::getDate()->date('Y-m-d 00:00:00',strtotime($end_time) - 24 * 3600 * $days);
            //所有状态的订单都要统计
            $query = SalesFlatOrder::find()->select('COUNT(DISTINCT(activity_id))  as `act_num`,`customer_id`,`city`')
                ->where(['between','created_at',$start_time,$end_time])
                ->andWhere(['city' => $rule->getCity()])
                ->andWhere(['>','activity_id',0])
                ->groupBy('customer_id')
                ->having(['>=', '`act_num`', $seckill_times]);
            Tools::log($query->createCommand()->getRawSql());
            $data = $query->asArray()->all();

            Tools::log($data,'hl.log');
            $grey_list = array_merge($grey_list,$data);
        }
        $response->setFrom(Tools::pb_array_filter(['grey_list' => $grey_list]));

        return $response;
    }

    public static function request()
    {
        return new GreyListRequest();
    }

    public static function response()
    {
        return new GreyListResponse();
    }
}