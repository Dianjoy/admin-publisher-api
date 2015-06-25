<?php
/**
 * Created by PhpStorm.
 * User: hemengyuan
 * Date: 15/6/1
 * Time: 下午2:33
 */

namespace publisher\service;


use diy\service\Base;
use PDO;
use publisher\model\PublisherModel;

class Publisher extends Base {
  public function stat($start, $end) {
    $sql = "select a.id,ad_name,package_id,a.out_rmb,sum(out_num) as out_num,sum(b.out_rmb*out_num) as outcome
    from t_pub_info as a join t_pub_log as b on a.id=b.pub_id where a.publisher_id=:id and quote_date>=:start and quote_date<=:end group by a.id";
    $DB = $this->get_read_pdo();
    $state = $DB->prepare($sql);
    $state->execute(array(':id' => $_SESSION['publisher_id'], ':start' => $start, ':end' => $end));
    return $state->fetchAll(PDO::FETCH_ASSOC);
  }

  public function get_list() {
    $sql = "select id,ad_name,package_id,status,out_rmb,create_time,status_time,ad_url,material_url,ad_size,ad_lib,out_cycle,quality,valid_data,advertiser_url,advertiser_user,advertiser_pwd,others
    from t_pub_info where publisher_id=:id";
    $DB = $this->get_read_pdo();
    $state = $DB->prepare($sql);
    $state->execute(array(':id' => $_SESSION['publisher_id']));
    return $state->fetchAll(PDO::FETCH_ASSOC);
  }

  public function get_info() {
    $sql = "select publisher_name,comment,telephone,qq,rmb_in,rmb_out,user_name,bank_name,bank_address,card_number,province,city,identity,publisher_type,business_license,identity_pic,identity_pic_back
    from t_publisher where id=:id";
    $DB = $this->get_read_pdo();
    $state = $DB->prepare($sql);
    $state->execute(array(':id' => $_SESSION['publisher_id']));
    $result = $state->fetch(PDO::FETCH_ASSOC);

    $date = date("d");
    if ($date < 16) {
      $start = date("Y-m-d", mktime(0, 0, 0, date("m") - 1, 1, date("Y")));
      $end = date("Y-m-d", mktime(0, 0, 0, date("m"), 1, date("Y")));
    } else {
      $start = date("Y-m-d", mktime(0, 0, 0, date("m") - 1, 16, date("Y")));
      $end = date("Y-m-d", mktime(0, 0, 0, date("m"), 16, date("Y")));
    }
    $rmb = self::get_income_in_interval($start, $end);
    $result['rmb'] = $result['rmb_in'] + $rmb - $result['rmb_out'];

    return $result;
  }

  public function get_income_in_interval($start, $end) {
    $sql = "select sum(a.out_rmb*out_num) from t_pub_log as a join t_pub_info as b on a.pub_id=b.id where quote_date>=:start and quote_date<:end and publisher_id=:id";
    $DB = $this->get_read_pdo();
    $state = $DB->prepare($sql);
    $state->execute(array(':id' => $_SESSION['publisher_id'], ':start' => $start, ':end' => $end));
    return $state->fetch(PDO::FETCH_COLUMN);
  }

  public function get_apply() {
    $sql = "select 'x' from t_publisher_apply where publisher_id=:id and status=" . PublisherModel::APPLYING;
    $DB = $this->get_read_pdo();
    $state = $DB->prepare($sql);
    $state->execute(array(':id' => $_SESSION['publisher_id']));
    return $state->fetch(PDO::FETCH_COLUMN);
  }

  public function get_applies() {
    $sql = "select apply_time,rmb,status from t_publisher_apply where publisher_id=:id";
    $DB = $this->get_read_pdo();
    $state = $DB->prepare($sql);
    $state->execute(array(':id' => $_SESSION['publisher_id']));
    return $state->fetchAll(PDO::FETCH_ASSOC);
  }

  public function check_password($password) {
    $sql = "select 'x' from t_publisher where publisher_id=:id and and password='$password' and status=" . PublisherModel::STATUS_ON;
    $DB = $this->get_read_pdo();
    $state = $DB->prepare($sql);
    $state->execute(array(':id' => $_SESSION['publisher_id']));
    return $state->fetch(PDO::FETCH_COLUMN);
  }

  public function check_account($account) {
    $sql = "select 'x' from t_publisher where account=:account";
    $DB = $this->get_read_pdo();
    $state = $DB->prepare($sql);
    $state->execute(array(':account' => $account));
    return $state->fetch(PDO::FETCH_COLUMN);
  }

  public function validate($username, $password) {
    $publisher_model = new PublisherModel();
    $password = $publisher_model->encrypt( $username, $password );
    $pdo = $this->get_read_pdo();
    $sql = "SELECT `id`,`publisher_name`
            FROM `t_publisher`
            WHERE `account`=:account AND `password`=:password AND `status`=" . PublisherModel::STATUS_ON;
    $state = $pdo->prepare($sql);
    $state->execute(array(
      ':account' => $username,
      ':password' => $password,
    ));
    $user = $state->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
      return false;
    }

    // 记录用户信息
    session_start();
    $_SESSION['publisher_account'] = $username;
    $_SESSION['publisher_id'] = $user['id'];
    $_SESSION['publisher_name'] = $user['publisher_name'];

    return true;
  }
}