<?php
namespace service\resources\sales\v1;

use common\models\SalesFlatOrder;
use common\models\SalesFlatOrderAddress;
use common\models\SalesOrderStatus;
use common\models\salesrule\Rule;
use common\models\salesrule\Usage;
use framework\components\Date;
use framework\components\ToolsAbstract;
use service\components\Proxy;
use service\components\Tools;
use service\message\common\Order;
use service\message\sales\OrderDetailRequest;
use service\resources\Exception;
use service\resources\ResourceAbstract;


/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2016/1/21
 * Time: 15:09
 */
class orderDetail extends ResourceAbstract
{
    const DEFAULT_PAGE_SIZE = 10;

    public function run($data)
    {
        /** @var \service\message\sales\OrderDetailRequest $request */
        $request = self::request();
        $request->parseFromString($data);
        $customer = $this->_initCustomer($request);
        //用户权限校验
        $response = new Order();
        if (!$request->getOrderId()) {
            Exception::orderNotExisted();
        }
        /** @var SalesFlatOrder $order */
        $order = SalesFlatOrder::find()
            ->joinWith('item')
            ->where(['entity_id' => $request->getOrderId()])->asArray()->one();
        //Tools::log($order,'wangyang.txt');
        if (!$order['entity_id']) {
            Exception::orderNotExisted();
        }

        $wholesaler = Proxy::getWholesaler($order['wholesaler_id'], $this->getTraceId(), $customer);
        $address = SalesFlatOrderAddress::find()->where(['order_id' => $request->getOrderId()])->asArray()->one();

        $date = new Date();
        /** @var SalesOrderStatus $orderStatus */
        $orderStatus = SalesOrderStatus::find()->where(['status' => $order['status']])->one();
        $orderData = [
            'order_id' => $order['entity_id'],
            'increment_id' => $order['increment_id'],
            'wholesaler_id' => $order['wholesaler_id'],
            'wholesaler_name' => $order['wholesaler_name'],
            'wholesaler_delivery_time' => $wholesaler->getDeliveryTime(),
            'wholesaler_delivery_text' => $wholesaler->getDeliveryText(),
            'comment' => $order['customer_note'],
            'status' => $order['status'],
            'state' => $order['state'],
            'status_label' => ($orderStatus && $orderStatus->label) ? $orderStatus->label : $order['status'],
            'payment_method' => $order['payment_method'],
            'created_at' => $date->date('Y-m-d H:i:s', $order['created_at']),
            'name' => $address['name'],
            'phone' => $address['phone'],
            'address' => $address['address'],
            'image' => $wholesaler->getLogo(),
            'promotions' => isset($order['promotions']) ? unserialize($order['promotions']) : '',
        ];

        //$orderData = $orderData;

        if ($wholesaler->getPhone() && count($wholesaler->getPhone()) > 0) {
            $orderData['store_phone'] = $wholesaler->getPhone();
        }

        $subtotal = 0;
        $totalQty = 0;
        $orderItems = [];
        foreach ($order['item'] as $_orderItem) {
            $orderItems[] = [
                'item_id' => $_orderItem['item_id'],
                'product_id' => $_orderItem['product_id'],
                'name' => $_orderItem['name'],
                'price' => $_orderItem['price'],
                'qty' => $_orderItem['qty'],
                'image' => $_orderItem['image'],
                'barcode' => $_orderItem['barcode'],
                'specification' => $_orderItem['specification'],
                'row_total' => $_orderItem['row_total'],
                'original_price' => $_orderItem['original_price'],
                'tags' => isset($_orderItem['tags']) ? unserialize($_orderItem['tags']) : array(),
                'receipt' => $_orderItem['receipt'],
                'type' => !empty($_orderItem['product_type']) ? $_orderItem['product_type'] : 0
            ];
            $subtotal += $_orderItem['row_total'];
            $totalQty += $_orderItem['qty'];
        }
        if (count($orderItems) > 0) {
            $orderData['items'] = $orderItems;
        }
        $orderData['totals'] = [
            'base_total' => $subtotal,
            'shipping_amount' => $order['shipping_amount'],
            'discount_amount' => $order['discount_amount'],
            'coupon_discount_amount' => $order['coupon_discount_amount'],
            'grand_total' => $order['grand_total'],
            'total_qty' => $totalQty,
            'balance' => $order['balance'],
            'receipt_total' => $order['receipt_total'],
        ];

        /* 已享优惠 */
        $additionalInfo = unserialize($order['additional_info']);
        if ($additionalInfo && $additionalInfo['applied_rules']) {
            $appliedRules = $additionalInfo['applied_rules'];
            $appliedRules['group_name'] = '已享优惠';

            if (!empty($appliedRules['tags'])) {
                foreach ($appliedRules['tags'] as $arKey => $appliedRule) {
                    /* 检查优惠券退回情况 */
                    if ($this->isNeedCheckCouponReturnStatus($order)) {
                        if (isset($appliedRule['type']) && $appliedRule['type'] != Rule::COUPON_TYPE_NO_COUPON
                            && !empty($appliedRule['id'])
                        ) {
                            if ($order['coupon_return_status'] == SalesFlatOrder::COUPON_RETURN_STATUS_RETURN) {
                                $appliedRules['tags'][$arKey]['status'] = '已退回';
                                $appliedRules['tags'][$arKey]['status_color'] = '#6666FF';
                            } else {
                                $appliedRules['tags'][$arKey]['status'] = '待退回';
                                $appliedRules['tags'][$arKey]['status_color'] = '#FF0000';
                            }
                        }
                    }
                    // 去掉无用的信息
                    unset($appliedRules['tags'][$arKey]['type'], $appliedRules['tags'][$arKey]['id']);
                }
            }

            $orderData['applied_rules'] = $appliedRules;
        }

        /* 返现状态 */
        if (!empty($orderData['promotions']) && $this->isNeedCheckRebateStatus($order)) {
            if ($order['rebate_return_status'] == SalesFlatOrder::REBATE_RETURN_STATUS_RETURN) {
                $orderData['promotions'][0]['rebates_status'] = '已到账';
                $orderData['promotions'][0]['rebates_status_color'] = '#6666FF';
            } else {
                $orderData['promotions'][0]['rebates_status'] = '未到账';
                $orderData['promotions'][0]['rebates_status_color'] = '#FF0000';
            }
        }

        $response->setFrom(Tools::pb_array_filter($orderData));
        return $response;
    }

    /**
     * 是否需要判断优惠券退回状态
     * @param array $order
     * @return bool
     */
    private function isNeedCheckCouponReturnStatus($order)
    {
        $statusArr = [
            'closed',
            'rejected_closed',
            'canceled'
        ];
        return in_array($order['status'], $statusArr, true);
    }

    /**
     * 是否需要判断返现退回状态
     * @param array $order
     * @return bool
     */
    private function isNeedCheckRebateStatus($order)
    {
        return true;
    }

    public static function request()
    {
        return new OrderDetailRequest();
    }

    public static function response()
    {
        return new Order();
    }
}
