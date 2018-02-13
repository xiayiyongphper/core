<?php

namespace common\models;

use framework\db\ActiveRecord;


/**
 * This is the model class for table "sales_flat_order_address".
 *
 * @property string $entity_id
 * @property integer $wholesaler_id
 * @property string $order_id
 * @property float $quality
 * @property float $delivery
 * @property float $total
 * @property string $comment
 * @property string $created_at
 * @property integer $tag
 */
class SalesFlatOrderComment extends ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'sales_flat_order_comment';
    }

    /**
     * @return \yii\db\Connection the database connection used by this AR class.
     */
    public static function getDb()
    {
        return \Yii::$app->get('mainDb');
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['order_id' ], 'required'],
        ];
    }

}
