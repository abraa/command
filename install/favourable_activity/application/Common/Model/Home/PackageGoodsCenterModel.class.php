<?php
/**
 * ====================================
 * 会员中心 里面的套装子商品模型
 * ====================================
 * Author: 9009123 (Lemonice)
 * Date: 2018-01-22 16:49
 * ====================================
 * File: PackageGoodsCenterModel.class.php
 * ====================================
 */
namespace Common\Model\Home;
use Common\Model\UserCenterModel;

class PackageGoodsCenterModel extends UserCenterModel{
	protected $_config = 'USER_CENTER';
    protected $tableName = 'package_goods';
}