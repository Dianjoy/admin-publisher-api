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
    $sql = "select publisher_name,comment,telephone,qq,rmb_in,rmb_out,user_name,bank_name,bank_address,card_number,province,city,identity,publisher_type,business_license,identity_pic,identity_pic_back,account
    from t_publisher where id=:id";
    $DB = $this->get_read_pdo();
    $state = $DB->prepare($sql);
    $state->execute(array(':id' => $_SESSION['publisher_id']));
    $result = $state->fetch(PDO::FETCH_ASSOC);


    $start = date("Y-m-d", mktime(0, 0, 0, date("m") - 1, 1, date("Y")));
    $end = date("Y-m-d", mktime(0, 0, 0, date("m"), 1, date("Y")));
    $sql = "select sum(a.out_rmb*out_num) from t_pub_log as a join t_pub_info as b on a.pub_id=b.id where quote_date>=:start and quote_date<:end and publisher_id=:id";
    $DB = $this->get_read_pdo();
    $state = $DB->prepare($sql);
    $state->execute(array(':id' => $_SESSION['publisher_id'], ':start' => $start, ':end' => $end));
    $rmb = $state->fetch(PDO::FETCH_COLUMN);

    $result['rmb'] = $result['rmb_in'] + $rmb - $result['rmb_out'];

    $tax = 0;
    if ($result['publisher_type'] == PublisherModel::TYPE_PERSONAL) {
      $tax = self::tax($result['rmb'] / 100);
    }
    $result['after_tax'] = $result['rmb'] - $tax * 100;

    $result['applying'] = (boolean)$this->get_apply();
    
    $result['whole_info'] = !empty($result['user_name']) && !empty($result['qq']) && !empty($result['mobile']) && !empty($result['bank_name']) && !empty($result['bank_address']) && !empty($result['province']) && !empty($result['city']) && !empty($result['card_number']) && (($result['publisher_type'] == 2 && !empty($result['telephone']) && !empty($result['company_name']) && !empty($result['business_license'])) || ($result['publisher_type'] == 1 && !empty($result['identity']) && !empty($result['identity_pic']) && !empty($result['identity_pic_back'])));

    $sql = "SELECT `telephone`,`qq`,`user_name`,`bank_name`,`bank_address`,
              `card_number`,`province`,`city`,`identity`,`business_license`,
              `identity_pic`,`identity_pic_back`,`comment`,`create_time`,
              `publisher_name`,`publisher_type`
            FROM `t_publisher_edit`
            WHERE `publisher_id`=:id AND `is_verify`=" . PublisherModel::EDIT_NOT_VERIFIED . "
            ORDER BY `create_time` DESC
            LIMIT 1";
    $DB = $this->get_read_pdo();
    $state = $DB->prepare($sql);
    $state->execute(array(':id' => $_SESSION['publisher_id']));
    $result['editing'] = $state->fetch(PDO::FETCH_ASSOC);

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
    $sql = "SELECT `id`,`apply_time`,`rmb`,`status`
            FROM `t_publisher_apply`
            WHERE `publisher_id`=:id AND `status`>-2
            ORDER BY `apply_time` DESC";
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

  public function tax($rmb) {
    $tax = 0;
    if ($rmb > 78125) {
      $tax = $rmb * 0.32 - 7000;
    } elseif ($rmb > 31250) {
      $tax = $rmb * 0.24 - 2000;
    } elseif ($rmb > 4000) {
      $tax = $rmb * 0.16;
    } elseif ($rmb > 800) {
      $tax = ($rmb - 800) * 0.2;
    }
    return $tax;
  }

  public function info_apply_exist( $id ) {
    $sql = "SELECT 'x'
            FROM `t_publisher_edit`
            WHERE `publisher_id`='$id' AND `is_verify`=" . PublisherModel::EDIT_NOT_VERIFIED;
    $DB = $this->get_read_pdo();
    return $DB->query($sql)->fetchColumn();
  }

  public function remove_info_apply( $id ) {
    $sql = "UPDATE `t_publisher_edit`
            SET `is_verify`=-1
            WHERE `publisher_id`='$id' AND `is_verify`=" . PublisherModel::EDIT_NOT_VERIFIED;
    $DB = $this->get_write_pdo();
    return $DB->exec($sql);
  }

  public function is_my_own_apply( $id ) {
    $me = $_SESSION['publisher_id'];
    $sql = "SELECT 'x'
            FROM `t_publisher_apply`
            WHERE `id`=:id AND `publisher_id`='$me'";
    $DB = $this->get_read_pdo();
    $state = $DB->prepare($sql);
    $state->execute([':id' => $id]);
    return $state->fetchColumn();
  }

  public function remove_apply( $id ) {
    $sql = "UPDATE `t_publisher_apply`
            SET `status`=-2
            WHERE `id`=:id";
    $DB = $this->get_write_pdo();
    $state = $DB->prepare($sql);
    return $state->execute([':id' => $id]);
  }
}