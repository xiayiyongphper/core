<?php
namespace service\resources;
class Exception
{
    const DEFAULT_ERROR_CODE = 30001;//default error code, none exception
    const OFFLINE = 39999;
    const RESOURCE_NOT_FOUND_TEXT = '找不到相关资源';
    const RESOURCE_NOT_FOUND = 31001;
    const INVALID_REQUEST_ROUTE_TEXT = '非法的请求';
    const INVALID_REQUEST_ROUTE = 31002;
    /**
     * customer exception code start with 2
     */
    /**
     * customer not found
     */
    const CUSTOMER_NOT_FOUND = 32001;
    const CUSTOMER_NOT_FOUND_TEXT = '用户不存在';
    /**
     * customer auth token expired
     */
    const CUSTOMER_SHOPPING_CART_EMPTY = 32003;
    const CUSTOMER_SHOPPING_CART_EMPTY_TEXT = '购物车为空，请先添加商品！';
    const CUSTOMER_BALANCE_INSUFFICIENT = 32007;
    const CUSTOMER_BALANCE_INSUFFICIENT_TEXT = '钱包余额不足';

    /**
     * store exception code start with 3
     */

    /**
     * store not found
     */
    const STORE_NOT_FOUND = 33001;
    const STORE_NOT_FOUND_TEXT = '店铺不存在';

    const MULTI_STORE_NOT_ALLOWED = 33003;
    const MULTI_STORE_NOT_ALLOWED_TEXT = '暂不支持多个店铺订单';
    const STORE_OFFLINE = 33005;
    const STORE_OFFLINE_TEXT = '商家休息中，暂不接单';
    /**
     * CATALOG EXCEPTION
     */
    const CATALOG_PRODUCT_SOLD_OUT = 34003;
    const NEW_CATALOG_PRODUCT_SOLD_OUT_TEXT1 = '下手太慢啦，%s已抢光';
    const NEW_CATALOG_PRODUCT_SOLD_OUT_TEXT2 = '下手太慢啦，%s库存不足';
    const CATALOG_PRODUCT_OUT_OF_RESTRICT_DAILY = 34004;
    const CATALOG_PRODUCT_OUT_OF_RESTRICT_DAILY_TEXT = '%s每日限购%s件，今天还可购买%s件';

    /**
     * SALES EXCEPTION
     */
    const SALES_ORDER_NOT_EXISTED = 35001;
    const SALES_ORDER_NOT_EXISTED_TEXT = '该订单不存在';
    const SALES_PAYMENT_METHOD_NOT_SUPPORTED = 35002;
    const SALES_PAYMENT_METHOD_NOT_SUPPORTED_TEXT = '暂不支持该支付方式';
    const SALES_ORDER_CANNOT_CANCELED = 35007;
    const SALES_ORDER_CANNOT_CANCELED_TEXT = '订单不能取消';
    const SALES_ORDER_CANNOT_RECEIPT_CONFIRM = 35013;
    const SALES_ORDER_CANNOT_RECEIPT_CONFIRM_TEXT = '订单不能操作确认收货';
    const SALES_ORDER_CANNOT_REVIEW = 35014;
    const SALES_ORDER_CANNOT_REVIEW_TEXT = '订单不能操作评价';
    const SALES_ORDER_NOT_SATISFY_MIN_TRADE_AMOUNT = 35019;
    const SALES_ORDER_NOT_SATISFY_MIN_TRADE_AMOUNT_TEXT = '不满足最低起送金额：%s';
    const SALES_ORDER_CANNOT_DECLINE = 35020;
    const SALES_ORDER_CANNOT_DECLINE_TEXT = '订单不能操作拒单';
    const SALES_ORDER_CANNOT_UNHOLD = 35021;
    const SALES_ORDER_CANNOT_UNHOLD_TEXT = '订单不能撤销取消';
	const SALES_ORDER_CANNOT_HOLD = 35022;
	const SALES_ORDER_CANNOT_HOLD_TEXT = '订单不能取消';
	const SALES_ORDER_BALANCE_OVER_GRAND_TOTAL = 35023;
	const SALES_ORDER_BALANCE_OVER_GRAND_TOTAL_TEXT = '钱包额度使用超限';
	const SALES_ORDER_BALANCE_OVER_DAILY_LIMIT = 35024;
	const SALES_ORDER_BALANCE_OVER_DAILY_LIMIT_TEXT = '钱包使用超每日限制';


    const SYSTEM_NOT_SUPPORT = 39001;
    const SYSTEM_NOT_SUPPORT_TEXT = '当前系统不支持';

    const MESSAGE_SUCCESS = '操作成功';
    const MESSAGE_COUPON_CODE_INVALID = '该优惠券不可使用或已过期';
    const MESSAGE_BALANCE_IS_ZERO_CANNOT_USE = '钱包余额为0，不能使用余额';


    /*
     * EVENT EXCEPTION
     */
    const EVENT_NOT_FOUND = 50000;
    const EVENT_NOT_FOUND_TEXT = '场次不存在';

    public static function paymentMethodNotSupported()
    {
        throw new \Exception(self::SALES_PAYMENT_METHOD_NOT_SUPPORTED_TEXT, self::SALES_PAYMENT_METHOD_NOT_SUPPORTED);
    }

    public static function orderNotExisted()
    {
        throw new \Exception(self::SALES_ORDER_NOT_EXISTED_TEXT, self::SALES_ORDER_NOT_EXISTED);
    }

    public static function customerNotExisted()
    {
        throw new \Exception(self::CUSTOMER_NOT_FOUND_TEXT, self::CUSTOMER_NOT_FOUND);
    }

    public static function storeNotExisted()
    {
        throw new \Exception(self::STORE_NOT_FOUND_TEXT, self::STORE_NOT_FOUND);
    }

    public static function offline($text)
    {
        throw new \Exception($text, self::OFFLINE);
    }

    public static function resourceNotFound()
    {
        throw new \Exception(self::RESOURCE_NOT_FOUND_TEXT, self::RESOURCE_NOT_FOUND);
    }

    public static function invalidRequestRoute()
    {
        throw new \Exception(self::INVALID_REQUEST_ROUTE_TEXT, self::INVALID_REQUEST_ROUTE);
    }

    public static function multiStoreNotAllowed()
    {
        throw new \Exception(self::MULTI_STORE_NOT_ALLOWED_TEXT, self::MULTI_STORE_NOT_ALLOWED);
    }

    public static function emptyShoppingCart()
    {
        throw new \Exception(self::CUSTOMER_SHOPPING_CART_EMPTY_TEXT, self::CUSTOMER_SHOPPING_CART_EMPTY);
    }

    public static function balanceInsufficient()
    {
        throw new \Exception(self::CUSTOMER_BALANCE_INSUFFICIENT_TEXT, self::CUSTOMER_BALANCE_INSUFFICIENT);
    }

	public static function balanceOverGrandTotal()
	{
		throw new \Exception(self::SALES_ORDER_BALANCE_OVER_GRAND_TOTAL_TEXT, self::SALES_ORDER_BALANCE_OVER_GRAND_TOTAL);
	}

	public static function balanceOverDailyLimit()
	{
		throw new \Exception(self::SALES_ORDER_BALANCE_OVER_DAILY_LIMIT_TEXT, self::SALES_ORDER_BALANCE_OVER_DAILY_LIMIT);
	}

    public static function salesOrderCanNotCanceled()
    {
        throw new \Exception(self::SALES_ORDER_CANNOT_CANCELED_TEXT, self::SALES_ORDER_CANNOT_CANCELED);
    }

    public static function salesOrderCanNotUnHold()
    {
        throw new \Exception(self::SALES_ORDER_CANNOT_UNHOLD_TEXT, self::SALES_ORDER_CANNOT_UNHOLD);
    }

    public static function salesOrderCanNotReceiptConfirm()
    {
        throw new \Exception(self::SALES_ORDER_CANNOT_RECEIPT_CONFIRM_TEXT, self::SALES_ORDER_CANNOT_RECEIPT_CONFIRM);
    }

    public static function salesOrderCanNotReview()
    {
        throw new \Exception(self::SALES_ORDER_CANNOT_REVIEW_TEXT, self::SALES_ORDER_CANNOT_REVIEW);
    }

    public static function salesOrderCanNotDecline()
    {
        throw new \Exception(self::SALES_ORDER_CANNOT_DECLINE_TEXT, self::SALES_ORDER_CANNOT_DECLINE);
    }

    public static function notSatisfyMinTradeAmount($amount)
    {
        throw new \Exception(sprintf(self::SALES_ORDER_NOT_SATISFY_MIN_TRADE_AMOUNT_TEXT, $amount), self::SALES_ORDER_NOT_SATISFY_MIN_TRADE_AMOUNT);
    }

    public static function catalogProductOutOfRestrictDaily($productName, $restrictDaily, $purchasedQty)
    {
        throw new \Exception(sprintf(self::CATALOG_PRODUCT_OUT_OF_RESTRICT_DAILY_TEXT, $productName, $restrictDaily, $purchasedQty), self::CATALOG_PRODUCT_OUT_OF_RESTRICT_DAILY);
    }


    const COUPON_RECEIVE_COUNT_OUT = 38001;
    const COUPON_RECEIVE_COUNT_OUT_TEXT = '优惠券已领光';

    public static function couponReceiveOut()
    {
        throw new \Exception(self::COUPON_RECEIVE_COUNT_OUT_TEXT, self::COUPON_RECEIVE_COUNT_OUT);
    }

    const COUPON_USER_RECEIVE_COUNT_OUT = 38002;
    const COUPON_USER_RECEIVE_COUNT_OUT_TEXT = '您的领取次数已超过该优惠券限制';

    public static function couponUserReceiveOut()
    {
        throw new \Exception(self::COUPON_USER_RECEIVE_COUNT_OUT_TEXT, self::COUPON_USER_RECEIVE_COUNT_OUT);
    }

    const COUPON_USER_RECEIVED = 38003;
    const COUPON_USER_RECEIVED_TEXT = '您已经领过此优惠券';

    public static function couponUserReceived()
    {
        throw new \Exception(self::COUPON_USER_RECEIVED_TEXT, self::COUPON_USER_RECEIVED);
    }

    const COUPON_RECEIVE_ERROR = 38004;
    const COUPON_RECEIVE_ERROR_TEXT = '优惠券领取失败';

    public static function couponReceivedError()
    {
        throw new \Exception(self::COUPON_RECEIVE_ERROR_TEXT, self::COUPON_RECEIVE_ERROR);
    }

    const COUPON_EXPIRE = 38005;
    const COUPON_EXPIRE_TEXT = '优惠活动已结束，无法领取此优惠券';

    public static function couponExpire()
    {
        throw new \Exception(self::COUPON_EXPIRE_TEXT, self::COUPON_EXPIRE);
    }

    const COUPON_NUMBER_ERROR = 38006;
    const COUPON_NUMBER_ERROR_TEXT = '优惠码错误，请检查后重新输入';

    public static function couponNumberError()
    {
        throw new \Exception(self::COUPON_NUMBER_ERROR_TEXT, self::COUPON_NUMBER_ERROR);
    }

    const CONTRACTOR_INIT_ERROR = 39001;
    const CONTRACTOR_INIT_ERROR_TEXT = '业务员不存在';

    public static function contractorInitError()
    {
        throw new \Exception(self::CONTRACTOR_INIT_ERROR_TEXT, self::CONTRACTOR_INIT_ERROR);
    }

    const CONTRACTOR_PERMISSION_ERROR = 39004;
    const CONTRACTOR_PERMISSION_ERROR_TEXT = '无权访问该模块';

    public static function contractorPermissionError()
    {
        throw new \Exception(self::CONTRACTOR_PERMISSION_ERROR_TEXT, self::CONTRACTOR_PERMISSION_ERROR);
    }

    public static function storeOffline()
    {
        throw new \Exception(self::STORE_OFFLINE_TEXT, self::STORE_OFFLINE);
    }

    const SYSTEM_DECLINE_ORDER_ERROR = 39005;
    const SYSTEM_DECLINE_ORDER_ERROR_TEXT = '操作失败，请联系客服';

    public static function systemDeclineOrderError()
    {
        throw new \Exception(self::SYSTEM_DECLINE_ORDER_ERROR_TEXT, self::SYSTEM_DECLINE_ORDER_ERROR);
    }

    const CONTRACTOR_CITY_EMPTY = 39006;
    const CONTRACTOR_CITY_EMPTY_TEXT = '请先选择城市';

    public static function contractorCityEmpty()
    {
        throw new \Exception(self::CONTRACTOR_CITY_EMPTY_TEXT, self::CONTRACTOR_CITY_EMPTY);
    }


    const CONTRACTOR_ROLE_ERROR = 39010;
    const CONTRACTOR_ROLE_ERROR_TEXT = '业务员角色错误';

    public static function contractorRoleError()
    {
        throw new \Exception(self::CONTRACTOR_ROLE_ERROR_TEXT, self::CONTRACTOR_ROLE_ERROR);
    }

}
