<?php
/**
 * ====================================
 * 菜单模型
 * ====================================
 * Author: Hugo
 * Date: 14-5-20 下午9:28
 * ====================================
 * File: MenuModel.class.php
 * ====================================
 */
namespace Cpanel\Model;
use Common\Model\CpanelModel;
use Common\Extend\Base\Common;

class MenuModel extends CpanelModel {
    protected $_validate = array(
        array('text','require','{%menu_name_lost}'),
        array('module','require','{%module_name_lost}'),
        array('controller','require','{%class_name_lost}'),
        array('method','require','{%method_name_lost}'),
        array('pid', '_validatePid', '{%PARENT_ERROR}', self::EXISTS_VALIDATE, 'callback', self::MODEL_UPDATE)
    );

    public function filter($params) {
        $where = array();
        if($params['text']) {
            $where['text'] = array('like', "%{$params['text']}%");
        }

        is_null($params['pid']) || $where['pid'] = $params['pid'];
        is_null($params['display']) || $where['display'] = $params['display'];
        empty($params['id']) || $where['id'] = array('in', $params['id']);

        $this->where($where);
        return $this;
    }

    /**
     * 读取树型数据
     * @return mixed
     */
    public function grid($params = array()) {

        $data = $this->field("*, id AS tree_id")->order("pid ASC, orderby DESC")->getAll();

        if($data){
            foreach($data as $key => $row){
                $row['power'] = "power('{$row['module']}-{$row['controller']}-{$row['method']}')";
                $data[$key] = $row;
            }
            Common::tree($data, $params['selected'], $params['type']);
        }
        return $data ? $data : array();
    }

    /**
     * 根据类型获取当前类型下一类型的数据
     * @param array $type
     * @return array|mixed
     */
    public function getDataByType($type = array()){
//        $where['display'] = 1;
        $module = !empty($type['module']) ? $type['module'] : 'cpanel';
        if(empty($module)){
            return array();
        }
        $where['module'] = $module;

        if(!empty($type['controller'])){
            //读取 method
            $where['controller'] = $type['controller'];
        }
        $data = $this->where($where)->field('text,module,controller,method,other_method')->select();
        return $data;
    }
}