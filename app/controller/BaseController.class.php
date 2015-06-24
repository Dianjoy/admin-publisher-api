<?php
/**
 * Created by PhpStorm.
 * User: hemengyuan
 * Date: 15/6/3
 * Time: 下午6:11
 */

namespace publisher\controller;


class BaseController extends \diy\controller\BaseController {
  protected $need_auth = true;

  public function __construct() {
    // 在这里校验用户身份
    if ($this->need_auth && $_SERVER['REQUEST_METHOD'] != 'OPTIONS') {
      if (!isset($_SESSION['publisher_id']) || !isset($_SESSION['publisher_name'])) {
        $this->exit_with_error(1, '登录失效', 401);
      }
    }
  }
}