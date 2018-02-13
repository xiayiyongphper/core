<?php

namespace common\models;

use framework\db\ActiveRecord;
use Yii;

/**
 * This is the model class for table "dataflow_batch_import_file".
 *
 * @property integer $entity_id
 * @property integer $profile_id
 * @property integer $store_id
 * @property string $adapter
 * @property string $status
 * @property string $file
 * @property resource $message
 * @property string $created_at
 * @property string $schedule_at
 * @property string $start_at
 * @property string $complete_at
 * @property string $updated_at
 * @property string $import_type
 */
class DataflowBatchImportFile extends ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'dataflow_batch_import_file';
    }

    /**
     * @return \yii\db\Connection the database connection used by this AR class.
     */
    public static function getDb()
    {
        return Yii::$app->get('commonDb');
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['profile_id', 'store_id', 'adapter'], 'required'],
            [['profile_id', 'store_id'], 'integer'],
            [['message'], 'string'],
            [['created_at', 'schedule_at', 'start_at', 'complete_at', 'updated_at'], 'safe'],
            [['adapter'], 'string', 'max' => 128],
            [['status'], 'string', 'max' => 32],
            [['file'], 'string', 'max' => 255],
            [['import_type'], 'string', 'max' => 60]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'entity_id' => 'Entity Id',
            'profile_id' => 'Profile Id',
            'store_id' => 'Store Id',
            'adapter' => 'Adapter',
            'status' => 'Status',
            'file' => 'File',
            'message' => 'Message',
            'created_at' => 'Created At',
            'schedule_at' => 'Schedule At',
            'start_at' => 'Start At',
            'complete_at' => 'Complete At',
            'updated_at' => 'Updated At',
            'import_type' => '导入类型',
        ];
    }
}
