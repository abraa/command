<?php
/**
 * ====================================
 * 数据权限相关
 * ====================================
 * Author: 9009123 (Lemonice)
 * Date: 2017-04-24 14:15
 * ====================================
 * File: DataPowerModel.class.php
 * ====================================
 */

namespace Cpanel\Model;
use Common\Model\CpanelModel;

class DataPowerModel extends CpanelModel {
    protected $tableName = 'data_power';

    private $powerConfig = array();

    public function __construct() {
        parent::__construct();
        $this->powerConfig = load_config(APP_PATH . 'Cpanel/Conf/dataPower.php');
    }

    /**
     * 检查数据权限 - 不传管理员ID则检查当前登录的管理员ID
     * @param string $key
     * @param string $value
     * @param int $user_id
     * @return bool
     */
    public function checkPower($key = '', $value = '', $user_id = 0){
        if(empty($key) || empty($value)){
            return false;
        }
        $user = $user_id <= 0 ? login() : D('Admin')->field('user_id,is_open')->where("user_id = '$user_id'")->find();
        if(empty($user)){
            return false;
        }
        $power_value = $this->where(array('user_id'=>$user['user_id'],'power_name'=>$key))->getField('power_value');
        $power_value = !empty($power_value) ? unserialize($power_value) : array();
        //有设置了对应权限 || 没设置任何相关权限
        if((!empty($power_value) && in_array($value, $power_value)) || empty($power_value)){ //如果没设置数据权限，默认是允许所有权限
            return true;
        }
        return false;
    }

    /**
     * 获取某个管理员的（单个/全部）数据权限
     * @param Int $user_id 管理员ID
     * @param string $key 单个数据权限
     * @return array
     */
    public function getPower($user_id = 0, $key = ''){
        if($user_id <= 0){
            return array();
        }
        $where = array('user_id'=>$user_id);
        if(!empty($key)){
            $where['power_name'] = $key;
        }
        $list = $this->field('power_name,power_value')->where($where)->select();
        $power_value = array();
        if(!empty($list)){
            foreach($list as $k=>$v){
                $power_value[$v['power_name']] = !empty($v['power_value']) ? unserialize($v['power_value']) : array();
                if(!empty($power_value[$v['power_name']])){
                    $power_value[$v['power_name']] = array_values($power_value[$v['power_name']]);
                }
            }
        }
        if(!empty($key) && isset($power_value[$key])){
            return $power_value[$key];
        }
        return $power_value;
    }

    /**
     * 获取所有数据权限列表
     * @param array $selected 选中的值，key必须与$this->powerConfig一致,多选的值组成数组
     * @return array
     */
    public function getPowerList($selected = array()){
        if(empty($this->powerConfig)){
            return array();
        }
        foreach($this->powerConfig as $k=>$v){
            if(!isset($v['value'])){
                $v['value'] = $this->getPowerAllValue($k, (isset($selected[$k]) ? $selected[$k] : array()));  //获取可选择的值
            }
            $this->powerConfig[$k] = $v;
        }
        return $this->powerConfig;
    }

/*==========================================================================================================================*/

    /**
     * 获取微信公众号的列表 - 对应key
     * @param array $selected
     * @return mixed
     */
    protected function valueWechatAccount($selected = array()){
        $account_list = D('WechatAccount')->field('id,text')->select();
        $account_list = $this->parseSelected($account_list, $selected);
        return $account_list;
    }

/*==========================================================================================================================*/
    /**
     * 处理权限的值是否选中
     * @param array $list
     * @param array $selected
     * @return array
     */
    private function parseSelected($list = array(), $selected = array()){
        if(!empty($list) && !empty($selected)){
            foreach($list as $k=>$v){
                if(in_array($v['id'], $selected)){
                    $v['selected'] = true;
                }
                $list[$k] = $v;
            }
        }
        return $list;
    }

    /**
     * 获取数据权限可选择的值 - 所有值
     * @param string $key
     * @param array $selected  选中的值，多个之间组成数组
     * @return array
     */
    private function getPowerAllValue($key = '',$selected = array()){
        $power_value = array();
        if(empty($key) || !isset($this->powerConfig[$key])){
            return $power_value;
        }
        $method = 'value' . $key;
        if(!method_exists($this, $method)){
            return $power_value;
        }
        return $this->$method($selected);
    }
}