<?php
/**
 * Created by PhpStorm.
 * User: 蔡旭东 caixudong@verystar.cn
 * Date: 1/28/16 2:09 PM
 */


//引入composer autoload
require __DIR__ . '/../vendor/autoload.php';

date_default_timezone_set('Asia/Shanghai');
define('DS', DIRECTORY_SEPARATOR);
define('APP_PATH', __DIR__ . DS);

$app = new Very\Application(APP_PATH);

view()->display('index.php', ["b" => '123456789']);