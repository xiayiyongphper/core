<?php

namespace common\models;

use framework\db\ActiveRecord;
use Yii;

/**
 * This is the model class for table "le_import_log".
 *
 * @property integer $entity_id
 * @property integer $store_id
 * @property integer $dataflow_id
 * @property integer $type_id
 * @property string $file_name
 * @property string $save_name
 * @property integer $total_row
 * @property integer $success
 * @property string $result
 * @property integer $status
 * @property string $other
 * @property string $create_date
 */
class LeImportLog extends ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'le_import_log';
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
            [['store_id', 'dataflow_id', 'type_id', 'file_name', 'save_name', 'total_row', 'status', 'other', 'create_date'], 'required'],
            [['store_id', 'dataflow_id', 'type_id', 'total_row', 'success', 'status'], 'integer'],
            [['result', 'other'], 'string'],
            [['create_date'], 'safe'],
            [['file_name'], 'string', 'max' => 100],
            [['save_name'], 'string', 'max' => 200]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'entity_id' => 'Entity id',
            'store_id' => 'Store id',
            'dataflow_id' => 'table dataflow_batch_import_file entity_id',
            'type_id' => '1:import product.2: open or close product.3:other',
            'file_name' => 'upload file name.',
            'save_name' => 'upload save name.',
            'total_row' => 'upload total row.',
            'success' => 'upload success row.',
            'result' => 'import Result log.',
            'status' => 'File Handle status.',
            'other' => 'other info.',
            'create_date' => 'upload date',
        ];
    }
}
