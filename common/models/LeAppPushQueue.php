<?php

namespace common\models;

use framework\db\ActiveRecord;
use Yii;

/**
 * This is the model class for table "le_app_push_queue".
 *
 * @property integer $entity_id
 * @property string $token
 * @property integer $group_id
 * @property integer $system
 * @property integer $type
 * @property integer $channel
 * @property integer $platform
 * @property integer $value_id
 * @property string $params
 * @property string $checksum
 * @property string $message
 * @property integer $status
 * @property integer $priority
 * @property string $created_at
 * @property string $scheduled_at
 * @property string $send_at
 * @property integer $typequeue
 */
class LeAppPushQueue extends ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'le_app_push_queue';
    }

    /**
     * @return \yii\db\Connection the database connection used by this AR class.
     */
    public static function getDb()
    {
        return Yii::$app->get('customerDb');
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['token', 'system', 'channel', 'value_id', 'checksum'], 'required'],
            [['group_id', 'system', 'type', 'channel', 'platform', 'value_id', 'status', 'priority', 'typequeue'], 'integer'],
            [['params', 'message'], 'string'],
            [['created_at', 'scheduled_at', 'send_at'], 'safe'],
            [['token'], 'string', 'max' => 100],
            [['checksum'], 'string', 'max' => 32]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'entity_id' => 'primary ID',
            'token' => 'device push token',
            'group_id' => 'Group ID',
            'system' => 'Phone OS:1=iOS,2=Android',
            'type' => 'Type:1=customer,2= merchant ,3= courier',
            'channel' => '1XXXXX：IOS正式版       2XXXXX：IOS企业版        3XXXXX：android',
            'platform' => '1:订货网  2:商家版',
            'value_id' => 'value id may be customer_id,merchant_id and courier_id',
            'params' => 'params',
            'checksum' => 'Checksum',
            'message' => 'push result',
            'status' => 'status,0:pending,1:success,2:failure',
            'priority' => 'priority',
            'created_at' => 'Created Time',
            'scheduled_at' => 'Scheduled Time',
            'send_at' => 'Send Time',
            'typequeue' => '默认0：百度推送   1：极光推送',
        ];
    }
}
