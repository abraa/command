<?php
/**
 * ====================================
 * 会员中心属性模型
 * ====================================
 * Author: 9006758
 * Date: 2017-04-08
 * ====================================
 * Project: new.m.chinaskin.cn
 * File: AttributeCenterModel.class.php
 * ====================================
 */
namespace Cpanel\Model;
use Common\Model\CpanelUserCenterModel;


class AttributeCenterModel extends CpanelUserCenterModel
{
    protected $tableName = 'attribute';

	public function filter(&$params){
		if(!empty($params['cat_id'])){
			$where['a.cat_id'] = intval($params['cat_id']);
		}

		return $this->alias('a')
				->field('a.attr_id,a.attr_name,c.cat_name')
				->where($where)
				->order('a.attr_id desc')
				->join("left join __CATEGORY__ as c on a.cat_id=c.cat_id");

	}

}
