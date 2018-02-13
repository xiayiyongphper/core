<?php

namespace common\models;

use framework\components\Date;
use framework\db\ActiveRecord;
use Yii;

/**
 * This is the model class for table "sales_flat_order_status_history".
 *
 * @property string $entity_id
 * @property string $parent_id
 * @property integer $is_customer_notified
 * @property integer $is_visible_on_front
 * @property string $comment
 * @property string $status
 * @property string $created_at
 */
class SalesFlatOrderStatusHistory extends ActiveRecord
{
    protected $_order;

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'sales_flat_order_status_history';
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
            [['parent_id'], 'required'],
            [['parent_id', 'is_customer_notified', 'is_visible_on_front'], 'integer'],
            [['comment'], 'string'],
            [['created_at'], 'safe'],
            [['status'], 'string', 'max' => 32]
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
            'is_customer_notified' => 'Is Customer Notified',
            'is_visible_on_front' => 'Is Visible On Front',
            'comment' => 'Comment',
            'status' => 'Status',
            'created_at' => 'Created At',
        ];
    }

    /**
     * Function: afterFind
     * Author: Jason Y. Wang
     * magento中存入时间时用的UTC时间，从数据库时拿出时转化为PRC时间
     */
    public function afterFind()
    {
        $date = new Date();
        parent::afterFind(); // TODO: Change the autogenerated stub
        $this->created_at = $this->created_at ? $date->date($this->created_at) : null;
    }

    public function setOrder(SalesFlatOrder $order)
    {
        $this->_order = $order;
    }

    public function beforeSave($insert)
    {
        $date = new Date();
        $this->created_at = $date->gmtDate();
        return parent::beforeSave($insert); // TODO: Change the autogenerated stub
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getOrderstatus()
    {
        return $this->hasOne(SalesOrderStatus::className(), ['status' => 'status']);
    }
}
