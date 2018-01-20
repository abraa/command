<?php
 /**
 * ====================================
 * thinkphp5
 * ====================================
 * Author: 1002571
 * Date: 2018/1/18 18:00
 * ====================================
 * File: Subscribe.php
 * ====================================
 */

namespace app\common\support\wechat;

use extend\Wechat;
use app\common\support\WechatSupport;

class Subscribe {


    public static function run(){
        $openid =  Wechat::getData('FromUserName');
        /* ======关注就添加到wechat_user表========== */
        static::subscribeUser($openid);
    }

    /**
     * 用户关注更新微信用户表WechatUser
     * @param string $openid
     */
    protected static function subscribeUser($openid = ""){
        if(empty($openid)){
            $openid = Wechat::getData("FromUserName");
        }
        $wechatAccount = WechatSupport::getWechatAccount();
        /* ==========首次关注新增记录，再次关注更新记录========= */
        $wechatUserInfo =  WechatSupport::getWechatUserModel()->get(["openid"=>$openid,'wechat_account_id'=>$wechatAccount]);    //获取微信用户信息
        $userinfo = Wechat::getUserInfo($openid);
        if(empty($userinfo)){                                                                //取不到用户信息就只保存openid
            $userinfo['openid'] = $openid;
            $userinfo['subscribe'] = 1;
        }
        $userinfo['wechat_account_id'] = WechatSupport::getWechatAccount();                 //加上当前公众号账号
        if(is_null($wechatUserInfo)){                                                      //没有记录则添加
            //添加微信信息
            WechatSupport::getWechatUserModel()->create($userinfo,true);
        }else{
            WechatSupport::getWechatUserModel()->updateUser($userinfo,["openid"=>$openid,'wechat_account_id'=>$wechatAccount]);
        }
    }
}