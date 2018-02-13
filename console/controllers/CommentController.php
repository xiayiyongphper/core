<?php
/**
 * 处理Sales Flat Order Comment中的tag字段数据（一次脚本）
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/7/4
 * Time: 18:27
 */

namespace console\controllers;

use Yii;
use yii\console\Controller;
use common\models\SalesFlatOrderComment;
use service\components\Tools;
use yii\helpers\ArrayHelper;

class CommentController extends Controller
{

    const TAG_BIT_MAX = 64;
    const LOOP_SIZE = 100;
    const LOG_FILE = 'commentTag.txt';
    const ERR_LOG_FILE = 'commentTagErr.txt';

    public $max_id = 0;
    public $success = 0;


    public function actionRun()
    {
        Tools::log(PHP_EOL . PHP_EOL . PHP_EOL . str_repeat('--*', 64), self::LOG_FILE);
        //TODO 计算总数
        $query = SalesFlatOrderComment::find()->select(['entity_id', 'tag'])->where(['>', 'tag', 0]);
        $count = (int)$query->count();
        Tools::log('Func:' . __FUNCTION__ . ', L' . __LINE__ . ', $count:' . $count, self::LOG_FILE);
        //TODO 分批处理
        $loop = ceil($count / self::LOOP_SIZE);
        for ($l = 0; $l < $loop; $l++) {
            Tools::log('Func:' . __FUNCTION__ . ', L' . __LINE__ . ', $this->max_id:' . $this->max_id, self::LOG_FILE);
            $list = $this->getData($query, $this->max_id);
            $this->processTag($list);
        }
        Tools::log('Func:' . __FUNCTION__ . ', L' . __LINE__ . ', $count:' . $count, self::LOG_FILE);
        Tools::log('Func:' . __FUNCTION__ . ', L' . __LINE__ . ', success:' . $this->success, self::LOG_FILE);
        Tools::log('Func:' . __FUNCTION__ . ', L' . __LINE__ . ', DONE.', self::LOG_FILE);
        return;
    }

    private function getData($query, $min_id)
    {
        $list = $query->andWhere(['>', 'entity_id', $min_id])->orderBy('entity_id asc')->limit(self::LOOP_SIZE)->all();
        return ArrayHelper::toArray($list);
    }

    /**
     * 处理list中的tag
     * 根据旧逻辑读取对应id，重新生成二进制数据存入
     * @param $list
     */
    private function processTag($list)
    {
        if (empty($list)) {
            return;
        }
        foreach ($list as $k => $row) {
            Tools::log(PHP_EOL . PHP_EOL . PHP_EOL . str_repeat('-', 12), self::LOG_FILE);
            $tag_ids = $this->oldGetTagIds($row['tag']);
            $order_comment = SalesFlatOrderComment::find()->where(['entity_id' => $row['entity_id']])->one();
            $new_tag = $this->newSetTag($tag_ids); //TODO 按照新逻辑生成二进制并转成十进制
            if ($new_tag == 0) {
                Tools::log('$new_tag=0: id-[' . $row['entity_id'] . ']. ', self::ERR_LOG_FILE);
            }
            if (!is_integer($new_tag)) {
                Tools::log('$new_tag error: id-[' . $row['entity_id'] . ']. ', self::ERR_LOG_FILE);
                $this->max_id = $row['entity_id'];
                continue;
            }
            $order_comment->tag = $new_tag;
            if ($order_comment->save()) {
            //if (is_integer($order_comment->tag)) {      //TODO debug..............................................................
                $this->newGetTagIds($order_comment->tag);   //TODO log校验id数组，非必要
                $this->success++;
                Tools::log('Func:' . __FUNCTION__ . ', L' . __LINE__ . ', $row[entity_id]---->OK: ' . $row['entity_id'], self::LOG_FILE);
            } else {
                Tools::log('update DB error: id-[' . $row['entity_id'] . ']: ' . print_r($order_comment->errors, true), self::ERR_LOG_FILE);
            }
            $this->max_id = $row['entity_id'];
            Tools::log(PHP_EOL, self::LOG_FILE);
        }
        return;
    }

    /**
     *
     * @param array $tag_ids
     * @return number  int
     */
    private function newSetTag($tag_ids = [])
    {
        //Tools::log('Func:' . __FUNCTION__ . ', L' . __LINE__ . ', 接收-$tag_ids:' . print_r($tag_ids, true), self::LOG_FILE);
        if (empty($tag_ids)) {
            return 0;
        }
        //TODO 设置默认二进制
        $bit = str_repeat('0', self::TAG_BIT_MAX); //注意长度64需要与数据库定义一致
        foreach ($tag_ids as $id) {
            //TODO 从右边开始位移，计算$id对应下标【$id从1开始, $offset(0-63)】
            $offset = self::TAG_BIT_MAX - $id;
            if ($offset > 0) {
                //TODO 对应下标设为1
                $bit[$offset] = 1;
            }
        }
        Tools::log('Func:' . __FUNCTION__ . ', L' . __LINE__ . ', 二进制$bit:' . $bit, self::LOG_FILE);
        $bit = bindec($bit);    //需要把二进制转为十进制入库
        Tools::log('Func:' . __FUNCTION__ . ', L' . __LINE__ . ', 新逻辑-set十进制$bit:' . $bit, self::LOG_FILE);
        return $bit;
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

        Tools::log('Func:' . __FUNCTION__ . ', L' . __LINE__ . ', 接收-十进制: ' . $original_tag, self::LOG_FILE);
        //TODO 十进制转为二进制
        $bit = decbin($original_tag);    //前置0会变成没有
        Tools::log('Func:' . __FUNCTION__ . ', L' . __LINE__ . ', 十进制-->二进制$bit: ' . $bit, self::LOG_FILE);

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
        Tools::log('Func:' . __FUNCTION__ . ', L' . __LINE__ . ', 新逻辑-还原$tag_ids: ' . print_r($tag_ids, true), self::LOG_FILE);
        return $tag_ids;
    }


    /**
     *
     * @param array $quality_tag integer[]
     * @param array $delivery_tag integer[]
     * @return number|string
     *
     * private function oldSetTag($quality_tag = [], $delivery_tag = [])
     * {
     * //Tools::log('Func:' . __FUNCTION__ . ', L' . __LINE__ . ', $quality_tag:' . print_r($quality_tag, true), self::LOG_FILE);
     * //Tools::log('Func:' . __FUNCTION__ . ', L' . __LINE__ . ', $delivery_tag:' . print_r($delivery_tag, true), self::LOG_FILE);
     * $bit = str_repeat('0', self::TAG_BIT_MAX); //注意长度64需要与数据库定义一致
     * //Tools::log('Func:' . __FUNCTION__ . ', L' . __LINE__ . ', $bit:' . $bit, self::LOG_FILE);
     * if (empty($quality_tag) && empty($delivery_tag)) {
     * } else {
     * $tags = array_merge($quality_tag, $delivery_tag);
     * foreach ($tags as $offset) {
     * $offset = intval($offset);
     * if ($offset > 0 && $offset <= self::TAG_BIT_MAX) {
     * //$offset客户端给值不应小于1
     * $bit[$offset - 1] = 1;
     * }
     * }
     * }
     * //Tools::log('Func:' . __FUNCTION__ . ', L' . __LINE__ . ', $bit:' . $bit, self::LOG_FILE);
     * $bit = bindec($bit);    //需要把二进制转为十进制入库
     * return $bit;
     * }
     */

    /**
     * 【原逻辑】根据tag获取保存的tag ids
     * @param int $original_tag
     * @return array
     */
    private function oldGetTagIds($original_tag = 0)
    {
        if (empty($original_tag)) {
            return [];
        }
        $tag_ids = [];  //选中标签id集合
        Tools::log('Func:' . __FUNCTION__ . ', L' . __LINE__ . ', 接收-$original_tag: ' . $original_tag, self::LOG_FILE);

        //TODO 十进制转为二进制
        $bit = decbin($original_tag);    //前置0会变成没有
        Tools::log('Func:' . __FUNCTION__ . ', L' . __LINE__ . ', 十进制-->二进制$bit: ' . $bit, self::LOG_FILE);

        $err_tag = '111111111111111111111111111111111111111111111111111111111111111';
        if ($bit == $err_tag) {
            Tools::log('Func:' . __FUNCTION__ . ', L' . __LINE__ . ', 异常数据只还原第一个标签.', self::LOG_FILE);
            array_push($tag_ids, 1);    //TODO 异常数据只还原第一个标签
            return $tag_ids;
        }
        $len = strlen($bit);
        if ($len == 0) {
            return $tag_ids;
        }
        $diff = self::TAG_BIT_MAX - $len;
        for ($i = 0; $i < $len; $i++) {
            if ($bit[$i] == '1') {
                $offset = $i + 1 + $diff;
                array_push($tag_ids, $offset);
            }
        }
        Tools::log('Func:' . __FUNCTION__ . ', L' . __LINE__ . ', 从数据库还原$tag_ids: ' . print_r($tag_ids, true), self::LOG_FILE);
        return $tag_ids;
    }

}
