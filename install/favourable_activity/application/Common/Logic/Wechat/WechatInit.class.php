<?php
/**
 * ====================================
 * 微信公众号
 * ====================================
 * Author: 9009123 (Lemonice)
 * Date: 2017-09-01 13:44
 * ====================================
 * File: WechatInit.class.php
 * ====================================
 */
namespace Common\Logic\Wechat;
use Common\Extend\Wechat;
use Common\Logic\Wechat\WechatData;

class WechatInit extends WechatData{
    /**
     * 微信数据
     * @var array
     */
    protected $data = array();
    public function __construct() {
        $this->user = D('Users');
        $this->wechat = D('BindUser');
    }

    /**
     * 事件处理
     */
    public function event(){
        $this->init();  //初始化

        $Event = strtolower($this->getData('Event'));
        $eventObj = new \Common\Logic\Wechat\Event();
        if(method_exists($eventObj, $Event)){
            $eventObj->setDatas($this->getDatas());
            $eventObj->setTexts($this->getTexts());
            $eventObj->$Event();
        }
    }

    /**
     * 文本回复
     */
    public function text(){
        $this->init(USER_ACT_REPLY);  //初始化
        $Text = new \Common\Logic\Wechat\Text();
        $Text->setDatas($this->getDatas());
        $Text->setTexts($this->getTexts());
        $Text->index();
    }

    /**
     * 语音回复
     */
    public function voice(){
        $this->init(USER_ACT_REPLY);  //初始化
        $Voice = new \Common\Logic\Wechat\Voice();
        $Voice->setDatas($this->getDatas());
        $Voice->setTexts($this->getTexts());
        $Voice->index();
    }

    /**
     * 图片回复
     */
    public function image(){
        $this->init(USER_ACT_REPLY);  //初始化
    }

    /**
     * 视频回复
     */
    public function video(){
        $this->init(USER_ACT_REPLY);  //初始化
    }

    /**
     * 小视频回复
     */
    public function shortvideo(){
        $this->init(USER_ACT_REPLY);  //初始化
    }

    /**
     * 定位回复
     */
    public function location(){
        $this->init(USER_ACT_REPLY);  //初始化
    }

    /**
     * 链接回复
     */
    public function link(){
        $this->init(USER_ACT_REPLY);  //初始化
    }

    /**
     * 获取微信openid
     * @return \Common\Extend\Pay\Wechatpay\用户的openid|mixed
     */
    public function getOpenId(){
        $openid = session('sopenid');
        if(empty($openid)){
            $jsApi = new \Common\Extend\Pay\Wechatpay\JsApiPay();
            $jsApi->appId = WECHAT_APPID;
            $jsApi->appSecret = WECHAT_APPSECRET;
            $jsApi->apiCallUrl = 'http://q.chinaskin.cn'.$_SERVER['REQUEST_URI'];
            $code = I('get.code',NULL,'trim');

            $host = '';
            if ($_SERVER['HTTP_HOST'] != 'q.chinaskin.cn') {  //判断当前域名是否授权域名，不是授权域名组装回调地址
                $host = urlencode($_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']);
            }
            if(is_null($code)){
                $url = $jsApi->createOauthUrlForCode('snsapi_base',$host);
                header("Location: $url");  //跳转过去，为了获取code
            }else{
                $openid = $jsApi->getOpenid();
                if(!empty($openid)){
                    session('sopenid',$openid);
                }else{
                    $url = $jsApi->createOauthUrlForCode('snsapi_userinfo',$host);
                    header("Location: $url");  //跳转过去，为了获取code
                }
            }
        }
        return $openid;
    }
}