<?php
/**
 * Created by PhpStorm.
 * User: hemengyuan
 * Date: 15/6/2
 * Time: 上午11:21
 */

namespace publisher\controller;


use publisher\service\Publisher;

class UserController extends BaseController {
  protected $need_auth = false;

  public function get_info() {
    if ($_SESSION['publisher_id']) {
      $result = array(
        'code' => 0,
        'msg' => 'is login',
        'me' => $this->get_user_info(),
      );
      $this->output($result);
    }
    $this->exit_with_error(1, 'not login', 401);
  }

  public function login() {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);
    $verify_code = trim($_POST['verifycode']);

    if ($verify_code != $_SESSION['Checknum']) {
      $this->exit_with_error(1, '验证码错误', 400);
    }

    if ($username == '' || $password == '') {
      $this->exit_with_error(2, '用户名或密码不能为空', 422);
    }

    $publisher_service = new Publisher();
    $pass = $publisher_service->validate($username, $password);
    if (!$pass) {
      $this->exit_with_error(3, '用户名或密码错误', 400);
    }

    $result = array(
      'code' => 0,
      'msg' => '登录成功',
      'me' => $this->get_user_info(),
    );
    $this->output($result);
  }

  public function logout() {
    $_SESSION['publisher_id'] = $_SESSION['publisher_account'] = $_SESSION['publisher_name'] = null;
    $this->output(array(
      'code' => 0,
      'msg' => 'logout',
    ));
  }

  /**
   * 取存在sesssion里的用户数据
   *
   * @return array
   */
  private function get_user_info() {
    return array(
      'publisher_id' => $_SESSION['publisher_id'],
      'publisher_account' => $_SESSION['publisher_account'],
      'publisher_name' => $_SESSION['publisher_name'],
    );
  }
}