<?php
namespace service\components;

use framework\components\ProxyAbstract;
use service\message\common\CategoryNode;
use service\message\common\Header;
use service\message\common\SourceEnum;
use service\message\common\Store;
use service\message\core\getCategoryRequest;
use service\message\customer\CustomerResponse;
use service\message\merchant\getProductBriefRequest;
use service\message\merchant\getProductBriefResponse;
use service\message\merchant\getStoreDetailRequest;

/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2016/1/25
 * Time: 11:02
 */
class Proxy extends ProxyAbstract
{
    const ROUTE_MERCHANT_GET_STORE_DETAIL = 'merchant.getStoreDetail1';
    const ROUTE_MERCHANT_GET_PRODUCT = 'merchant.getProduct';
    const ROUTE_MERCHANT_GET_CATEGORY = 'merchant.getFirstCategory';
    const ROUTE_MERCHANT_GET_PRODUCT_BRIEF = 'merchant.getProductBrief';

    /**
     * @param $wholesalerId
     * @param $traceId
     *
     * @param CustomerResponse $customer
     * @return Store
     * @throws \Exception
     */
    public static function getWholesaler($wholesalerId, $traceId, CustomerResponse $customer)
    {
        $request = new getStoreDetailRequest();
        $request->setWholesalerId($wholesalerId);
        $request->setCustomerId($customer->getCustomerId());
        $request->setAuthToken($customer->getAuthToken());
        $header = new Header();
        $header->setSource(SourceEnum::CORE);
        $header->setRoute(self::ROUTE_MERCHANT_GET_STORE_DETAIL);
        $header->setTraceId($traceId);
        $message = self::sendRequest($header, $request);
        $response = new Store();
        $response->parseFromString($message->getPackageBody());
        return $response;
    }

    /**
     * @param CustomerResponse $customer
     * @param integer $productId
     *
     * @param string $traceId
     * @return bool|getProductBriefResponse
     * @throws \Exception
     * @internal param $wholesalerId
     */
    public static function getProducts(CustomerResponse $customer, $productId, $traceId)
    {
        if (!$customer || !is_array($productId) || count($productId) == 0) {
            return false;
        }

        $requestData = [
            'city' => $customer->getCity(),
            'product_ids' => $productId,
            'customer_id' => $customer->getCustomerId(),
            'area_id' => $customer->getAreaId()
        ];

        $request = new getProductBriefRequest();
        $request->setFrom($requestData);
        $header = new Header();
        $header->setSource(SourceEnum::CORE);
        $header->setRoute(self::ROUTE_MERCHANT_GET_PRODUCT_BRIEF);
        $header->setTraceId($traceId);
        $header->setCustomerId($customer->getCustomerId());
        $header->setAreaId($customer->getAreaId());
        $message = self::sendRequest($header, $request);
        /** @var getProductBriefResponse $response */
        $response = new getProductBriefResponse();
        $response->parseFromString($message->getPackageBody());
        return $response;
    }

    public static function getFirstCategory($wholesaler_id)
    {
        $request = new getCategoryRequest();
        $request->setWholesalerId($wholesaler_id);
        $header = new Header();
        $header->setSource(SourceEnum::CORE);
        $header->setRoute(self::ROUTE_MERCHANT_GET_CATEGORY);
        $message = self::sendRequest($header, $request);
        $response = new CategoryNode();
        $response->parseFromString($message->getPackageBody());
        return $response;
    }
}