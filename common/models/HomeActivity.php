<?php
namespace common\models;

use framework\db\ActiveRecord;
use Yii;

/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2016/1/26
 * Time: 18:07
 * @property integer $entity_id
 * @property string $url
 * @property integer $count
 * @property integer $city
 * @property string $area
 * @property integer $status
 * @property string $created_at
 * @property string $url_backup
 * @property string $compare_type
 * @property string $version
 *
 */
class HomeActivity extends ActiveRecord
{
    
    const NORMAL = 1;
    
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'home_activity';
    }

    /**
     * @return \yii\db\Connection the database connection used by this AR class.
     */
    public static function getDb()
    {
        return Yii::$app->get('mainDb');
    }
}