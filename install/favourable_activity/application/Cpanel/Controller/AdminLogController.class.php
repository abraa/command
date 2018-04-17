<?php
/**
 * ====================================
 * 管理员日志控制器
 * ====================================
 * Author: 9006758
 * Date: 2017/03/
 * ====================================
 * File: AdminLogController.class.php
 * ====================================
 */
namespace Cpanel\Controller;
use Common\Controller\CpanelController;

class AdminLogController extends CpanelController {
    protected $tableName = 'AdminLog';

    public function getMenu(){
        $menu_module = I('request.menu_module', 'cpanel', 'trim');
        $menu_controller = I('request.menu_controller', '', 'trim');
        $menu_method = I('request.menu_method', '', 'trim');

        $type['module'] = $menu_module;
        $type['controller'] = $menu_controller;
        $type['method'] = $menu_method;

        $data = D('Menu')->getDataByType($type);
        $controller_arr = array();
        $method_arr = array();
        foreach($data as $key=>$val){
            if(array_search($val['controller'], $controller_arr) === FALSE){
                $controller_arr[] = $val['controller'];
            }
            if(array_search($val['method'], $method_arr) === FALSE){
                $method_arr[] = $val['method'];
                if(!empty($val['other_method'])){
                    $method_arr =array_merge($method_arr, explode(',', $val['other_method']));
                }
            }
        }
        $menu = array();
        if($menu_controller){
            foreach($method_arr as $v){
                $menu[]['text'] = $v;
            }
        }else{
            foreach($controller_arr as $v){
                $menu[]['text'] = $v;
            }
        }
        $this->ajaxReturn($menu);
    }
}