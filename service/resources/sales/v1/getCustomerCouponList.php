<?php
/**
 * Created by PhpStorm.
 * User: zgr0629
 * Date: 8/12/2016
 * Time: 5:48 PM
 */

namespace service\resources\sales\v1;

use common\models\salesrule\Rule;
use common\models\salesrule\UserCoupon;
use framework\components\Date;
use framework\components\ToolsAbstract;
use framework\data\Pagination;
use service\components\Tools;
use service\message\common\SourceEnum;
use service\message\sales\getCustomerCouponListRequest;
use service\message\sales\getCustomerCouponListResponse;
use service\resources\ResourceAbstract;
use yii\db\Expression;

class getCustomerCouponList extends ResourceAbstract
{
	const PAGE_SIZE = 10;
	const MAX_PAGE_SIZE = 50;

	public function run($data){

		/** @var getCustomerCouponListRequest $request */
		$request = self::request();
		$request->parseFromString($data);

        if($this->getSource() == SourceEnum::ANDROID_CONTRACTOR || $this->getSource() == SourceEnum::IOS_CONTRACTOR){
            //来自业务员的请求，不验证用户
        }else{
            $this->_initCustomer($request);
        }

		$date = ToolsAbstract::getDate();
		$currentDate = $date->date('Y-m-d H:i:s');

		// 是否查询过期券
		$is_expire_list = $request->getExpireList() ? $request->getExpireList() : 0;
		if($is_expire_list){
			// 查询已过期的
			$date_operator = '<';
		}else{
			// 查未过期的
			$date_operator = '>=';
		}

		$coupons = UserCoupon::find()
			->joinWith('rule')
			->where(['salesrule_user_coupon.state' => UserCoupon::USER_COUPON_UNUSED,])
			->andWhere([$date_operator, 'salesrule_user_coupon.expiration_date', $currentDate])
			->andWhere(['salesrule_user_coupon.customer_id' => $request->getCustomerId()])
			//->groupBy('salesrule_user_coupon.rule_id')
			//->limit(UserCoupon::MAX_COUPON_LIMIT)
			->orderBy(new Expression('salesrule_user_coupon.created_at DESC'))
			//->all();
		;
        //Tools::log($coupons->createCommand()->getRawSql(), 'hl.log');
		$totalCount = $coupons->count();

		//分页
        $page = $request->getPagination() && $request->getPagination()->getPage() ? $request->getPagination()->getPage() : 1;
        $page_size = $request->getPagination() && $request->getPagination()->getPageSize() ? $request->getPagination()->getPageSize() : self::PAGE_SIZE;
        if ($page_size <= 0) {
            $page_size = self::PAGE_SIZE;
        }elseif($page_size>self::MAX_PAGE_SIZE){
            $page_size = self::MAX_PAGE_SIZE;
        }

		$pages = new Pagination(['totalCount' => $totalCount]);
		$pages->setCurPage($page);
		$pages->setPageSize($page_size);

		$responsePage = [
			'total_count' => $pages->getTotalCount(),
			'page' => $pages->getCurPage(),
			'last_page' => $pages->getLastPageNumber(),
			'page_size' => $pages->getPageSize(),
		];

		if ($page > $pages->getLastPageNumber()) {
			$page = $pages->getLastPageNumber();
		}
		if ($page <= 0) {
			$page = 1;
		}

		$coupons = $coupons->offset(($page - 1) * $page_size)->limit($page_size)->all();

		//Tools::log($coupons);
		$coupon_list = [];
		/** @var UserCoupon $coupon */
		foreach ($coupons as $coupon) {
			if (!$coupon || !$coupon->rule || !$coupon->rule->rule_id) {
				//优惠券查询不到对应的活动，跳过
				continue;
			}

			// 优惠券跳转
			$url = '';
			// 单品级跳商品详情
			if($coupon->rule->type==1){
				$wholesaler_ids = explode('|', $coupon->rule->store_id);
				$wholesaler_ids = array_filter($wholesaler_ids);
				$wholesaler_id = array_shift($wholesaler_ids);
				$product_id = $coupon->rule->product_id;
				if($wholesaler_id && $product_id){
					$url = "lelaishop://prod/info?wid={$wholesaler_id}&pid={$product_id}";
				}
			}
			// 多品级跳优惠专题页
			elseif($coupon->rule->type==2){
				$url = "lelaishop://topicV4/list?rid={$coupon->rule->rule_id}";
			}
			// 订单级如果是单个商家则跳商家页
			elseif($coupon->rule->type==3){
				$wholesaler_ids = array_filter(explode('|', $coupon->rule->store_id));
				if(count($wholesaler_ids)==1){
					$wholesaler_id = array_shift($wholesaler_ids);
					$url = "lelaishop://shop/info?wid={$wholesaler_id}";
				}
			}

			// 使用条件语句
			$use_condition = '';
			$rule_conditions = unserialize($coupon->rule->conditions_serialized);
			if(isset($rule_conditions['conditions']['0'])){
				$condition = $rule_conditions['conditions']['0'];
				$action_levels = Rule::getCondition($condition['value'], $coupon->rule->discount_amount);
				$conditionInfo = Rule::getCouponConditionInfo($action_levels, $condition['attribute']);
				$use_condition = $conditionInfo['use_condition'];
			}

			//有效期
			$validity_time = '';
			if($coupon->rule->from_date){
			    $validity_time = date("m月d日 H:i",strtotime($coupon->rule->from_date));
            }
            $validity_time .= " 至 ".date("m月d日 H:i",strtotime($coupon->expiration_date))."有效";

			$coupon_list[] = [
				'entity_id' => $coupon->entity_id,
				'customer_id' => $coupon->customer_id,
				'state' => $coupon->state,
				'rule_id' => $coupon->rule_id,
				'expiration_date' => $coupon->expiration_date,
				'source' => $coupon->source,
				'created_at' => $coupon->created_at,
				'coupon_title' => $coupon->rule->coupon_title,
				'frontnote' => $coupon->rule->frontnote,
				'discount_type' => $coupon->rule->getDiscountType(),
				'discount' => $coupon->rule->getDiscountAmount(),
				'use_condition' => $use_condition,
				'is_soon_expire' => $coupon->isSoonExpire(),
				'url' => $url,
                'validity_time' => $validity_time
			];

		}

		//Tools::log($coupon_list);
		//Tools::log($responsePage);

		$response = self::response();
		$response->setFrom(Tools::pb_array_filter([
			'coupon_list' => $coupon_list,
			'pagination' => $responsePage,
		]));
		return $response;
	}

	public static function request()
	{
		return new getCustomerCouponListRequest();
	}

	public static function response()
	{
		return new getCustomerCouponListResponse();
	}

}