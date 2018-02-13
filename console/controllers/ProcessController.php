<?php

namespace console\controllers;

use common\models\SalesFlatOrder;
use common\models\salesrule\Rule;
use common\models\salesrule\UserCoupon;
use Elasticsearch\ClientBuilder;
use service\components\Events;
use service\components\Proxy;
use service\components\Tools;
use yii\console\Controller;


/**
 * Created by PhpStorm.
 * User: henryzhu
 * Date: 16-7-21
 * Time: 上午10:21
 */
class ProcessController extends Controller
{
    protected $properties_mapping = [
        'entity_id' => [
            'type' => 'integer',
        ],
        'increment_id' => [
            'type' => 'string',
        ],
        'wholesaler_id' => [
            'type' => 'integer'
        ],
        'wholesaler_name' => [
            'type' => 'string',
        ],
        'state' => [
            'type' => 'string',
        ],
        'status' => [
            'type' => 'string',
        ],
        'coupon_code' => [
            'type' => 'string',
        ],
        'applied_rule_ids' => [
            'type' => 'string',
        ],
        'payment_method' => [
            'type' => 'string',
        ],
        'province' => [
            'type' => 'integer',
        ],
        'city' => [
            'type' => 'integer',
        ],
        'district' => [
            'type' => 'integer',
        ],
        'area_id' => [
            'type' => 'integer',
        ],
        'remote_ip' => [
            'type' => 'string',
        ],
        'hold_before_status' => [
            'type' => 'string',
        ],
        'hold_before_state' => [
            'type' => 'string',
        ],
        'customer_note' => [
            'type' => 'string',
        ],
        'balance' => [
            'type' => 'float',
        ],
        'rebates' => [
            'type' => 'float',
        ],
        'commission' => [
            'type' => 'float',
        ],
        'promotions' => [
            'type' => 'string',
        ],
        'merchant_remarks' => [
            'type' => 'string',
        ],
        'total_qty_ordered' => [
            'type' => 'integer',
        ],
        'total_due' => [
            'type' => 'float',
        ],
        'total_paid' => [
            'type' => 'float',
        ],
        'discount_amount' => [
            'type' => 'float',
        ],
        'total_item_count' => [
            'type' => 'integer',
        ],
        'coupon_discount_amount' => [
            'type' => 'float',
        ],
        'shipping_amount' => [
            'type' => 'float',
        ],
        'subtotal' => [
            'type' => 'float',
        ],
        'grand_total' => [
            'type' => 'float',
        ],
        'pay_time' => [
            'type' => 'date',
            "format" => "yyy-MM-dd HH:mm:ss||yyyy-MM-dd||epoch_millis"
        ],
        'complete_at' => [
            'type' => 'date',
            "format" => "yyy-MM-dd HH:mm:ss||yyyy-MM-dd||epoch_millis"
        ],
        'expire_time' => [
            'type' => 'date',
            "format" => "yyy-MM-dd HH:mm:ss||yyyy-MM-dd||epoch_millis"
        ],
        'created_at' => [
            'type' => 'date',
            "format" => "yyy-MM-dd HH:mm:ss||yyyy-MM-dd||epoch_millis"
        ],
        'remind_count' => [
            'type' => 'integer',
        ],
        'remind_at' => [
            'type' => 'date',
            "format" => "yyy-MM-dd HH:mm:ss||yyyy-MM-dd||epoch_millis"
        ],
        'updated_at' => [
            'type' => 'date',
            "format" => "yyy-MM-dd HH:mm:ss||yyyy-MM-dd||epoch_millis"
        ],
        'receipt' => [
            'type' => 'integer',
        ],
        'receipt_total' => [
            'type' => 'float',
        ],
        'timestamp' => [
            'type' => 'date',
            "format" => "yyy-MM-dd HH:mm:ss||yyyy-MM-dd||epoch_millis"
        ],
        'address' => [
            'properties' => [
                'entity_id' => [
                    'type' => 'integer'
                ],
                'order_id' => [
                    'type' => 'integer',
                ],
                'name' => [
                    'type' => 'string',
                ],
                'phone' => [
                    'type' => 'string',
                ],
                'address' => [
                    'type' => 'string',
                ]
            ]
        ],
        'items' => [
            'type' => 'nested',
            'properties' => [
                'item_id' => [
                    'type' => 'integer'
                ],
                'order_id' => [
                    'type' => 'integer'
                ],
                'wholesaler_id' => [
                    'type' => 'integer'
                ],
                'created_at' => [
                    'type' => 'date',
                    "format" => "yyy-MM-dd HH:mm:ss||yyyy-MM-dd||epoch_millis"
                ],
                'updated_at' => [
                    'type' => 'date',
                    "format" => "yyy-MM-dd HH:mm:ss||yyyy-MM-dd||epoch_millis"
                ],
                'product_id' => [
                    'type' => 'integer'
                ],
                'sku' => [
                    'type' => 'string'
                ],
                'first_category_id' => [
                    'type' => 'integer'
                ],
                'second_category_id' => [
                    'type' => 'integer'
                ],
                'third_category_id' => [
                    'type' => 'integer'
                ],
                'product_type' => [
                    'type' => 'string'
                ],
                'product_options' => [
                    'type' => 'string'
                ],
                'tags' => [
                    'type' => 'integer'
                ],
                'weight' => [
                    'type' => 'float'
                ],
                'barcode' => [
                    'type' => 'string'
                ],
                'name' => [
                    'type' => 'string'
                ],
                'brand' => [
                    'type' => 'string'
                ],
                'image' => [
                    'type' => 'string'
                ],
                'specification' => [
                    'type' => 'string'
                ],
                'qty' => [
                    'type' => 'integer'
                ],
                'price' => [
                    'type' => 'float'
                ],
                'original_price' => [
                    'type' => 'float'
                ],
                'row_total' => [
                    'type' => 'float'
                ],
                'rebates' => [
                    'type' => 'float'
                ],
                'rebates_lelai' => [
                    'type' => 'float'
                ],
                'is_calculate_lelai_rebates' => [
                    'type' => 'integer'
                ],
                'rebates_calculate' => [
                    'type' => 'float'
                ],
                'rebates_calculate_lelai' => [
                    'type' => 'float'
                ],
                'commission_percent' => [
                    'type' => 'float'
                ],
                'commission' => [
                    'type' => 'float'
                ],
                'receipt' => [
                    'type' => 'integer'
                ],
                'subsidies_wholesaler' => [
                    'type' => 'float'
                ],
                'subsidies_lelai' => [
                    'type' => 'float'
                ],
            ]
        ],
        'history' => [
            'type' => 'nested',
            'properties' => [
                'entity_id' => [
                    'type' => 'integer',
                ],
                'parent_id' => [
                    'type' => 'integer',
                ],
                'operator' => [
                    'type' => 'string',
                ],
                'is_customer_notified' => [
                    'type' => 'integer',
                ],
                'is_visible_on_front' => [
                    'type' => 'integer',
                ],
                'is_visible_to_customer' => [
                    'type' => 'integer',
                ],
                'comment' => [
                    'type' => 'string',
                ],
                'status' => [
                    'type' => 'string',
                ],
                'created_at' => [
                    'type' => 'date',
                    "format" => "yyy-MM-dd HH:mm:ss||yyyy-MM-dd||epoch_millis"
                ]
            ]
        ]
    ];

    /**
     * Author Jason Y. wang
     * 发送即将过期的优惠券提醒
     */
    public function actionCouponExpire()
    {
        $expireDate = date('Y-m-d H:i:s', strtotime(date('Y-m-d H:i:s') . ' +3 days'));
        $now = date('Y-m-d H:i:s');
//        print_r($expireDate);
        $coupons = UserCoupon::find()
            ->where(['state' => UserCoupon::USER_COUPON_UNUSED])
            ->andWhere(['<', 'expiration_date', $expireDate])
//            ->groupBy('customer_id')
            ->andWhere(['>', 'expiration_date', $now])
            ->all();
        if (count($coupons) == 0) {
            print_r('无即将过期优惠券需要推送');
        }
        $rules = [];
        $customers = [];
        //user coupons
        /** @var UserCoupon $coupon */
        foreach ($coupons as $coupon) {
            if (!isset($rules[$coupon->rule_id])) {
                /** @var Rule $rule */
                $rule = Rule::find()->where(['rule_id' => $coupon->rule_id])->one();
                $rules[$coupon->rule_id] = $rule;
            }
            $customers[$coupon->customer_id]['num'] = isset($customers[$coupon->customer_id]['num']) ?
                $customers[$coupon->customer_id]['num']++ : 1;
            $rule = $rules[$coupon->rule_id];
            if ($rule->simple_action == 'by_percent') {
                $percent = array_pop(array_filter(explode(',', $rule->discount_amount)));
                $value = $percent * 100;
            } else if ($rule->simple_action == 'by_fixed') {
                $percent = array_pop(array_filter(explode(',', $rule->discount_amount)));
                $value = $percent * 100;
            } else {
                $value = 10;
            }

            $customers[$coupon->customer_id]['value'] = isset($customers[$coupon->customer_id]['value']) ?
                $customers[$coupon->customer_id]['value'] += $value : $value;
            $coupon->expire_push = 1;
            $coupon->save();
        }

        $name = Events::EVENT_COUPON_EXPIRE;
        $eventName = Events::getCustomerEventName($name);
        foreach ($customers as $customer_id => $customerData) {
            $customerData['customer_id'] = $customer_id;
            $event = [
                'name' => $name,
                'data' => $customerData,
            ];
            Tools::log($customerData,'actionCouponExpire.log');
            Proxy::sendMessage($eventName, $event);
        }
    }

    /**
     * Author Jason Y. wang
     *
     */
    public function actionImportOrder()
    {
        $esClusters = \Yii::$app->params['es_cluster'];
        $hosts = $esClusters['hosts'];
        $client = ClientBuilder::create()
            ->setHosts($hosts)
            ->build();


//        $client->indices()->delete(['index' => 'import_order']);
//        exit();
//        if (!$client->indices()->exists(['index' => ''])) {
//            $params = [
//                'index' => 'import_order',
//                'body' => [
//                    'settings' => [
//                        'number_of_shards' => 5,
//                        'number_of_replicas' => 1,
//                    ],
//                    'mappings' => [
//                        'order' => [
//                            '_source' => [
//                                'enabled' => true
//                            ],
//                            'properties' => $this->properties_mapping
//                        ]
//                    ]
//                ]
//            ];
//
//            $client->indices()->create($params);
//
//        }

//

//        print_r($hosts);
//        exit();

        for ($i = 0; $i < 200; $i++) {

            $orderData = SalesFlatOrder::find()->where(['between', 'sales_flat_order.entity_id', $i * 1000, ($i + 1) * 1000])
                ->joinWith(['address', 'item', 'history']);

            $orderData = $orderData->asArray()->all();
//            print_r(count($orderData));
//            exit();
//            echo PHP_EOL;
            if (count($orderData) > 0) {
//                print_r(count($orderData));
                $params = [];
                foreach ($orderData as $orderArray) {
                    $params['body'][] = [
                        'index' => [
                            '_index' => '.order',
                            '_type' => 'order',
                            '_id' => $orderArray['entity_id']
                        ]
                    ];

                    $params['body'][] = $orderArray;
                }
                $client->bulk($params);
                print_r('.');
            }
        }

    }
}