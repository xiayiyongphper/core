<?php
/**
 * Created by Jason.
 * Author: Jason Y. Wang
 * Date: 2016/2/3
 * Time: 14:56
 */

namespace service\resources\core\v1;


use common\models\HomeActivity;

use service\components\Tools;
use service\message\common\SourceEnum;
use service\message\core\ConfigRequest;
use service\message\core\ConfigResponse;
use service\message\core\getHomeActivityRequest;
use service\message\core\getHomeActivityResponse;
use service\resources\ResourceAbstract;

class getHomeActivity extends ResourceAbstract
{

    public function run($data)
    {
        /** @var getHomeActivityRequest $request */
        $request = self::request();
        $request->parseFromString($data);
        //针对不同用户推荐不同活动时使用
        $customer = $this->_initCustomer($request);
        $response = self::response();

        //透明度bug，返回不同浮层。先以android为准，ios为透明状态，用户量上去时，改为透明度30%
//        $source = $this->getSource();
//        if($source == SourceEnum::ANDROID_SHOP){
//            $homeActivity = HomeActivity::find()->where(['status' => HomeActivity::NORMAL])
//                ->andWhere(['city' => $customer->getCity()])
//                ->andWhere(['like','area','|'.$customer->getAreaId().'|'])
//                ->andWhere(['system' => 'android'])->orderBy('entity_id desc')->one();
//        }else{
//            $homeActivity = HomeActivity::find()->where(['status' => HomeActivity::NORMAL])
//                ->andWhere(['like','area','|'.$customer->getAreaId().'|'])
//                ->andWhere(['city' => $customer->getCity()])
//                ->andWhere(['system' => 'ios'])->orderBy('entity_id desc')->one();
//        }
        $date_now = date('Y-m-d H:i:s',strtotime(date('Y-m-d H:i:s').' +8 hours'));
        $homeActivity = HomeActivity::find()->where(['status' => HomeActivity::NORMAL])
            ->andWhere(['like','area','|'.$customer->getAreaId().'|'])
            ->andWhere(['>=','time_to',$date_now])
            ->andWhere(['<=','time_from',$date_now])
            ->orderBy('entity_id desc');

        //Tools::log($homeActivity->createCommand()->getRawSql(),'wangyang.log');
        /* @var HomeActivity $homeActivity */
        $homeActivity = $homeActivity->one();
        if($homeActivity){
            $response->setActivityId($homeActivity->entity_id);
            //根据版本选用链接
            if($homeActivity->url_backup && $homeActivity->compare_type && $homeActivity->version){
                if(Tools::compareVersion($this->getAppVersion(),$homeActivity->version,$homeActivity->compare_type)){
                    $homeActivity->url = $homeActivity->url_backup;
                }
            }
            $response->setUrl($homeActivity->url);
            $response->setCount($homeActivity->count);
        }
        return $response;
    }

    public static function request()
    {
        return new getHomeActivityRequest();
    }

    public static function response()
    {
        return new getHomeActivityResponse();
    }
}