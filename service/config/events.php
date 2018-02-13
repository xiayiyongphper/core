<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2016/1/29
 * Time: 15:45
 */
return [
    'events' => [
        'sales_model_quote_submit_before' => [
            'inventory' => [
                'class' => 'service\models\sales\Observer',
                'method' => 'subtractQuoteInventory',
            ]
        ],
        'sales_model_quote_submit_failure' => [
            'inventory' => [
                'class' => 'service\models\sales\Observer',
                'method' => 'revertQuoteInventory',
            ]
        ],
        'sales_order_place_after' => [
            'shopping_cart' => [
                'class' => 'service\models\sales\Observer',
                'method' => 'removeOrderItems',
            ],
            'daily_purchase_history' => [
                'class' => 'service\models\sales\Observer',
                'method' => 'dailyPurchaseHistory',
            ],
            'customer_rules_limit' => [
                'class' => 'service\models\sales\Observer',
                'method' => 'addCustomerRulesLimit',
            ],
        ],
        'balance_change' => [
            'add_order_status_history' => [
                'class' => 'service\models\sales\Observer',
                'method' => 'balanceChange',
            ],
        ],
        'logistics_status_change' => [
            'order_logistics_status_history' => [
                'class' => 'service\models\sales\Observer',
                'method' => 'logisticsStatusChange',
            ],
        ],
        'send_coupon' => [
            //运营后台导入用户id发放优惠券
            'send_coupon' => [
                'class' => 'service\models\sales\Observer',
                'method' => 'send_coupon',
            ],
        ],
//        'order_agree_cancel' => [
//            'return_coupon' => [
//                'class' => 'service\models\sales\Observer',
//                'method' => 'returnCoupon',
//            ],
//        ],
//        'order_decline' => [
//            'return_coupon' => [
//                'class' => 'service\models\sales\Observer',
//                'method' => 'returnCoupon',
//            ],
//        ],
        'es_order_report' => [
            'es_order_report' => [
                'class' => 'service\models\sales\Observer',
                'method' => 'esOrderReport',
            ],
        ],
		'slim_to_swoole_message'=>[
			'slim_to_swoole_message' => [
				'class' => 'service\models\sales\Observer',
				'method' => 'slim_to_swoole_message',
			],
		],
    ],
];