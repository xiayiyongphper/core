<?php

namespace common\models;

use Yii;
use framework\db\ActiveRecord;

/*
 * 业务员拜访记录表
 * */
class LeContractor extends ActiveRecord
{


    public static function tableName()
    {
        return 'contractor';
    }

    public static function getDb()
    {
        return Yii::$app->get('customerDb');
    }
}