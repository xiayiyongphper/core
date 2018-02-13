<?php
/**
 * Created by Jason.
 * Author: Jason Y. Wang
 * Date: 2016/2/3
 * Time: 15:07
 */

namespace service\resources\core\v1;

use common\models\AvailableCity;
use framework\components\ToolsAbstract;
use service\components\Tools;
use service\message\core\AvailableCityListResponse;
use service\resources\ResourceAbstract;

class getAvailableCityList extends ResourceAbstract
{
    /**
     * Function: run
     * Author: Jason Y. Wang
     * 获取开通城市列表
     * @param string $data
     * @return AvailableCityListResponse
     */
    public function run($data)
    {

        $city = AvailableCity::find()->select(['city_name','city_code','province_name','province_code'])
			->where(['frontend_show'=>1])
			->asArray()->all();

        $response = self::response();
        $response->setFrom(Tools::pb_array_filter(['city' => $city]));
        return $response;
    }

    public static function request()
    {
        return true;
    }

    public static function response()
    {
        return new AvailableCityListResponse();
    }
}