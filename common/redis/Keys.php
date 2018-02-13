<?php
namespace common\redis;
use framework\components\ToolsAbstract;

/**
 * Created by PhpStorm.
 * User: henryzhu
 * Date: 16-6-30
 * Time: 上午11:33
 */
class Keys
{
    /**
     *
     * 获取用户每日钱包已使用额度的key
     *
     * @param $customerId
     *
     * @return string
     */
    public static function getBalanceDailyLimitKey($customerId)
    {
        return 'balance_daily_limit_' . $customerId;
    }


    /**
     * 获取每日限购的ｋｅｙ
     * @param $customerId
     * @param $city
     * @return string
     */
    public static function getDailyPurchaseHistory($customerId, $city)
    {
        return 'daily_purchase_history_' . $customerId . '_' . $city;
    }

    public static function getRedisESQueueKey()
    {
        return ENV_SYS_NAME . '_es_queue';
    }

    /**
     * 获取用户享受优惠活动次数key（总次数）
     * @param int $customerId
     * @param int $ruleId
     * @return string
     */
    public static function getEnjoyTimesKey($customerId, $ruleId)
    {
        return 'enjoy_times_key_' . $customerId . '_' . $ruleId;
    }

    /**
     * 获取用户享受优惠活动每天的次数的key
     * @param int $customerId
     * @param int $ruleId
     * @param string $date YYYY-MM-DDD格式，null取当天
     * @return string
     */
    public static function getEnjoyDailyTimesKey($customerId, $ruleId, $date = null)
    {
        $date = (null === $date) ? ToolsAbstract::getDate()->date('Y-m-d') : $date;
        return sprintf('enjoy_times_key_%s_%s_%s', $customerId, $ruleId, $date);
    }
}