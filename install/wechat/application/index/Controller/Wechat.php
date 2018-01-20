<?php
/**
 * Created by PhpStorm.
 * User: 1002571
 * Date: 2017/11/8
 * Time: 15:40
 */
namespace app\index\controller;

use app\common\support\wechat\WechatEvent;
use app\common\support\WechatSupport;
use extend\Curl;
use think\Controller;

class Wechat extends Controller
{
    //openid : oFJj4s0X7xmDWGrYSxIPS0wdAOkY
// secid: 8fbe26d3aef4874eb8e0560ba104961c
    /* 接收微信推送过来的信息 */
    public function index()
    {
        $wechatAccount = input("accountid");         //根据传入的wechat_account判断当前公众号是User还是Doctor(WechatAccount表)
        if(!empty($wechatAccount)){
            \extend\Wechat::init(WechatSupport::getConfig($wechatAccount));
        }
        $str = "===========START================\n";
        $str .= "开始时间：".date('Y-m-d H:i:s',time())."\n";
        $str .= "访问IP：".$this->request->ip()."\n";
        $str .= "账户类型：".$wechatAccount."\n";
        if(isset($GLOBALS['HTTP_RAW_POST_DATA'])){
            $str .= "GLOBALS方式：".$GLOBALS['HTTP_RAW_POST_DATA']."\n";
        }
        $str .= "INPUT方式：".file_get_contents("php://input")."\n";
        $str .= "DATA：".var_export(\extend\Wechat::getData(),true)."\n";


        /* ======验证微信公众号接入=====  */
        $result = WechatSupport::checkSignature();
        if(false !== $result){
            echo $result;             //直接输出
            exit;
        }
        try{
            /*==== 保存操作记录=====**/
//            register_shutdown_function(array(logic('WeChat'), 'activityRecord'),$wechatAccount,\extend\Wechat::getData());        //脚本结束后执行
        }catch (\Exception $e){
            //不管操作记录是否出错都忽略,不影响后面代码执行
            @file_put_contents(LOG_PATH.date("Ym").DS.date("d").'wechat.log',$e->getMessage()."\n",FILE_APPEND);
            @file_put_contents(LOG_PATH.date("Ym").DS.date("d").'wechat.log',var_export(\extend\Wechat::getData(),true)."\n",FILE_APPEND);
        }
        $MsgType = \extend\Wechat::getData("MsgType");
        switch($MsgType){
            /* =======接收事件推送 Event事件在wechatEvent中处理====== */
            case 'event':
                $event = \extend\Wechat::getData("Event");
                $event = strtolower($event);            //转小写
                if(method_exists(WechatEvent::class,$event)){
                    $result =  call_user_func([WechatEvent::class, $event]);
                    if(is_string($result)){
                        $str .= "SEND：".$result."\n";
                        echo $result;
                        $str .= "结束时间：".date('Y-m-d H:i:s',time())."\n";
                        $str .= "==========END===================\n\n";
                        file_put_contents(LOG_PATH.date('Y-m-d')."_wechat.txt",$str,FILE_APPEND);
                        exit;
                    }
                    $str .= "SEND：".var_export($result,true)."\n";
                    $str .= "结束时间：".date('Y-m-d H:i:s',time())."\n";
                    $str .= "==========END===================\n\n";
                    file_put_contents(LOG_PATH.date('Y-m-d')."_wechat.txt",$str,FILE_APPEND);
                }
                break;
            default:
        }
        echo '';
        exit;  //回复文本给微信
    }



    /**
     *     显示临时二维码          ---用户|医生推荐
     */
    public function showTempQrCode(){

        //二维码参数是 账号类型_账号id          ---用户|医生推荐
        $userId = $doctorId = 0;
        //1. 如果有doctor_id就是医生推荐
        $doctorId  = input("doctor_id",0,'trim');
            //2.没有doctor_id根据登录账户生成

        $qrcode = "";
        //医生使用永久二维码
        if(!empty($doctorId)){
            $key = self::DOCTOR_ACCOUNT.WechatSupport::SCAN_BIND_USER_DS.$doctorId;           //使用不同连接符区分事件
            //检查本地是否存在二维码
            $filename = PUBLIC_PATH."uploads/qrcode/".$key.".jpg";
            if(!is_file($filename)){                                                        //不存在则生成
                // 生成永久二维码 保存到本地
                $qrcodeUrl = WechatSupport::getQrCode($key,self::USER_ACCOUNT);
                $qrcode  = Curl::get($qrcodeUrl); //获取二维码二进制流
                writeFile($filename,$qrcode);
            }
            $qrcode = file_get_contents($filename);
        }
        //用户使用临时二维码
        else if(!empty($userId)){
            $key = self::USER_ACCOUNT.WechatSupport::SCAN_BIND_USER_DS.$userId;           //使用不同连接符区分事件
            //临时二维码必然是用户公众号配置
            $qrcodeUrl = WechatSupport::getTempQrCode($key,self::USER_ACCOUNT);
            $qrcode  = Curl::get($qrcodeUrl); //获取二维码二进制流
        }
        header( "Content-type: image/jpeg");
        echo $qrcode;
        exit;
//        $this->success('Success', $qrcodeUrl);
    }


    /**
     *     显示永久二维码       --业务员推荐医生
     */
    public function showQrCode(){
        $qrcodeUrl = "";
        /*======  业务员推荐医生二维码 =======*/
        $saleId  = input("sale_id",0,'trim');            //二维码参数是 账号类型_账号id
        //检查业务员是否存在
        if(isset($saleId) && logic('sales')->checkIsExist($saleId)){
            $key = WechatSupport::SCAN_SALES_DS.$saleId;           //使用不同连接符区分事件
            \extend\Wechat::init(WechatSupport::getConfig(self::DOCTOR_ACCOUNT));           //业务员推荐医生是医生公众号配置
            $qrcodeUrl = WechatSupport::getQrCode($key,self::DOCTOR_ACCOUNT);
        }
        if(empty($qrcodeUrl)){
            return "";
        }
        $qrcode  = Curl::get($qrcodeUrl); //获取二维码二进制流
        header( "Content-type: image/jpeg");
        echo $qrcode;
        exit;
//        $this->success('Success', $qrcodeUrl);
    }

    public function getOpenid(){
        $wechatAccount = input("accountid");         //根据传入的wechat_account判断当前公众号是User还是Doctor(WechatAccount表)
        $data = [];
        if(WechatSupport::getWeChatOpenId($wechatAccount,$data)){
            if(isset($data['url'])){
                $this->redirect($data['url']);
            }else{
                $this->success('Success',null,$data);;
            }
        }
        $this->error('error');
    }
    

}
