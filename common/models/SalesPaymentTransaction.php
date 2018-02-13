<?php

namespace common\models;

use framework\db\ActiveRecord;
use Yii;

/**
 * This is the model class for table "sales_payment_transaction".
 *
 * @property string $transaction_id
 * @property string $order_id
 * @property string $payment_method
 * @property string $txn_id
 * @property string $txn_type
 * @property string $total_fee
 * @property integer $is_closed
 * @property resource $additional_information
 * @property string $created_at
 */
class SalesPaymentTransaction extends ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'sales_payment_transaction';
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
            [['order_id', 'is_closed'], 'integer'],
            [['payment_method', 'total_fee'], 'required'],
            [['total_fee'], 'number'],
            [['additional_information'], 'string'],
            [['created_at'], 'safe'],
            [['payment_method'], 'string', 'max' => 32],
            [['txn_id'], 'string', 'max' => 100],
            [['txn_type'], 'string', 'max' => 15],
            [['order_id', 'txn_id', 'txn_type'], 'unique', 'targetAttribute' => ['order_id', 'txn_id', 'txn_type'], 'message' => 'The combination of Order ID, Txn ID and Txn Type has already been taken.']
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'transaction_id' => 'Transaction ID',
            'order_id' => 'Order ID',
            'payment_method' => 'Payment Method',
            'txn_id' => 'Txn ID',
            'txn_type' => 'Txn Type',
            'total_fee' => 'Total Fee',
            'is_closed' => 'Is Closed',
            'additional_information' => 'Additional Information',
            'created_at' => 'Created At',
        ];
    }
}
