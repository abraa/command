<?php
/**
 * ====================================
 * 会员中心 里面的新零售订单详情模型
 * ====================================
 * Author: 9009123 (Lemonice)
 * Date: 2017-07-04 17:28
 * ====================================
 * File: RetailOrderCenterModel.class.php
 * ====================================
 */
namespace Home\Model;
use Common\Model\CustomizeModel;

class RetailOrderCenterModel extends CustomizeModel{
	protected $_config = 'USER_CENTER';
    protected $_table = 'RetailOrder';
}