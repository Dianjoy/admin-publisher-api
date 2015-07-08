<?php
/**
 * Created by PhpStorm.
 * User: hemengyuan
 * Date: 15/6/1
 * Time: 下午2:29
 */

namespace publisher\controller;


use diy\utils\Utils;
use Exception;
use publisher\model\PublisherModel;
use publisher\service\Publisher;

class PublisherController extends BaseController {
  public function stat() {
    $start = $_REQUEST['start'];
    $end = $_REQUEST['end'];

    $publisher_service = new Publisher();
    $list = $publisher_service->stat($start, $end);

    $this->output(array(
      'code' => 0,
      'msg' => 'ok',
      'list' => $list,
    ));
  }

  public function get_list() {
    $publisher_service = new Publisher();
    $list = $publisher_service->get_list();

    $this->output(array(
      'code' => 0,
      'msg' => 'ok',
      'list' => $list,
      'options' => array(
        'out_cycle' => PublisherModel::$OUT_CYCLE,
      ),
    ));
  }

  public function get_info() {
    require_once dirname(__FILE__) . '/../static/province.php';
    $publisher_service = new Publisher();
    $publisher = $publisher_service->get_info();

    $this->output(array(
      'code' => 0,
      'msg' => 'ok',
      'publisher' => $publisher,
      'options' => [
        'provinces' => $PROVINCE,
        'cities' => $CITY,
      ]
    ));
  }

  public function update() {
    $attr = $this->get_post_data();

    $publisher_model = new PublisherModel($attr);

    if ($publisher_model->has_modified()) {
      $this->exit_with_error(10, '您的上一次修改还未通过，不能再次申请', 403);
    }

    try {
      $attr = $publisher_model->update();
    } catch (Exception $e) {
      $this->exit_with_error($e->getCode(), $e->getMessage(), 500);
    }

    $this->output(array(
      'code' => 0,
      'msg' => '申请发送成功，请等待管理员审批。',
      'publisher' => $attr,
    ));
  }

  public function delete_info_apply() {
    $model = new PublisherModel();

    $model->remove_info_apply();

    $this->output(array(
      'code' => 0,
      'msg' => '撤销成功',
    ));
  }

  public function apply() {
    $rmb = $_REQUEST['rmb'];

    $publisher_model = new PublisherModel();

    try {
      $attr = $publisher_model->apply($rmb);
    } catch (Exception $e) {
      if ($e->getCode() == 101) {
        $http_code = 403;
      } elseif ($e->getCode() == 102) {
        $http_code = 409;
      } else {
        $http_code = 500;
      }
      $this->exit_with_error($e->getCode(), $e->getMessage(), $http_code);
    }

    $this->output(array(
      'code' => 0,
      'msg' => '申请修改成功',
      'apply' => $attr,
    ));
  }

  public function get_apply() {
    $publisher_service = new Publisher();
    $list = $publisher_service->get_applies();

    $this->output(array(
      'code' => 0,
      'msg' => 'ok',
      'list' => $list,
      'options' => array(
        'apply_status' => PublisherModel::$APPLY_STATUS,
      ),
    ));
  }

  public function delete_apply($id) {
    $service = new Publisher();

    if (!$service->is_my_own_apply($id)) {
      $this->exit_with_error(20, '您不能操作别人的申请', 403);
    }

    $check = $service->remove_apply($id);

    if ($check) {
      $this->output([
        'code' => 0,
        'msg' => '撤销成功',
      ]);
    } else {
      $this->exit_with_error(21, '撤销申请失败', 400);
    }
  }

  public function update_password() {
    $old_password = $_REQUEST['old_password'];
    $new_password = $_REQUEST['new_password'];
    $repassword = $_REQUEST['repassword'];

    if ($new_password != $repassword) {
      $this->exit_with_error(1, '两次密码输入不一致', 403);
    }
    if (strlen($new_password) < 6 || strlen($new_password) > 16) {
      $this->exit_with_error(2, '密码长度不符合要求', 403);
    }

    $publisher_model = new PublisherModel();

    try {
      $publisher_model->update_password($old_password, $new_password);
    } catch (Exception $e) {
      $http_code = $e->getCode() == 104 ? 409 : 500;
      $this->exit_with_error($e->getCode(), $e->getMessage(), $http_code);
    }

    $this->output(array(
      'code' => 0,
      'msg' => '申请修改成功',
      'password' => '********',
    ));
  }

  public function create() {
    $account = $_REQUEST['account'];
    $password = $_REQUEST['password'];
    $repassword = $_REQUEST['repassword'];
    $attr = Utils::array_pick($_REQUEST, PublisherModel::$FIELD_CREATE);

    if (!filter_var($account, FILTER_VALIDATE_EMAIL)) {
      $this->exit_with_error(3, '请填写正确的邮箱账号', 403);
    }

    if ($password != $repassword) {
      $this->exit_with_error(4, '两次密码输入不一致', 403);
    }
    if (strlen($password) < 6 || strlen($password) > 16) {
      $this->exit_with_error(5, '密码长度不符合要求', 403);
    }

    $publisher_service = new Publisher();
    if ($publisher_service->check_account($account)) {
      $this->exit_with_error(6, '此账号已注册过', 409);
    }

    $publisher_model = new PublisherModel($attr);

    try {
      $attr = $publisher_model->create();
    } catch (Exception $e) {
      $this->exit_with_error($e->getCode(), $e->getMessage(), 500);
    }

    $attr['password'] = '********';
    $this->output(array(
      'code' => 0,
      'msg' => '创建成功',
      'publisher' => $attr,
    ));
  }
}