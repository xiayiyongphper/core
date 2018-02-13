<?php

namespace service\resources\sales\v1;

use common\models\salesrule\Rule;
use service\components\Tools;
use service\message\core\AllSaleRuleRequest;
use service\message\core\AllSaleRuleResponse;
use service\resources\ResourceAbstract;

class allSaleRule extends ResourceAbstract
{
    public function run($data)
    {
        /** @var SaleRuleRequest $request */
        $request = self::request();
        $request->parseFromString($data);
        $response = self::response();
        $wholesaler_id = $request->getWholesalerId();
        $promotions = Rule::getWholesalerAllPromotions($wholesaler_id);
        $response->setFrom(Tools::pb_array_filter(['promotions' => $promotions]));
        return $response;
    }

    public static function request()
    {
        return new AllSaleRuleRequest();
    }

    public static function response()
    {
        return new AllSaleRuleResponse();
    }
}