<?php

namespace console\controllers;

use framework\components\ToolsAbstract;
use yii\console\Controller;

/**
 * Site controller
 */
class LuaController extends Controller
{
    protected $customerId = 35;
    protected $authToken = '123456789';
    protected $wholesaler_id = 1;

    public function actionIndex()
    {
        $redis = ToolsAbstract::getRedis();
        $script = <<<SCRIPT
local function checkLimiter(_keys, _values)
    local flag = 1
    if  table.getn(_keys) == 3 then
        local id = _values[1]
        local window = tonumber(_values[2])
        local size = tonumber(_values[3])
        if redis.call("EXISTS", id) == 1 then
           if redis.call("INCR", id) > size then
               redis.call("DECR", id)
               flag = -2
           end
        else
            if redis.call("INCR", id) <= size then
               redis.call("EXPIRE", id, window)
            else
                redis.call("DEL", id)
                flag = -3
            end
        end
    else
        flag = -1
    end
    return flag
end

local availability = checkLimiter(KEYS, ARGV)
return availability
SCRIPT;
//        $ret = $redis->eval($script, ['qty_441800_1', 'qty_441800_2', 'qty_441800_3', 5, 1, 10], 3);
        //$ret = ToolsAbstract::subtractInventory(['qty_441800_1' => 5, 'qty_441800_2' => 1, 'qty_441800_3' => 1]);
        $ret = $redis->eval($script, ['id', 'w', 's', 'qty_441800_1', 31, 1], 3);
        var_dump($ret);
    }


}
