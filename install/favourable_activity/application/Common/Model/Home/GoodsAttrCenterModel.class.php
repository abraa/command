<?php
/**
 * ====================================
 * 会员中心 里面的商品属性模型
 * ====================================
 * Author: 9009123 (Lemonice)
 * Date: 2018-01-22 13:49
 * ====================================
 * File: GoodsAttrCenterModel.class.php
 * ====================================
 */
namespace Common\Model\Home;
use Common\Model\UserCenterModel;

class GoodsAttrCenterModel extends UserCenterModel{
	protected $_config = 'USER_CENTER';
    protected $tableName = 'goods_attr';
}