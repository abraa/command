<?php
 /**
 * ====================================
 * ggzy
 * ====================================
 * Author: 1002571
 * Date: 2017/12/6 18:16
 * ====================================
 * File: WechatMenu.php
 * ====================================
 */

namespace app\admin\logic;

use app\admin\BaseLogic;
use app\common\support\WechatSupport;
use extend\Wechat;

class WechatMenu extends BaseLogic{


    /**
     * 更新菜单到公众号
     */
    public function create(){
        $params = $this->getData();
        if(method_exists($this->dbModel,'filter')){
            $params['locked'] = 0;
        }
        $this->dbModel->filter($params);
        $meun = array();
        $data = $this->dbModel->treegrid($params);

        if(empty($data)){
            return ['errcode'=>1,'errmsg'=>'当前公众号没有启用的菜单'];
        }

        $account = WechatSupport::getConfig($this->getData('account_id'));
        if(empty($account)){
            return ['errcode'=>1,'errmsg'=>'公众号不存在'];
        }

        foreach ($data as $item){
            if($item['children']){
                $child = array();
                foreach ($item['children'] as $children){
                    if ($children['action'] == 'url'){
                        $child[] = array('name' => $children['text'],'type' => 'view','url' => $children['action_param']);
                    }else{
                        $child[] = array('name' => $children['text'],'type' => 'click','key' => $children['action_param']);
                    }
                }
                $meun['button'][] = array('name' => $item['text'],'sub_button' => $child);
            }else{
                if ($item['action'] == 'url'){
                    $meun['button'][] = array('name' => $item['text'], 'type' => 'view', 'url' => $item['action_param']);
                }else{
                    $meun['button'][] = array('name' => $item['text'], 'type' => 'click', 'key' => $item['action_param']);
                }
            }
        }
        Wechat::init($account);
        $ret = Wechat::createMenu($meun);
        if($ret['errcode'] == 0){
            return ['errcode'=>0,'errmsg'=>'操作成功'];
        }else{
            return ['errcode'=>1,'errmsg'=>'操作失败，状态码：'.$ret['errcode'].'错误信息：'.$ret['errmsg']];
        }
    }


    /**
     * 删除公众号菜单
     */
    public function remove(){
        $account = WechatSupport::getConfig($this->getData('account_id'));
        if(empty($account)){
            return ['errcode'=>1,'errmsg'=>'公众号不存在'];
        }
        Wechat::init($account);
        $ret = Wechat::removeMenu();
        if($ret['errcode'] == 0){
            return ['errcode'=>0,'errmsg'=>'操作成功'];
        }else{
            return ['errcode'=>1,'errmsg'=>'操作失败，状态码：'.$ret['errcode'].'错误信息：'.$ret['errmsg']];
        }
    }

    function _after_delete(){
        /*==== menu删除时删除子菜单 ====*/
        $pk = $this->dbModel->getPk();
        if (isset($this->data[$pk]) && !empty($this->data[$pk])) {
            if (strpos($this->data[$pk], ',') !== false) {
                $where['pid'] = ['IN', $this->data[$pk]];
            } else {
                $where['pid'] = $this->data[$pk];
            }
            $this->dbModel->where($where)->delete();
        }
    }




}