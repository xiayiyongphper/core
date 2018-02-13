<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/5/19
 * Time: 15:26
 */

namespace service\resources\sales\v1;

use service\components\Tools;
use service\resources\ResourceAbstract;
use service\message\sales\OrderCommentReviewRequest;
use service\message\sales\OrderCommentReviewResponse;
use common\models\SalesFlatOrderComment;
use common\models\orderCommentTags;

class orderCommentReview extends ResourceAbstract
{
    const TAG_BIT_MAX = 64;

    public function run($data)
    {
        $request = self::request();
        $request->parseFromString($data);
        $response = self::response();
        $wholesaler_id = $request->getWholesalerId();
        $order_id = $request->getOrderId();
        $responseData = $this->getCommentReview($wholesaler_id, $order_id);
        $response->setFrom(Tools::pb_array_filter($responseData));
        return $response;
    }

    public static function request()
    {
        return new OrderCommentReviewRequest();
    }

    public static function response()
    {
        return new OrderCommentReviewResponse();
    }

    /**
     * @param $wholesaler_id
     * @param $order_id
     * @return array|null|\yii\db\ActiveRecord
     */
    private function getCommentReview($wholesaler_id, $order_id)
    {
        $data = SalesFlatOrderComment::find()->select(['entity_id', 'quality', 'delivery', 'comment', 'tag'])->where(['wholesaler_id' => $wholesaler_id, 'order_id' => $order_id])->orderBy('entity_id desc')->asArray()->one();
        if (isset($data['entity_id'])) {
            $tags = $this->processTags($data['tag']);
            unset($data['entity_id'], $data['tag']);
            $data['quality_tags'] = $tags['1'];
            $data['delivery_tags'] = $tags['2'];
        }
        return $data;
    }

    /**
     * 根据DB存的int转二进制，再返回tag id数组
     * @param int $original_tag
     * @return array
     */
    private function newGetTagIds($original_tag = 0)
    {
        if (empty($original_tag)) {
            return [];
        }
        $tag_ids = [];  //选中标签id集合

        Tools::log('Func:' . __FUNCTION__ . ', L' . __LINE__ . ', 接收-十进制: ' . $original_tag, 'debug.txt');
        //TODO 十进制转为二进制
        $bit = decbin($original_tag);    //前置0会变成没有
        Tools::log('Func:' . __FUNCTION__ . ', L' . __LINE__ . ', 十进制-->二进制$bit: ' . $bit, 'debug.txt');

        $len = strlen($bit);
        if ($len == 0) {
            return $tag_ids;
        }
        //TODO 从右边开始判断（右边第一位为（id=1，即$offset=63），$offset[0-63]）
        for ($i = ($len - 1); $i >= 0; $i--) {
            if ($bit[$i] == '1') {
                $offset = $len - $i;
                array_push($tag_ids, $offset);
            }
        }
        Tools::log('Func:' . __FUNCTION__ . ', L' . __LINE__ . ', 新逻辑-还原$tag_ids: ' . print_r($tag_ids, true), 'debug.txt');
        return $tag_ids;
    }


    /**
     * @param $original_tag integer
     * @return array
     */
    private function processTags($original_tag)
    {
        $tag_list = ['1' => [], '2' => []];     //标签所属类型：1-商品质量，2-配送速度
        $tag_ids = $this->newGetTagIds($original_tag);
        if (!empty($tag_ids)) {
            $tags = orderCommentTags::find()->select(['name', 'type'])->where(['in', 'entity_id', $tag_ids])->asArray()->all();
            if (!empty($tags)) {
                foreach ($tags as $tag) {
                    extract($tag);
                    array_push($tag_list[$type], $name);
                }
            }
            unset($tags, $tag, $type, $name);
        }
        return $tag_list;
    }

}