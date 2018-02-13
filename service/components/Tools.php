<?php
namespace service\components;

use common\models\SalesFlatOrder;
use common\models\SensitiveWords;
use common\redis\Keys;
use framework\components\Date;
use framework\components\SensitiveWordFilter;
use framework\components\ToolsAbstract;
use service\resources\Exception;
use Yii;
use yii\db\ActiveQuery;

/**
 * public function
 */
class Tools extends ToolsAbstract
{

    public static function numberFormat($number, $precision = 0)
    {
        return number_format($number, $precision, null, '');
    }

    public static function formatPrice($price)
    {
        return number_format($price, 2, '.', '');
    }

    public static function orderCountToday($customerId, $wholesaler_id)
    {
        //今天的时间
        $date = new Date();
        $dayFrom = $date->gmtDate('Y-m-d 00:00:00');
        $dayEnd = $date->gmtDate('Y-m-d 23:59:59');
        $query = SalesFlatOrder::find();
        $count = $query->where(['<>', 'state', SalesFlatOrder::STATUS_CANCELED])
            ->andWhere(['<>', 'state', SalesFlatOrder::STATUS_HOLDED])
            ->andWhere(['between', 'created_at', $dayFrom, $dayEnd])
            ->andWhere(['customer_id' => $customerId])
            ->andWhere(['wholesaler_id' => $wholesaler_id])
            ->count();
        return $count;
    }

	public static function getBalanceDailyLimit($customerId){
		$redis = self::getRedis();
		$key = Keys::getBalanceDailyLimitKey($customerId);

		if (!$redis->exists($key)) {
			// 不存在
			$use = 0;
		} else {
			$use = $redis->get($key);
		}

		if($use < Settings::BALANCE_DAILY_LIMIT){
			return self::formatPrice(Settings::BALANCE_DAILY_LIMIT - $use);
		}else{
			return 0;
		}
	}


	public static function filterSensitiveWords($filter_words){
        $redis = self::getRedis();
        $dict = $redis->get(SensitiveWordFilter::redisKey);
        if($dict){
            $words = unserialize($dict);
            $filter = new SensitiveWordFilter($words,0);
        }else{
            $words = SensitiveWords::find()->select('word')->column();
            $filter = new SensitiveWordFilter($words,1);
        }

        foreach ($filter_words as $field=>$name) {
            if($filter->hasSensitiveWords($name)){
                Exception::hasSensitiveWord($field.'含有敏感词，请重新填写');
            }
        }

    }

    /**
     * 比较app版本
     * @param $version1
     * @param $version2
     * @param $compareType
     * 可以是lt、le、gt、ge、eq
     */
    public static function compareVersion($version1,$version2,$compareType){
        if(!in_array($compareType,['lt','le','gt','ge','eq'])){
            return false;
        }

        if(empty($version1) || empty($version2)){
            return false;
        }

        return version_compare($version1,$version2,$compareType);
    }
}