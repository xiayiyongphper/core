<?php
namespace service\resources\sales\v1;

use common\models\SalesFlatOrder;
use common\models\SalesFlatOrderAddress;
use common\models\SalesOrderStatus;
use framework\components\Date;
use framework\components\ToolsAbstract;
use service\components\Proxy;
use service\message\common\Header;
use service\message\common\Order;
use service\message\common\SourceEnum;
use service\message\common\Store;
use service\message\merchant\getStoreDetailRequest;
use service\message\sales\DriverOrderDetailRequest;
use service\message\sales\OrderDetailBriefRequest;
use service\resources\Exception;
use service\resources\ResourceAbstract;


/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2016/1/21
 * Time: 15:09
 */
class contractorOrderDetail extends ResourceAbstract
{
    const DETAIL_LEVEL_FEW = 1;
    const DETAIL_LEVEL_LITTLE = 2;
    const DETAIL_LEVEL_LESS = 3;
    const DETAIL_LEVEL_MORE = 4;
    const DETAIL_LEVEL_FULL = 5;

    public function run($data)
    {
        if ($this->isRemote()) {
            Exception::resourceNotFound();
        }
        /** @var \service\message\sales\DriverOrderDetailRequest $request */
        $request = self::request();
        $request->parseFromString($data);
        if (!$request->getIncrementId() && !$request->getOrderId()) {
            Exception::orderNotExisted();
        }
        //用户权限校验
        $response = self::response();
        if (!$request->getOrderId()) {
            Exception::orderNotExisted();
        }
        /** @var SalesFlatOrder $order */
        $order = SalesFlatOrder::find()
            ->joinWith('item')
            ->where(['entity_id' => $request->getOrderId()])->asArray()->one();

        if (!$order['entity_id']) {
            Exception::orderNotExisted();
        }
        $wholesaler = $this->getWholesaler($order['wholesaler_id'], $this->getTraceId());
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
            'rebates' => $order['rebates'],
            'receipt' => $order['receipt'],
            'contractor_id' => $order['contractor_id'],
            'contractor' => $order['contractor'],
            'store_name' => $order['store_name'],
            'customer_id' => $order['customer_id'],
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
        $additionalInfo = unserialize($order['additional_info']);
        if ($additionalInfo && $additionalInfo['applied_rules']) {
            $appliedRules = $additionalInfo['applied_rules'];
            $appliedRules['group_name'] = '已享优惠';

            if (!empty($appliedRules['tags'])) {
                foreach ($appliedRules['tags'] as $arKey => $appliedRule) {
                    // 去掉无用的信息
                    unset($appliedRules['tags'][$arKey]['type'], $appliedRules['tags'][$arKey]['id']);
                }
            }

            $orderData['applied_rules'] = $appliedRules;
        }
        $response->setFrom(ToolsAbstract::pb_array_filter($orderData));
        return $response;
    }

    public static function request()
    {
        return new DriverOrderDetailRequest();
    }

    public static function response()
    {
        return new Order();
    }

    public function getWholesaler($wholesalerId, $traceId)
    {
        $request = new getStoreDetailRequest();
        $request->setWholesalerId($wholesalerId);
        $header = new Header();
        $header->setSource(SourceEnum::CORE);
        $header->setRoute('merchant.getStoreDetail');
        $header->setTraceId($traceId);
        $message = Proxy::sendRequest($header, $request);
        $response = new Store();
        $response->parseFromString($message->getPackageBody());
        return $response;
    }
}