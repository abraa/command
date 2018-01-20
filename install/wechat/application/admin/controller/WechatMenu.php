<?php
 /**
 * ====================================
 * ggzy
 * ====================================
 * Author: 1002571
 * Date: 2017/12/6 10:37
 * ====================================
 * File: WechatMenu.php
 * ====================================
 */

namespace app\admin\controller;

use app\admin\BaseController;

class WechatMenu extends BaseController{
    protected $isTree = true;

    /**
     * 更新菜单到公众号
     */
    public function create(){
        $ret = $this->logic->create();
        if($ret['errcode'] == 0){
            $this->success('操作成功');
        }else{
            $this->error($ret['errmsg']);
        }
    }

    /**
     * 删除公众号菜单
     */
    public function remove(){
        $ret = $this->logic->remove();
        if($ret['errcode'] == 0){
            $this->success('操作成功');
        }else{
            $this->error($ret['errmsg']);
        }
    }

}