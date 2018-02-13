<?php
/**
 * Created by PhpStorm.
 * User: zgr0629
 * Date: 21/1/2016
 * Time: 5:58 PM
 */
namespace service\resources\core\v1;

use service\components\Proxy;
use service\message\common\CategoryNode;
use service\message\core\getCategoryRequest;
use service\resources\ResourceAbstract;


class getCategory extends ResourceAbstract
{
	public function run($data)
	{
		/** @var getCategoryRequest $request */
		$request = self::request();
		$request->parseFromString($data);

		// 现在暂时不用到,直接返回一级分类
		$wholesalerId = $request->getWholesalerId();

		$response = Proxy::getFirstCategory($wholesalerId);

		return $response;
	}

	public static function request()
	{
		return new getCategoryRequest();
	}

	/**
	 * @return CategoryNode
	 */
	public static function response()
	{
		return new CategoryNode();
	}
}