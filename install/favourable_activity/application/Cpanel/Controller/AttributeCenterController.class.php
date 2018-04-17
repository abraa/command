<?php
/**
 * ====================================
 * 会员中心商品属性控制器
 * ====================================
 * Author: 9006758
 * Date: 2017-04-08
 * ====================================
 * Project: new.m.chinaskin.cn
 * File: AttributeCenterController.class.php
 * ====================================
 */
namespace Cpanel\Controller;
use Common\Controller\CpanelController;

class AttributeCenterController extends CpanelController{
    protected $tableName = 'AttributeCenter';

    /**
     * 获取属性，商品管理
     */
	public function getAttrs(){
		$data = $this->dbModel->order('attr_id')->getAll();
		$this->ajaxReturn($data);
	}
}