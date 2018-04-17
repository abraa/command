<?php
/**
 * ====================================
 * 微信公众号 - 关注、取消关注相关操作
 * ====================================
 * Author: 9009123 (Lemonice)
 * Date: 2017-09-01 15:14
 * ====================================
 * File: Subscribe.class.php
 * ====================================
 */
namespace Common\Logic\Wechat;
use Common\Extend\Wechat;
use Common\Extend\Curl;
use Common\Logic\Wechat\WechatData;

class Subscribe extends WechatData{
    /**
     * 普通关注事件回复文字
     * @var string
     */
    private $generalSubscribeText = '{$nickname}，您好，你有任何肌肤问题都可以随时联系我，我是您贴身的护肤顾问小瓷，请保存我们的官方热线电话020-22005555，识别下面发送的二维码可直接添加通讯录哦';
    /**
     * 带参数的二维码关注事件
     */
    public function eventKey(){
        Curl::$timeOut = 1;
        Curl::$headers = array(
            'Content-type: text/html; charset=utf-8'
        );
        Curl::post('http://vtm.chinaskin.cn/weChat/index.json', $GLOBALS['HTTP_RAW_POST_DATA']);
        $this->sayNothing();
    }

    /**
     * 普通关注事件
     * @throws \Exception
     */
    public function general(){
        //关注欢迎消息推送
        $content = $this->generalSubscribe();
        Wechat::serviceText($content);
        $subscribeQrcode = $this->getText('subscribeQrcode');  //获取不同公众号的文字回复区别
        //上传二维码名片，并推送图片
        if(!empty($subscribeQrcode)){
            $data_media = Wechat::mediaUpload($subscribeQrcode, "image");
            $media_id = $data_media['media_id'];            //生产环境
            Wechat::serviceImage($media_id);
        }
    }

    /**
     * 取消关注
     */
    public function unSubscribe(){
        $info = $this->getDatas();
        $data = array('subscribe' => 0, 'cancel_time' => time(), 'openid' => $info['FromUserName']);
        D('BindUser')->updateUser($data);
        //删除标签的绑定
        D('WechatTagBind')->where("openid = '$info[FromUserName]'")->delete();
    }

    /**
     * 普通关注
     * @return bool|string
     */
    private function generalSubscribe() {
        $info = $this->getDatas();
        $data['openid'] = $info['FromUserName'];
        $userInfo = Wechat::getUserInfo();
        if (empty($userInfo)) return false;
        $field = array('nickname', 'sex', 'language', 'city', 'province', 'country', 'headimgurl', 'remark', 'subscribe', 'subscribe_time');
        foreach ($userInfo as $key => $val) {
            if (!in_array($key, $field)) continue;
            $data[$key] = $val;
        }
        $nickname = empty($userInfo['nickname']) ? '' : $userInfo['nickname'] . ',';        //微信昵称

        $BindUser = D('BindUser');
        $msg = $this->getText('subscribe');  //获取不同公众号的文字回复区别
        if ($BindUser->isSubcribe($data['openid'])) {
            $data['subscribe_time'] = $userInfo['subscribe_time'];        //最近关注时间（可能是第二次以上关注）
            $BindUser->updateUser($data);
            $content = str_replace(array('{$nickname}','{$openid}'), array($userInfo['nickname'],$data['openid']), (!empty($msg) ? $msg : $this->generalSubscribeText));
        } else {
            $data['add_time'] = $userInfo['subscribe_time'];
            $data['subscribe_time'] = $userInfo['subscribe_time'];
            $BindUser->add($data);
            $content = str_replace(array('{$nickname}','{$openid}'), array($nickname,$data['openid']), (!empty($msg) ? $msg : $this->generalSubscribeText));
        }
        return $content;
    }
}