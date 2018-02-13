<?php
/**
 * Created by Jason.
 * Author: Jason Y. Wang
 * Date: 2016/2/3
 * Time: 14:56
 */

namespace service\resources\core\v1;


use common\models\Dau;
use framework\components\ToolsAbstract;
use service\components\Tools;
use service\message\core\ConfigRequest;
use service\message\core\ConfigResponse;
use service\resources\ResourceAbstract;

class config extends ResourceAbstract
{
    /**
     * Function: run
     * Author: Jason Y. Wang
     * 获取配置信息,IOS，ANDROID,IOS企业通过channel或system进行区分
     * @param string $data
     * @return ConfigResponse
     */
    public function run($data)
    {
        /** @var ConfigRequest $request */
        $request = self::request();
        $request->parseFromString($data);
        $response = self::response();
        $response->setCsh('4008949580');
        $response->setAdShowTime(5);
        $response->setHelperUrl('http://assets.lelai.com/assets/h5/help/index.html');
        $response->setWalletHelperUrl('http://assets.lelai.com/assets/h5/help/detail.html?cid=wallet');
        $response->setCouponHelperUrl('http://assets.lelai.com/assets/h5/help/detail.html?cid=coupon');
        $response->setVer(1);
        $response->setJsCart('http://assets.lelai.com/assets/cart/cart.html?version=26');
        $response->setCustomerServiceTime('服务时间：9:00-21:00');
        $response->setCouponNotifyTime(2 * 24 * 3600);
        $response->setMoreQuantityTip('超出限购数量部分按原价结算');
        $response->setSeckillNotifyTime(300);


        if ($this->isDebug()) {
            $response->setDebug(true);
            $options = [['key' => 'edit_url', 'value' => '1'],];
            if (defined('ENV_LOG_SERVER_IP') && defined('ENV_LOG_SERVER_PORT')) {
                switch ($this->getLevel()) {
                    case 1:
                        break;
                    case 2:
                        $options[] = ['key' => 'api', 'value' => '1'];
                        $options[] = ['key' => 'ip', 'value' => ENV_LOG_SERVER_IP];
                        $options[] = ['key' => 'port', 'value' => ENV_LOG_SERVER_PORT];
                        break;
                    case 3:
                    case 4:
                    case 5:
                    case 6:
                    case 7:
                    case 8:
                    case 9:
                    default:
                        $options[] = ['key' => 'api', 'value' => '1'];
                        $options[] = ['key' => 'console_exception', 'value' => '1'];
                        $options[] = ['key' => 'ip', 'value' => ENV_LOG_SERVER_IP];
                        $options[] = ['key' => 'port', 'value' => ENV_LOG_SERVER_PORT];
                }
            }
            if (count($options) > 0) {
                $response->setFrom(['debug_options' => $options]);
            }
        }

        /* DAU 打开app */
		if (!empty($this->getDeviceId())) {
			$dau = new Dau();
			$dau->type = Dau::TYPE_LAUNCH;
			$dau->device_id = $this->getDeviceId();
			if($this->getCustomerId()){
				$dau->customer_id = $this->getCustomerId();
			}
			//$dau->phone = $customer->phone;
			$dau->created_at = ToolsAbstract::getDate()->date();
			$dau->save();
		}

        return $response;
    }

    public static function request()
    {
        return new ConfigRequest();
    }

    public static function response()
    {
        return new ConfigResponse();
    }
}
