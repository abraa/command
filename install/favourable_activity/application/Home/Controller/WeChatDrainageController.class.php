<?php
/**
 * ====================================
 * 微信
 * ====================================
 * Author: 9009221
 * Date: 2016-07-25
 * ====================================
 * File: WeChatDrainageController.class.php
 * ====================================
 */
namespace Home\Controller;

use Common\Controller\InitController;
use Common\Extend\Wechat;
use Common\Extend\WechatJsSdk;
use \Common\Logic\Wechat\WechatInit;

class WeChatDrainageController extends InitController {
    /**
     * 微信业务逻辑层
     * @var null
     */
    private $logicWechat = NULL;
    /**
     * 公众号ID - 数据库里面的ID
     * @var int
     */
    private $account_id = 9;

    public function __construct() {
        parent::__construct();
        $this->setAppId();
        Wechat::$token = C('token');
        Wechat::$app_id = C('appid');
        Wechat::$app_secret = C('appsecret');

        //实例化逻辑层
        $this->logicWechat = new WechatInit();
    }

    public function index() {
        //测试的 Lemonice
        /*
        $data = array(
            'MsgType'=>'Event',
            'Event'=>'subscribe',
            'FromUserName'=>'oUxlv1ERKD5WUcD0IfRTNE9EZAbQ',
            'Content'=>'D:17032543400778932555',
        );
        */
        $data = Wechat::postData();
        if (empty($data)) {
            $echoStr = $_GET["echostr"];
            if (Wechat::checkSignature()) {
                echo $echoStr;
                exit;
            }
        }
        //设置微信数据
        $this->logicWechat->setDatas($data);
        $this->logicWechat->setText('subscribe', '{$nickname}，等你好久啦~'."\n".'先恭喜您成为瓷肌的尊贵会员，每月我们都会对会员进行限量免费送礼！'."\n".'月度会员送礼进行中，点击免费抢礼品！包邮哦~'."\n".'<a href=\'http://q.chinaskin.cn/#wx_flow?openid={$openid}\'>立即领取</a>');
        $this->logicWechat->setText('account_id', $this->account_id);

        if (is_array($data) && !empty($data)) {
            //根据不同标识符触发业务处理
            $msgType = strtolower($data['MsgType']);
            if(method_exists($this->logicWechat, $msgType)){
                $this->logicWechat->$msgType();
            }
        }
    }

    /**
     * 获取jsSdk
     */
    public function getJsSdk(){
        $authorize = I('param.authorize','');
        $jssdk = new WechatJsSdk(C('appid'), C('appsecret'));
        $signPackage = $jssdk->GetSignPackage($authorize);
        $this->ajaxReturn($signPackage);
    }

    /**
     * 设置当前公众号相关参数
     */
    private function setAppId() {
        $weChatConfig = array(
            'appid' => 'wx817baaa9e9deb835',
            'appsecret' => 'f624af8daea2e35d8eaac3386b732995',
            'token' => 'df0fc93790781c7998cc69e680b761f3'
        );
        C($weChatConfig);
    }
}
