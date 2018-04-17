<?php
/**
 * ====================================
 * 每日一堂化妆课公众号自动回复
 * ====================================
 * Author: 9006758
 * Date: 2016-05-23
 * ====================================
 * File: WeChatReplyController.class.php
 * ====================================
 */
namespace Home\Controller;

use Common\Controller\InitController;
use Common\Extend\Wechat;
use \Common\Logic\Wechat\WechatInit;

class WeChatReplyController extends InitController {
    /**
     * 微信业务逻辑层
     * @var null
     */
    private $logicWechat = NULL;
    /**
     * 公众号ID - 数据库里面的ID
     * @var int
     */
    private $account_id = 1;

    //关注提示语
    private $attention_text = "Hello~我是菲爷，谢谢你关注我！\n在这里，我会分享我的化妆护肤心得！\n希望通过我的努力能够让你越来越美！\n不会化妆不会护肤的女孩，发送关键词【学变美】，我会马上回复哦！\n有时候可能太忙，可以留下微信号，看到马上找你！";

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
        //测试Lemonice
        /*
        $data = array(
            'MsgType'=>'Text',
            'Event'=>'Text',
            'EventKey'=>'asdasdasd',
            'FromUserName'=>'ogXq9uEITMADmJGmxi8hixJSruTI',
            'Content'=>'asdasdsasdsadasdsad',
        );
        */
        $data = Wechat::postData();
        if (empty($data)) {
            $echoStr = $_GET["echostr"];
            if (\Common\Extend\Wechat::checkSignature()) {
                echo $echoStr;
                exit;
            }
        }
        //设置微信数据
        $this->logicWechat->setDatas($data);
        $this->logicWechat->setText('subscribe', $this->attention_text);
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
     * 设置当前公众号的配置
     */
    private function setAppId() {
        $weChatConfig = array(
            'appid' => 'wxa303070fc69d9ed9',
            'appsecret' => '6e13bf4b50832c0730d0ca4a4bfc0d26',
            'token' => 'df0fc93790781c7998cc69e680b761f3'
        );
        C($weChatConfig);
    }

}
