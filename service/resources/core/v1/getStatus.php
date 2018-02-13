<?php
/**
 * Created by PhpStorm.
 * User: zgr0629
 * Date: 21/1/2016
 * Time: 5:58 PM
 */

namespace service\resources\core\v1;

use common\models\RegionArea;
use service\components\ContractorPermission;
use service\message\core\OrderStatusRequest;
use service\message\core\OrderStatusResponse;
use service\resources\Exception;
use service\resources\ResourceAbstract;


class getStatus extends ResourceAbstract
{
    public function run($data)
    {
        $returnStatus = [
            'processing' => '待商家接单', 'processing_receive' => '商家已接单',
            'pending_comment' => '待评价', 'complete' => '交易成功',
            'canceled' => '取消订单', 'holded' => '申请取消订单',
            'closed' => '供货商拒单', 'rejected_closed' => '超市拒单'
        ];
        /** @var OrderStatusRequest $request */
        $request = self::request();
        $request->parseFromString($data);
        $contractor_id = $request->getContractorId();
        $auth_token = $request->getAuthToken();

        $contractor = $this->_initContractor($contractor_id, $auth_token);

        if (!$contractor) {
            Exception::contractorInitError();
        }

        if (!ContractorPermission::orderStatusCollectionPermission($contractor->getRolePermission())) {
            Exception::contractorPermissionError();
        }

        $status = [];
        foreach ($returnStatus as $key => $value) {
            $status_tmp['key'] = $key;
            $status_tmp['value'] = $value;
            array_push($status, $status_tmp);
        }

        //区域列表
        $areaArr = RegionArea::find()->select('entity_id AS key,area_name AS value')
            ->where(['city' => intval($contractor->getCity())])->asArray()->all();

//        Tools::log($status,'wangyang.log');
        $response = self::response();
        $response->setFrom(['status' => $status, 'area_list' => $areaArr]);
        // Tools::log($response, 'wangyang.log');
        return $response;
    }

    public static function request()
    {
        return new OrderStatusRequest();
    }

    /**
     * @return OrderStatusResponse
     */
    public static function response()
    {
        return new OrderStatusResponse();
    }
}