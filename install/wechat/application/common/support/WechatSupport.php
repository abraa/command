<?php
/**
 * 微信业务支持静态类
 * User: 1002571
 * Date: 2017/11/10
 * Time: 10:28
 */

namespace app\common\support;


use extend\Wechat;
use think\Model;

class WechatSupport {
    const WECHATUSER_MODEL = 'WechatUser';
    const WECHATACCOUNT_MODEL = 'WechatAccount';


    protected static $wechatConfig = [];
    protected static $wechatAccount = null;



    /**
     * 获取模型
     * @param $modelName
     * @return \\think\Model
     */
    protected static function getModel($modelName){
        return  model('admin/'.ucfirst($modelName));
    }

    /**
     * 获取微信关注用户模型
     * @return \think\Model
     */
    public static function getWechatUserModel(){
        return  self::getModel(self::WECHATUSER_MODEL);
    }

    /**
     * 获取微信账号模型
     * @return \think\Model
     */
    public static function getWechatAccountModel(){
        return  self::getModel(self::WECHATACCOUNT_MODEL);
    }

    /**
     * 获取当前使用配置的微信公众号索引(要有值你得先getConfig($key)获取一次配置信息才会更改)
     * @return number|null
     */
    public static function getWechatAccount(){
        return self::$wechatAccount;
    }

    /**
     * 设置当前使用配置的微信公众号索引(+这个只是给你个不需要获取配置信息 , 主动存公众号索引的地方....)
     * @param int $key    微信公众号Id索引
     * @return null|number
     */
    public static function setWechatAccount($key){
        self::$wechatAccount = $key;
        return self::$wechatAccount;
    }

    /**
     * 获取微信公众号配置
     * @param string $key       //公众号主键
     * @param string $name
     * @return mixed
     */
    public static function getConfig($key="",$name=""){
        if(empty(self::$wechatConfig)){                         //获取所有微信公众号配置
            self::$wechatConfig = self::getWechatAccountModel()->getWechatConfig();
        }
        if(empty($key)){                //没有key获取全部
            return self::$wechatConfig;
        }
        if(!isset(self::$wechatConfig[$key])){
            return null;
        }else{
            self::$wechatAccount = $key;    //修改当前使用配置的微信公众号索引
        }
        return empty($name) ? self::$wechatConfig[$key] : (isset(self::$wechatConfig[$key][$name]) ? self::$wechatConfig[$key][$name] : null);
    }

    /**
     *  验证接入 echostr
     * @return mixed
     */
    public static function checkSignature(){
        $data = Wechat :: getData();
        if (empty($data) && Wechat::checkSignature()) {
            $echoStr = request()->get('echostr');
            return $echoStr;
        }
        return false;
    }

    /**
     * 获取微信临时二维码
     * @param string $key               二维码参数
     * @param int $wechatAccount        微信公众号账号主键(WechatAccount表)
     * @param int $expire_seconds   有效时间
     * @return string
     */
    public static function getTempQrCode($key,$wechatAccount,$expire_seconds=2592000){
        if(empty($wechatAccount)) return "";
        //生成临时二维码
        Wechat::$app_id = self::getConfig($wechatAccount,"app_id");
        Wechat::$app_secret =  self::getConfig($wechatAccount,"app_secret");
        return Wechat::getQrcode($key,Wechat::QR_STR_SCENE,$expire_seconds); //返回一个从微信获取二维码图片的url
    }


    /**
     * 获取微信永久二维码
     * @param string $key               二维码参数
     * @param int $wechatAccount        微信公众号账号主键(WechatAccount表)
     * @return string
     */
    public static function getQrCode($key,$wechatAccount){
        if(empty($wechatAccount)) return "";
        //生成永久二维码
        Wechat::$app_id = self::getConfig($wechatAccount,"app_id");
        Wechat::$app_secret =  self::getConfig($wechatAccount,"app_secret");
        return Wechat::getQrcode($key,Wechat::QR_LIMIT_STR_SCENE); //返回一个从微信获取二维码图片的url
    }

    /**
     * 微信推送模板消息
     * @param int $wechatUserId
     * @param string $templateId           模板id
     * @param array $template       模板数据
     * @param string $url           跳转url
     * @return bool|string
     * @throws \think\Exception
     */
    public static function sendTemplate($wechatUserId,$templateId,$template=[],$url=''){
       $weChatUser = self::getWechatUserModel()->where("id",$wechatUserId)->find();
        if($weChatUser instanceof Model){
            $weChatUser = $weChatUser->toArray();
        }
        if(empty($weChatUser)){
            return false;
        }
        Wechat::init(WechatSupport::getConfig($weChatUser['wechat_account_id']));
        Wechat::$userOpenId = $weChatUser['openid'];
        return Wechat::sendTemplate($templateId, $template, $url);
    }


    /**
     * 获取微信端openId;
     * @param int $wechatAccountId          微信账号id
     * @param array $data                     处理数据结果      获取code  ->跳转url  ->获取openid
     * @return bool
     */
    public static function getWeChatOpenId($wechatAccountId,&$data)
    {
        $weChatConfig = self::getConfig($wechatAccountId);
        Wechat::$app_id = $weChatConfig['app_id'];
        Wechat::$app_secret = $weChatConfig['app_secret'];
        if (!input('code',null)) {                                                            //没有code取code
            $callback = request()->url(true);                                               //微信回调地址
            $callback = urlencode($callback);
            $data['url'] = Wechat::createOauthUrlForCode('snsapi_base', $callback);
            return true;
        } else {                                                                            //有code取openid
            $codeUrl = Wechat::createOauthUrlForOpenid(input('code'));
            $result = \extend\Curl::get($codeUrl);
            $result = json_decode($result, true);
            if (!empty($result['openid'])) {
                $data['openid'] = $result['openid'];
                session('openid', $result['openid']);
                return true;
            }
        }
        return false;
    }

}