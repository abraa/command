<?php
/**
 * ====================================
 * 会员中心文章属性模型
 * ====================================
 * Author: lirunqing
 * Date: 2017-07-03
 * ====================================
 * Project: new.m.chinaskin.cn
 * File: ArticleManagementModel.class.php
 * ====================================
 */
namespace Cpanel\Model;
use Common\Model\CpanelUserCenterModel;
use Common\Extend\Base\Common;

class ArticleManagementModel extends CpanelUserCenterModel{

	protected $tableName = 'article';

	public function grid($params = array()) {
        $field = 'art_id,art_id AS id,art_id AS tree_id,';
        $field .= 'art_name as text,art_name,keyword,sort_order,';
        $field .= 'art_desc,is_view,parent_id as pid,parent_id';
        $data = $this->field($field)->order("parent_id ASC, tree_id DESC")->getAll();

        if($data){
            Common::tree($data, $params['selected'], $params['type']);
        }

        return $data ? $data : array();
    }
}