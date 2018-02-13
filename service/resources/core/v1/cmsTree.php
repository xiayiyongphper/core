<?php
/**
 * Created by Jason.
 * Author: Jason Y. Wang
 * Date: 2016/2/3
 * Time: 14:56
 */

namespace service\resources\core\v1;


use common\models\CmsPage;
use framework\components\ToolsAbstract;
use service\components\Tools;
use service\message\core\CmsRequest;
use service\message\core\CmsResponse;
use service\resources\ResourceAbstract;

class cmsTree extends ResourceAbstract
{
    /**
     * Function: run
     * Author: Jason Y. Wang
     * 获取页脚配置
     * @param string $data
     * @return CmsResponse
     */
    public function run($data)
    {
        /** @var CmsRequest $request */
        $request = self::request();
        $request->parseFromString($data);
//        Tools::log($request,'wangyang.txt');
        /** @var CmsPage $page */
        $page = CmsPage::find()
            ->addSelect(CmsPage::getGeneralSelectColumns())
            ->where(['identifier' => $request->getIdentifier()])
            ->andWhere(['is_active' => 1])
            ->orderBy('sort_order desc')->one();
//        Tools::log($page,'wangyang.txt');
        $responseData = [
            'page_id' => $page->page_id,
            'identifier' => $page->identifier,
            'title' => $page->title,
        ];
        $response = self::response();
        $response->setFrom(ToolsAbstract::pb_array_filter($responseData));
        return $response;
    }

    public static function request()
    {
        return new CmsRequest();
    }

    public static function response()
    {
        return new CmsResponse();
    }
}