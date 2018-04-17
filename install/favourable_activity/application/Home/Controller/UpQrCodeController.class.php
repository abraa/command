<?php
/**
 * ====================================
 * 微信自动回复
 * ====================================
 * Author: 9009123
 * Date: 2017-08-15 13:45
 * ====================================
 * File: UpQrCodeController.class.php
 * ====================================
 */
namespace Home\Controller;
use Common\Controller\InitController;

class UpQrCodeController extends InitController{
    public function __construct() {
        parent::__construct();
        parent::_initialize();
        $this->dbModel = new \Cpanel\Model\WechatUpqrcodeModel();
    }

    /**
     * 获取某个标识的列表
     */
    public function getList(){
        $account_id = I('request.account_id',0,'intval');  //微信公众号ID
        $identifier = I('request.identifier','','trim');  //二维码的标识（后台设置的）
        if($account_id <= 0){
            $this->error('请传公众号ID');
        }
        if(empty($identifier)){
            $this->error('请传标识！');
        }
        if(!isset($this->dbModel->identifier[$identifier])){
            $this->error('标识不正确！');
        }
        $list = $this->dbModel->field('id,name,file_path')->where(array('account_id'=>$account_id,'identifier'=>$identifier,'locked'=>0))->select();
        if(!empty($list)){
            foreach($list as $key=>$value){
                $value['file_path'] = C('RESOURCE.IMG_URL') . substr($value['file_path'], 1);
                $list[$key] = $value;
            }
        }
        $this->success(!empty($list) ? $list : array());
    }

    /**
     * 随机获取某个二维码
     */
    public function getRand(){
        $account_id = I('request.account_id',0,'intval');  //微信公众号ID
        $identifier = I('request.identifier','','trim');  //二维码的标识（后台设置的）
        if($account_id <= 0){
            $this->error('请传公众号ID');
        }
        if(empty($identifier)){
            $this->error('请传标识！');
        }
        if(!isset($this->dbModel->identifier[$identifier])){
            $this->error('标识不正确！');
        }
        $info = $this->dbModel->field('id,name,file_path')->where(array('account_id'=>$account_id, 'identifier'=>$identifier, 'locked'=>0))->order('rand()')->find();
        if(!empty($info)){
            $info['file_path'] = C('RESOURCE.IMG_URL') . substr($info['file_path'], 1);
        }
        $this->success(!empty($info) ? $info : array());
    }
}