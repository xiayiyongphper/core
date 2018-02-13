<?php
/**
 * Created by PhpStorm.
 * User: zgr0629
 * Date: 8/12/2016
 * Time: 5:48 PM
 */

namespace service\resources\sales\v1;

use common\models\SalesFlatOrder;
use framework\components\Date;
use service\components\Tools;
use service\message\common\SourceEnum;
use service\message\sales\getCustomerOrderStatRequest;
use service\message\sales\getCustomerOrderStatResponse;
use service\resources\ResourceAbstract;
use yii\db\Expression;

class getCustomerOrderStat extends ResourceAbstract
{
	public function run($data){

		/** @var getCustomerOrderStatRequest $request */
		$request = self::request();
		$request->parseFromString($data);

		if($this->getSource() == SourceEnum::ANDROID_CONTRACTOR || $this->getSource() == SourceEnum::IOS_CONTRACTOR){
            //来自业务员的请求，不验证用户
        }else{
            $this->_initCustomer($request);
        }


		// 统计每月分布
		$stat_list = SalesFlatOrder::find()
			->addSelect([
				new Expression("DATE_FORMAT(DATE_ADD(`created_at`, INTERVAL 8 HOUR), '%Y-%m') as `year_month`"),
				new Expression("sum(grand_total) as `sum_grand_total`"),
				new Expression("count(*) as `order_count`")
			])
			->where(['customer_id'=>$request->getCustomerId()])
            ->andWhere(['not in','state',SalesFlatOrder::INVALID_ORDER_STATE()])
            ->andWhere(['customer_tag_id' => 1, 'merchant_type_id' => 1])
			->groupBy(new Expression("`year_month`"))
			->orderBy(new Expression("`year_month` desc"))
			->asArray()
			->all()
			;
		// 统计所有
		$stat_all = [
			'sum_grand_total'=>0,
			'order_count'=>0,
		];
		foreach ($stat_list as $item) {
			$stat_all['sum_grand_total'] += $item['sum_grand_total'];
			$stat_all['order_count'] += $item['order_count'];
		}
		//Tools::log($stat_list);

		$ret = Tools::pb_array_filter([
			'stat_all' => $stat_all,
			'stat_list' => $stat_list,
		]);
		//Tools::log($ret);
		$response = self::response();
		$response->setFrom($ret);
		return $response;
	}

//    private function getOrders($condition){
//        $orders = new SalesFlatOrder();
//        $orders->find()->where($condition);
//    }

	public static function request()
	{
		return new getCustomerOrderStatRequest();
	}

	public static function response()
	{
		return new getCustomerOrderStatResponse();
	}

}