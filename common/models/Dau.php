<?php

namespace common\models;

use framework\db\ActiveRecord;
use Yii;

/**
 * This is the model class for table "dau".
 *
 * @property integer $entity_id
 * @property integer $type
 * @property integer $customer_id
 * @property string $phone
 * @property string $device_id
 * @property string $created_at
 */
class Dau extends ActiveRecord
{

	/*
	 * 1打开app(访问core.config接口)、2登陆成功、3登陆失败、4注册成功、5注册失败
	 */
	const TYPE_LAUNCH = 1;
	const TYPE_LOGIN_SUCCESS = 2;
	const TYPE_LOGIN_FAIL = 3;
	const TYPE_REGISTER_SUCCESS = 4;
	const TYPE_REGISTER_FAIL = 5;


	/**
	 * @inheritdoc
	 */
	public static function tableName()
	{
		return 'dau';
	}

	/**
	 * @return \yii\db\Connection the database connection used by this AR class.
	 */
	public static function getDb()
	{
		return Yii::$app->get('mainDb');
	}

	/**
	 * @inheritdoc
	 */
	public function rules()
	{
		return [
			[['type', 'customer_id'], 'integer'],
			[['created_at'], 'safe'],
			[['device_id', 'phone'], 'string', 'max' => 255]
		];
	}

	/**
	 * @inheritdoc
	 */
	public function attributeLabels()
	{
		return [
			'entity_id' => 'Entity ID',
			'type' => '1打开app(访问core.config接口)、2登陆成功、3登陆失败、4注册成功、5注册失败',
			'customer_id' => 'Customer ID',
			'phone' => 'Phone',
			'device_id' => 'Device ID',
			'created_at' => 'Created At',
		];
	}
}
