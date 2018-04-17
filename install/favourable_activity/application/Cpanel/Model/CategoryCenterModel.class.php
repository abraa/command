<?php
/**
 * ====================================
 * 会员中心商品属性模型
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

use Common\Extend\Base\Common;


class CategoryCenterModel extends CpanelUserCenterModel
{
    protected $tableName = 'category';

    protected $_validate = array(
        array('text','require','请输入分类名称'),
    );

    public function grid($params = array()) {
        $field = 'cat_id,cat_id AS id,cat_id AS tree_id,';
        $field .= 'cat_name as text,cat_name,keyword,';
        $field .= 'cat_desc,is_view,parent_id as pid,parent_id';
        $data = $this->field($field)->order("parent_id ASC, tree_id DESC")->getAll();

        if($data){
            Common::tree($data, $params['selected'], $params['type']);
        }
        if(isset($params['all']) && $params['all']=='all'){
            foreach($data as &$val){
                if($val['id'] === 0){
                    $val['text'] = '全部';
                }
            }

        }
        return $data ? $data : array();
    }

    /*public function _before_write(&$data){
        echo '<pre>';
        print_r($data);exit;
    }*/




}
