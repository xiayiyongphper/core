<?php

namespace common\models;

use framework\db\ActiveRecord;
use Yii;

/**
 * This is the model class for table "region".
 *
 * @property integer $entity_id
 * @property integer $parent_id
 * @property string $chinese_name
 * @property integer $code
 * @property string $first_letter
 * @property string $path
 * @property integer $level
 * @property integer $position
 * @property integer $status
 */
class Region extends ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'region';
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
            [['parent_id', 'code', 'level', 'position', 'status'], 'integer'],
            [['chinese_name'], 'string', 'max' => 120],
            [['first_letter'], 'string', 'max' => 2],
            [['path'], 'string', 'max' => 255]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'entity_id' => 'Entity ID',
            'parent_id' => 'Parent ID',
            'chinese_name' => 'Chinese Name',
            'code' => 'Code',
            'first_letter' => 'First Letter',
            'path' => 'Path',
            'level' => 'Level',
            'position' => 'Position',
            'status' => 'Status',
        ];
    }
}
