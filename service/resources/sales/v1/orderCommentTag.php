<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/5/18
 * Time: 11:04
 */

namespace service\resources\sales\v1;


use service\components\Tools;
use service\resources\ResourceAbstract;
use service\message\sales\OrderCommentTagRequest;
use service\message\sales\OrderCommentTagResponse;
use common\models\orderCommentTags;


class orderCommentTag extends ResourceAbstract
{
    public function run($data)
    {
        $request = self::request();
        $request->parseFromString($data);
        $response = self::response();
        //$customerId = $request->getCustomerId();
        //Tools::log("-----------get customerId:$customerId".PHP_EOL,'debug.log');
        $list = $this->getTags();
        $responseData = [
            'quality' => isset($list['1']) ? $list['1'] : [],
            'delivery' => isset($list['2']) ? $list['2'] : [],
        ];
        unset($list);
        $response->setFrom(Tools::pb_array_filter($responseData));
        return $response;
    }

    public static function request()
    {
        return new OrderCommentTagRequest();
    }

    public static function response()
    {
        return new OrderCommentTagResponse();
    }

    /**
     * @return array
     */
    private function getTags()
    {
        $tags = orderCommentTags::find()->select(['entity_id', 'name', 'score', 'type'])->orderBy('type asc, score asc, entity_id asc')->asArray()->all();
        //Tools::log('-----------'.print_r($tags,true),'debug.log');
        $list = array();
        foreach ($tags as $r) {
            extract($r);
            if (!isset($list[$type])) {
                $list[$type] = [];
            }
            if (!isset($list[$type][$score])) {
                $list[$type][$score] = ['score' => $score, 'tags' => []];
            }
            $list[$type][$score]['tags'][] = ['review_tag_id' => $entity_id, 'name' => $name];
        }
        unset($tags);
        return $list;
    }
}