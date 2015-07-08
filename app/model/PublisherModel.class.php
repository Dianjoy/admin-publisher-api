<?php
/**
 * Created by PhpStorm.
 * User: hemengyuan
 * Date: 15/6/1
 * Time: 下午3:22
 */

namespace publisher\model;


use diy\model\Base;
use diy\utils\Utils;
use Exception;
use publisher\service\Publisher;
use SQLHelper;

class PublisherModel extends Base {
  const EDIT_NOT_VERIFIED = 0;
  const APPLYING = 0;
  const STATUS_ON = 1;
  const TYPE_PERSONAL = 1;
  const TYPE_COMPANY = 2;
  static $T_EDIT = 't_publisher_edit';
  static $T_APPLY = 't_publisher_apply';
  static $T_INFO = 't_publisher';
  static $FIELD_CREATE = array('account', 'password', 'qq', 'mobile', 'publisher_name');
  static $FIELDS_EDIT = ['telephone', 'qq', 'user_name', 'bank_name', 'bank_adress',
    'card_number', 'province', 'city', 'identity', 'business_license', 'identity_pic',
    'identity_pic_back', 'comment', 'publisher_name', 'publisher_type', 'publisher_id'];
  static $OUT_CYCLE = array(
    1 => '点乐渠道后台',
    2 => '上游后台',
    3 => '截图',
  );
  static $APPLY_STATUS = array(
    '-2' => '撤销',
    '-1' => '申请失败',
    '0' =>' 申请中',
    '1' => '已结款',
    '2' => '申请成功，待结款',
  );

  public function __construct($attr = null) {
    $this->idAttribute = 'publisher_id';
    $attr = is_array($attr) ? $attr : [];
    $attr['publisher_id'] = $_SESSION['publisher_id'];
    parent::__construct($attr);
  }

  public function update(array $attr = null) {
    $attr = $this->attributes;
    // 有一些属性在更新时需要过滤掉
    $attr = Utils::array_pick($attr, self::$FIELDS_EDIT);

    $attr['create_time'] = date("Y-m-d H:i:s");
    $attr['is_verify'] = self::EDIT_NOT_VERIFIED;

    $DB_write = $this->get_write_pdo();
    if (!SQLHelper::insert($DB_write, self::$T_EDIT, $attr)) {
      throw new Exception('申请修改失败', 100);
    }

    return $attr;
  }

  public function apply() {
    $publisher_service = new Publisher();
    $info = $publisher_service->get_info();
    $rmb = (int)$_REQUEST['apply'] * 100;
    if ($info['rmb'] < $rmb) {
      throw new Exception('申请金额超过可提现余额', 101);
    }
    if ($publisher_service->get_apply()) {
      throw new Exception('您有等待审核的申请，暂时不能发起新申请', 102);
    }

    $attr = array(
      'id' => Utils::create_id(),
      'apply_time' => date("Y-m-d H:i:s"),
      'publisher_id' => $_SESSION['publisher_id'],
      'rmb' => $rmb,
      'status' => self::APPLYING,
      'is_pay' => $rmb,
      'tax' => (int)($publisher_service->tax($rmb / 100) * 100),
    );
    $DB_write = $this->get_write_pdo();
    if (!SQLHelper::insert($DB_write, self::$T_APPLY, $attr)) {
      throw new Exception('申请提现失败', 103);
    }

    return $attr;
  }

  public function update_password($old_password, $new_password) {
    $account = $_SESSION['publisher_account'];
    $publisher_service = new Publisher();
    $publisher_model = new PublisherModel();
    if (!$publisher_service->check_password($publisher_model->encrypt($account, $old_password))) {
      throw new Exception('原密码错误', 104);
    }

    $DB_write = $this->get_write_pdo();
    if (!SQLHelper::update($DB_write, self::$T_INFO, array('password' => md5($new_password . $account)), array('id' => $_SESSION['publisher_id']))) {
      throw new Exception('修改失败', 105);
    }
  }

  public function create() {
    $attr = $this->attributes;
    $attr['create_time'] = date("Y-m-d H:i:s");
    $attr['status'] = self::STATUS_ON;
    $attr['password'] = self::encrypt($attr['account'], $attr['password']);
    $DB_write = $this->get_write_pdo();
    if (!SQLHelper::insert($DB_write, self::$T_INFO, $attr)) {
      throw new Exception('创建失败', 106);
    }

    return $attr;
  }

  public function has_modified() {
    $service = new Publisher();
    return $service->info_apply_exist($this->id);
  }

  public function remove_info_apply() {
    $service = new Publisher();
    return $service->remove_info_apply($this->id);
  }

  /**
   * @param $username
   * @param $password
   *
   * @return string
   */
  public function encrypt( $username, $password ) {
    return md5( $password . $username . SALT );
  }
}