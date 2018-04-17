<?php
/**
 * ====================================
 * 微信管理
 * ====================================
 * Author: 9004396
 * Date: 2017-01-13 09:40
 * ====================================
 * Project: new.m.chinaskin.cn
 * File: WeChatController.class.php
 * ====================================
 */
namespace Cpanel\Controller;
use Common\Controller\CpanelController;
use Common\Extend\Wechat;

class WeChatMenuController extends CpanelController{
    protected $tableName = 'wechat_menu';
    private $account_id = '';

    public function __construct() {
        parent::__construct();

        $this->account_id = I('request.account_id','','trim');
        if(empty($this->account_id)){  //没有传account_id，获取当前默认
            $this->account_id = D('WechatAccount')->getField('id');
            $_GET['account_id'] = $this->account_id;
            $_REQUEST['account_id'] = $this->account_id;
            $_POST['account_id'] = $this->account_id;
        }
    }

    public function _before_index(){
        $account_list = D('WechatAccount')->field('id,text')->select();
        $this->assign('account_list', $account_list);
        $this->assign('account_id', $this->account_id);
    }
    /**
     * 更新菜单到公众号
     */
    public function create(){
        $params = array('account_id'=>$this->account_id);
        if(method_exists($this->dbModel,'filter')){
            $params['locked'] = 0;
        }
        $this->dbModel->filter($params);
        $meun = array();
        $data = $this->dbModel->grid();
        $account = D('WechatAccount')->getAccountKey($this->account_id);

        if(empty($data)){
            $this->error('当前公众号没有启用的菜单');
        }
        if(empty($account)){
            $this->error('公众号不存在');
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
                if($item['url']){
                    $meun['button'][] = array('name' => $item['text'], 'type' => 'view', 'url' => $item['action_param']);
                }else{
                    $meun['button'][] = array('name' => $item['text'], 'type' => 'click', 'key' => $item['action_param']);
                }
            }
        }

        Wechat::$app_id = $account['app_id'];
        Wechat::$app_secret = $account['app_secret'];
        $ret = Wechat::createMenu($meun);
        if($ret['errcode'] == 0){
            $this->success('操作成功');
        }else{
            $this->error('操作失败，状态码：'.$ret['errcode'].'错误信息：'.$ret['errmsg']);
        }
    }

    /**
     * 删除公众号菜单
     */
    public function remove(){
        $account = D('WechatAccount')->getAccountKey($this->account_id);
        if(empty($account)){
            $this->error('公众号不存在');
        }
        Wechat::$app_id = $account['app_id'];
        Wechat::$app_secret = $account['app_secret'];
        $ret = Wechat::removeMenu();
        if($ret['errcode'] == 0){
            $this->success('操作成功');
        }else{
            $this->error('状态码：'.$ret['errcode'].'错误信息：'.$ret['errmsg']);
        }
    }

}