<?php
/**
 * ====================================
 * 管理员管理日志模型
 * ====================================
 * Author: 9006758
 * Date: 2017/3/
 * ====================================
 * File: AdminLogModel.class.php
 * ====================================
 */

namespace Cpanel\Model;
use Common\Model\CpanelModel;

class AdminLogModel extends CpanelModel {

    /**
     * 获取列表
     * @param array $params
     * @return mixed
     */
    public function filter($params) {
        $module = trim($params['menu_module']);
        $controller = trim($params['menu_controller']);
        $method = trim($params['menu_method']);
        $keyword = trim($params['keyword']);

        if($module){
            $where['log.module_name'] = $module;
        }
        if($controller){
            $where['log.controller_name'] = $controller;
        }
        if($method && $method!='*'){
            $where['log.action_name'] = $method;
        }
        if($keyword){
            $where['_string'] = "a.user_name='$keyword' or log.note like '%$keyword%'";
        }
        $params['sort'] = !empty($params['sort']) ? 'log.'.$params['sort'] : 'log.id';
        $params['order'] = !empty($params['order']) ? $params['order'] : 'desc';

        $field = 'a.user_name,a.real_name,';
        $field .= 'log.id,log.module_name,log.controller_name,log.action_name,log.create_time,log.note,log.params';
        return $this->alias('log')
                ->join('__ADMIN__ as a on log.user_id=a.user_id')
                ->field($field)
                ->where($where)
                ->order('log.create_time desc')
                ->limit($params['page'], $params['rows'])
                ->order($params['sort'].' '.$params['order']);
    }

    /**
     * 后台操作日志
     * @param $message  操作信息
     * @param int $user_id  管理员id
     * @param array $merge_data 日志信息
     * @return bool
     */
    public function addLog($message, $user_id = 0, $merge_data = array()) {
        if(empty($user_id)){
            $user_id = login('user_id');
        }
        $data = array(
            'user_id' => $user_id,
            'module_name' => MODULE_NAME,
            'controller_name' => CONTROLLER_NAME,
            'action_name' => ACTION_NAME,
            'note' => $message,
            'create_time' => time()
        );
        if(!empty($merge_data)){
            $data = array_merge($data, $merge_data);
        }
        $this->data($data);
        $result = $this->add();
        return $result ? true : false;
    }

}