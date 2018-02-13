<?php
namespace common\models\salesrule;

use Yii;
use framework\db\ActiveRecord;

/**
 * Class Usage
 * @package common\models\salesrule
 *
 * @property integer $entity_id
 * @property integer $customer_id
 * @property integer $rule_id
 * @property integer $status
 * @property integer $salesrule_user_coupon_id
 * @property string $created_at
 * @property string $device_id
 * @property integer $order_id
 *
 */
class Usage extends ActiveRecord
{

    const COUPON_RECEIVE = 'receive';
    const COUPON_USED = 'use';
    const COUPON_RETURN = 'return';
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'salesrule_usage';
    }

    /**
     * @return \yii\db\Connection the database connection used by this AR class.
     */
    public static function getDb()
    {
        return Yii::$app->get('mainDb');
    }
}