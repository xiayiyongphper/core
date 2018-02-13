<?php
namespace common\models;

use framework\db\ActiveRecord;
use Yii;

/**
 * Class SensitiveWords
 * @package common\models
 * @property integer $entity_id
 * @property string $word
 * @property string $created_at
 *
 */
class SensitiveWords extends ActiveRecord
{
    
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'sensitive_words';
    }

    /**
     * @return \yii\db\Connection the database connection used by this AR class.
     */
    public static function getDb()
    {
        return Yii::$app->get('commonDb');
    }
}