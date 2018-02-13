<?php

namespace service\resources\sales\v1;

use common\models\salesrule\Rule;
use framework\components\ToolsAbstract;
use service\components\Tools;
use service\message\merchant\SaleRuleRequest;
use service\message\merchant\SaleRuleResponse;
use service\resources\ResourceAbstract;
use yii\db\Expression;

class getRulesByJson extends ResourceAbstract
{
    public function run($data)
    {
        $date = ToolsAbstract::getDate();
        $now = $date->date('Y-m-d H:i:s');

        $request = json_decode($data, true);
        Tools::log($request, 'getRulesByJson.log');
        $wholesalerId = $request['wholesalerId'];
        $ruleIds = $request['ruleIds'];

        $rules = [];
        //商家级别活动
        $query = Rule::find();
        $wholesalerRule = $query->where(
            ['or',
                ['and', ['rule_founder' => Rule::RULE_FOUNDER_WHOLESALER], ['store_id' => "|$wholesalerId|"], ['type' => Rule::TYPE_ORDER]],//店铺订单级
                ['and', ['rule_founder' => Rule::RULE_FOUNDER_LELAI], ['store_id' => '||'], ['type' => Rule::TYPE_ORDER]],//平台级别，店铺无关的，订单级
                ['and', ['rule_founder' => Rule::RULE_FOUNDER_LELAI], ['like', 'store_id', "|$wholesalerId|"], ['type' => Rule::TYPE_ORDER]],//平台级别，店铺相关，订单级
            ])->andWhere(['is_active' => 1])
            ->andWhere(['<=', 'from_date', $now])
            ->andWhere(['>=', 'to_date', $now])
            ->andWhere(['is_del' => 0])
            ->andWhere(['coupon_type' => Rule::COUPON_TYPE_NO_COUPON])
            ->orderBy(new Expression('type ASC,coupon_type DESC'))->asArray()->one();
        if($wholesalerRule){
            $rules[0] = $wholesalerRule;
        }

        Tools::log($rules, 'getRulesByJson.log');
        //商品级别活动
        $query = Rule::find();
        $productRules = $query->where(
            ['or',
                ['and', ['rule_founder' => Rule::RULE_FOUNDER_WHOLESALER], ['store_id' => "|$wholesalerId|"], ['type' => [Rule::TYPE_ITEM, Rule::TYPE_GROUP]], ['rule_id' => $ruleIds]],//店铺单品级、多品级
                ['and', ['rule_founder' => Rule::RULE_FOUNDER_LELAI], ['store_id' => '||'], ['type' => [Rule::TYPE_ITEM, Rule::TYPE_GROUP]], ['rule_id' => $ruleIds]],//平台级别，店铺无关的，单品级、多品级
                ['and', ['rule_founder' => Rule::RULE_FOUNDER_LELAI], ['like', 'store_id', "|$wholesalerId|"], ['type' => [Rule::TYPE_ITEM, Rule::TYPE_GROUP]], ['rule_id' => $ruleIds]],//平台级别，店铺相关，单品级、多品级
            ])->andWhere(['is_active' => 1])
            ->andWhere(['<=', 'from_date', $now])
            ->andWhere(['>=', 'to_date', $now])
            ->andWhere(['is_del' => 0])
            ->andWhere(['coupon_type' => Rule::COUPON_TYPE_NO_COUPON])
            ->orderBy(new Expression('type DESC'))->asArray()->all();

        foreach ($productRules as $productRule) {
            $rules[$productRule['rule_id']] = $productRule;
        }
        Tools::log($rules, 'getRulesByJson.log');
        return $rules;
    }

    public static function request()
    {
        return true;
    }

    public static function response()
    {
        return true;
    }
}