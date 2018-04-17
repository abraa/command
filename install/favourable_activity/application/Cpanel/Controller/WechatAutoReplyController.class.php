<?php
/**
 * ====================================
 * 微信自动回复
 * ====================================
 * Author: 9009123
 * Date: 2017-08-14 10:45
 * ====================================
 * File: WechatAutoReplyController.class.php
 * ====================================
 */
namespace Cpanel\Controller;
use Common\Controller\CpanelController;
use Common\Extend\Time;

class WechatAutoReplyController extends CpanelController{
    private $account_id = '';
    protected $allowAction = array(
        'identifier'
    );

    public function __construct() {
        parent::__construct();

        $this->dbModel = D('WechatUpqrcode');

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

        $this->dbModel->select();
    }

    /**
     * 显示编辑二维码、添加二维码页面
     */
    public function form(){
        $id = I('request.id',0,'intval');
        if($id > 0){
            $data = $this->dbModel->find($id);
            $this->assign('data',$data);
        }
        $account_name = D('WechatAccount')->where(array('id'=>$this->account_id))->getField('text');
        $this->assign('account_name', $account_name);
        $this->assign('account_id', $this->account_id);
        $this->display();
    }

    /**
     * 提交保存、添加
     */
    public function save(){
        $id = I('post.id',0,'intval');
        $identifier = I('post.identifier','','trim');
        $name = I('post.name','','trim');
        $locked = I('post.locked',0,'intval');
        $file = isset($_FILES['file']) ? $_FILES['file'] : array();

        if(empty($identifier)){
            $this->ajaxReturn(array(
                'status'=>0,
                'info'=>'请选择一个标识',
            ));
        }
        if(empty($name)){
            $this->ajaxReturn(array(
                'status'=>0,
                'info'=>'请输入标题',
            ));
        }
        if(empty($file) && $id <= 0){
            $this->ajaxReturn(array(
                'status'=>0,
                'info'=>'请选择一个二维码文件',
            ));
        }
        $root_path = substr(APP_ROOT, 0, -1);
        if(!empty($file) && $file['error'] == 0){
            if(strstr($file['type'], 'image/') === false){
                $this->ajaxReturn(array(
                    'status'=>0,
                    'info'=>'选择的文件类型不是图片',
                ));
            }

            $file_info = pathinfo($file['name']);
            $suffix = isset($file_info['extension']) ? strtolower($file_info['extension']) : '';
            if(empty($suffix)){
                $suffix = str_replace('image/','',$file['type']);
                $suffix = $suffix=='jpeg' ? 'jpg' : $suffix;
            }

            $path = '/upload/wechat_upqrcode/';
            makeDir($root_path . $path);  //检查目录是否存在，不存在则创建
            $file_path = $path . md5(time().rand(100000,999999)) . '.' . $suffix;
            if(!move_uploaded_file($file['tmp_name'], $root_path . $file_path) && !copy($file['tmp_name'], $root_path . $file_path)){
                $this->ajaxReturn(array(
                    'status'=>0,
                    'info'=>'上传文件失败',
                ));
            }
        }
        $old_file_path = '';
        if($id > 0){
            $old_file_path = $this->dbModel->where(array('id'=>$id))->getField('file_path');
        }
        $file_path = empty($file_path) ? $old_file_path : $file_path;

        if($id > 0){  //编辑
            $result = $this->dbModel->where(array('id'=>$id))->save(array(
                'account_id'=>$this->account_id,
                'identifier'=>$identifier,
                'name'=>$name,
                'file_path'=>$file_path,
                'locked'=>$locked,
                'update_time'=>Time::gmTime(),
            ));
            if($result !== false && !empty($file) && $file['error'] == 0 && file_exists($root_path . $old_file_path)){
                unlink($root_path . $old_file_path);  //删除旧文件
            }
            if($result === false){
                $this->ajaxReturn(array(
                    'status'=>0,
                    'info'=>'更新失败',
                ));
            }
        }else{
            $result = $this->dbModel->add(array(
                'account_id'=>$this->account_id,
                'identifier'=>$identifier,
                'name'=>$name,
                'file_path'=>$file_path,
                'locked'=>$locked,
                'create_time'=>Time::gmTime(),
                'update_time'=>Time::gmTime(),
            ));
            if($result === false){
                $this->ajaxReturn(array(
                    'status'=>0,
                    'info'=>'添加失败',
                ));
            }
        }
        $this->ajaxReturn(array(
            'status'=>1,
            'info'=>'保存成功',
        ));
    }

    /**
     * 显示标识符的下拉菜单
     */
    public function identifier(){
        $identifier = I('request.identifier','','trim');
        $data = array();
        foreach($this->dbModel->identifier as $key=>$row){
            $text = $row;
            $row = array(
                'id'=>$key,
                'text'=>$text,
            );
            if(!empty($identifier) && $key == $identifier){
                $row['selected'] = true;
            }
            $data[] = $row;
        }
        $this->ajaxReturn($data);
    }

    /**
     * 自动回复微信内容，显示页面、提交保存
     */
    public function replay(){
        $config = array(  //保存到数据表的内容
            'name'=>'WECHAT_AUTO_REPLAY',
            'type'=>0,
            'title'=>'微信自动回复内容',
            'group'=>0,
            'extra'=>'',
            'remark'=>'微信自动回复',
            'create_time'=>Time::gmTime(),
            'update_time'=>Time::gmTime(),
            'value'=>serialize(array()),
        );
        $config_value = D('Config')->where(array('name'=>$config['name']))->getField('value');
        if(empty($config_value)){
            $config_value = $config['value'];
            D('Config')->add($config);
        }
        $config_value = unserialize($config_value);

        //提交保存
        if(IS_POST){
            $data['effect_start_time'] = I('request.effect_start_time','','trim');
            $data['effect_end_time'] = I('request.effect_end_time','','trim');
            $data['locked'] = I('request.locked',0,'intval');
            $data['content'] = I('request.content','','trim');

            $config_value[$this->account_id] = $data;
            $config['value'] = serialize($config_value);
            unset($config['create_time']);
            D('Config')->where(array('name'=>$config['name']))->save($config);
            $this->success('保存成功!');
        }

        $account_name = D('WechatAccount')->where(array('id'=>$this->account_id))->getField('text');
        $this->assign('account_name', $account_name);
        $this->assign('account_id', $this->account_id);
        $this->assign('replay', (isset($config_value[$this->account_id]) ? $config_value[$this->account_id] : array()));
        $this->display();
    }
}