<?php
/**
 * Created by PhpStorm.
 * User: henryzhu
 * Date: 16-10-13
 * Time: 下午3:16
 * Email: henryzxj1989@gmail.com
 */
error_reporting(E_ALL);
ini_set('display_errors', 1);
defined('YII_DEBUG') or define('YII_DEBUG', false);
defined('YII_ENV') or define('YII_ENV', 'prod');

require(__DIR__ . '/common/config/env.php');
require(__DIR__ . '/framework/autoload.php');
require(__DIR__ . '/common/config/bootstrap.php');
require(__DIR__ . '/service/config/bootstrap.php');

$config = yii\helpers\ArrayHelper::merge(
    require(__DIR__ . '/common/config/main.php'),
    require(__DIR__ . '/common/config/main-local.php'),
    require(__DIR__ . '/service/config/main.php'),
    require(__DIR__ . '/service/config/main-local.php')
);
$bench = new \framework\bench\Benchmark();
$bench->run();