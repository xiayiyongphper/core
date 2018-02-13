<?php

namespace common\models;

use Yii;
use framework\db\ActiveRecord;

/*
 * 业务员拜访记录表
 * */
class LeCustomer extends ActiveRecord
{


    public static function tableName()
    {
        return 'le_customers';
    }

    public static function getDb()
    {
        return Yii::$app->get('customerDb');
    }
}