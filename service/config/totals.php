<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2016/1/26
 * Time: 17:42
 */
return [
    'totals' => [
        'discount' => [
            'class' => 'service\models\sales\quote\Discount',
            'after' => 'subtotal,shipping',
            'before' => 'grand_total',
        ]
    ]
];