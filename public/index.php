<?php
/**
 * Created by PhpStorm.
 * User: meathill
 * Date: 14/11/12
 * Time: 下午5:20
 */

// Autoload
require '../vendor/autoload.php';
require '../config/config.php';

// 设置错误日志位置
ini_set('error_log', '/tmp/publisher_' . date('md') . '.log');

use diy\controller\BaseController;
use NoahBuscher\Macaw\Macaw;

session_start();
header('Access-Control-Allow-Origin: ' . BaseController::get_allow_origin());

// routes
require '../router/routes.php';
Macaw::dispatch();