<?php

namespace console\models;

use Yii;
use \yii\db\ActiveRecord;


/**
 * 统计数据
 * @author Jason
 * @property integer $entity_id
 * @property integer $city
 * @property string $date
 * @property string $contractor_id
 * @property float $sales_total
 * @property integer $first_users
 * @property integer $orders_count
 * @property integer $active_users
 * @property integer $core_users
 */
class ContractorStatisticsData extends ActiveRecord
{

    public $first_users_total;
    public $orders_count_total;

    public static function tableName()
    {
        return 'contractor_statistics_data';
    }

    public static function getDb()
    {
        return Yii::$app->get('customerDb');
    }
}
