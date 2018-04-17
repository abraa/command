<?php
/**
 * ====================================
 * 微信公众帐号管理
 * ====================================
 * Author: 9004396
 * Date: 2017-02-22 13:55
 * ====================================
 * Project: new.m.chinaskin.cn
 * File: WechatAccountController.class.php
 * ====================================
 */
namespace Cpanel\Controller;
use Common\Controller\CpanelController;
use Common\Extend\Time;

class WechatKeywordCenterController extends CpanelController{
	protected $tableName = 'WechatKeywordContent';
    private   $account_id = '';

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

    public function form(){

    	$content_id = I('content_id', 0, 'intval');

    	$wechatKeywordContent = D('WechatKeywordContent');
    	$info = $wechatKeywordContent->where(array('id'=>$content_id))->find();

    	if (!empty($info)) {
    		$keywordArr = D('WechatKeyword')->where(array('content_id'=>$content_id))->field('id as keyword_id,keyword,content_id')->select();
    		$info['keyword_arr'] = $keywordArr;
    	}

    	$this->assign('type_arr', $wechatKeywordContent->typeArr);
    	$this->assign('info', $info);
        $this->display();
    }

    /**
     * 更新/新增关键字内容
     * @author lirunqing 2017-7-5
     * @return json
     */
    public function save(){

    	$content_id = I('request.id', 0, 'intval');//内容id
        $params['keyword']       = I('request.keyword'); //关键字
        $params['type']   = I('request.type', 0, 'intval'); //事件类型
        $params['locked']   = I('request.locked', 0, 'intval'); //是否启用
        $params['content']     = I('request.content', '', 'trim'); //内容
        $params['title']      = I('request.title', '', 'trim'); //标题
        $params['wechat_account_id'] = I('request.account_id', 0, 'intval'); //所属微信公众号id

        $wechatKeywordContent = D('WechatKeywordContent');

        if($content_id){
            //更新
            $params['last_update'] = Time::gmTime();
            $wechatKeywordContent->where(array('id'=>$content_id))->save($params);
        }else{
            //添加
            $params['add_time'] = Time::gmTime();
            $content_id = $wechatKeywordContent->data($params)->add();
        }

        //先删除原先的关键字，后在重新添加关键字
        if(!empty($params['keyword'][0])){
            $wechatKeywordModel = D('WechatKeyword');
            foreach($params['keyword'] as $k=>$v){
                if(empty($v)){
                    continue;
                }
                $data_attr[$k]['keyword'] = $v;
                $data_attr[$k]['content_id'] = $content_id;
                $data_attr[$k]['wechat_account_id'] = $params['wechat_account_id'];
                $data_attr[$k]['type'] = $params['type'];
            }
            $wechatKeywordModel->where(array('content_id'=>$content_id))->delete();
            $wechatKeywordModel->addAll($data_attr);
        }

        $this->ajaxReturn(array(
            'status'=>1,
            'info'=>'添加成功',
        ));
    }

    /**
     * 关键字删除
     * @params content_id int 内容id
     * @params keyword_id int 关键字id
     */
    public function rmGoodAttr(){
        $content_id = I('request.content_id', 0, 'intval');
        $keyword_id = I('request.keyword_id', 0, 'intval');
        $res = D('WechatKeyword')->where(array('content_id'=>$content_id, 'id'=>$keyword_id))->delete();
        if($res){
            //操作日志
            $this->logAdmin('删除关键字', 0, array('params'=>"content_id=$content_id and keyword_id=$keyword_id"));

            $this->success();
        }
        else{
            $this->error('删除失败');
        }
    }
}