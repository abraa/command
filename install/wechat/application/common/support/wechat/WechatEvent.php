<?php
/**
 * ====================================
 * 登录逻辑
 * ====================================
 * Author: 1002571
 * Date: 2017-11-08 16:55
 * ====================================
 * Project: ggzy
 * File: WechatEvent.php
 * ====================================
 */

namespace app\common\support\wechat;

use extend\Wechat;



class WechatEvent
{

    static function test(){
        return 1111;
    }
    /**
     * 微信关注事件
     * @return mixed
     */
    public static function subscribe(){
        $res = Subscribe::run();
        if(is_string($res)){
            return $res;
        }
        $eventKey = Wechat::getData('EventKey');
        /* ========判断是否通过扫描带参数二维码关注======== */
        $eventKey = explode("qrscene_", $eventKey);
        if(isset($eventKey[1])){                                    //通过扫描带参数二维码关注
            $res = static::scan();                                   //通过扫描带参数二维码进来的执行扫描带参数二维码事件
            if(is_string($res)){
                return $res;
            }
        }
        //TODO...
        return true;
    }

    /**
     *  取消关注事件
     * @return mixed
     */
    public static function unsubscribe(){
        $openid =   Wechat::getData('FromUserName');
        /* =======取消关注更新update_time 和cancel_time====== */

        WechatSupport::getWechatUserModel()->updateUser(['subscribe'=>0],['openid'=>$openid , 'wechat_account_id'=>WechatSupport::getWechatAccount()]);
        return true;
    }

    /**
     *  扫描带参数二维码事件
     */
    public static function scan(){
        return Scan::run();
    }







}