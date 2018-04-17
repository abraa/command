<?php
/**
 * ====================================
 * 会员中心 里面的商品图片模型
 * ====================================
 * Author: 9009123 (Lemonice)
 * Date: 2018-01-22 16:49
 * ====================================
 * File: GoodsGalleryCenterModel.class.php
 * ====================================
 */
namespace Common\Model\Home;
use Common\Model\UserCenterModel;

class GoodsGalleryCenterModel extends UserCenterModel{
	protected $_config = 'USER_CENTER';
    protected $tableName = 'goods_gallery';
}