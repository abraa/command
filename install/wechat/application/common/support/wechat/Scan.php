<?php
 /**
 * ====================================
 * ggzy
 * ====================================
 * Author: 1002571
 * Date: 2017/12/19 9:30
 * ====================================
 * File: Scan.php
 * ====================================
 */

namespace app\common\support\wechat;

use extend\Wechat;
use app\common\support\WechatSupport;


class Scan {


    /**
     * Scan 事件入口
     * @return bool|string
     */
    public static function run(){
        //获取openid 和 EventKey
        $eventKey =  Wechat::getData('EventKey');
        //判断是否通过扫描带参数二维码关注
        $eventKey = explode("qrscene_", $eventKey);
        $scene_id = isset($eventKey[1])?$eventKey[1]:$eventKey[0];
        if(empty($scene_id)){return false;}                     //空的参数直接结束
        /*=====绑定用户关系==========*/
//        if(false !== strpos($scene_id , WechatSupport::SCAN_BIND_USER_DS)){
//            return $this->bindUser($scene_id);
//        }
//        /*=====医生推荐药方==========*/
//        elseif(false !== strpos($scene_id , WechatSupport::SCAN_PRESCRIPTION_DS)){
//            return $this->presOrder($scene_id);
//        }
//        /*=====业务员推荐医生==========*/
//        elseif(false !== strpos($scene_id , WechatSupport::SCAN_SALES_DS)){
//            return $this->saleRecommend($scene_id);
//        }
        return true;
    }


    /**
     * 扫码绑定用户处理
     * @param $scene_id
     * @return string
     */
    public function bindUser($scene_id){
        $openid =   Wechat::getData('FromUserName');
        $userinfo = Wechat::getUserInfo($openid);
        $userName = isset($userinfo['nickname']) ? $userinfo['nickname'] : '用户';
        $role = explode(WechatSupport::SCAN_BIND_USER_DS,$scene_id);
        $userinfo['account_type'] = isset($role[0]) ? $role[0] : '0';
        $userinfo['parent_id'] = isset($role[1]) ? $role[1] : '0';
        /*---- 推送用户关注信息 ----*/
        if(WechatUser::ACCOUNT_TYPE_DOCTOR == $userinfo['account_type']){                                //扫医生二维码执行
            /*---- 用户关注医生 ----*/
            $this->focusUser($userinfo['parent_id']);
            //取医生信息
            $doctorName = $this->setModel('doctor')->where('doctor_id',$userinfo['parent_id']) ->value('real_name');
            $doctorName = empty($doctorName) ? "" : $doctorName;
            $url =getDomain().'html/patient/docter-card.html?doctor_id='.$userinfo['parent_id'];
            $textContent = "尊敬的".$userName.", 欢迎关注极米健康。点击以下链接关注您的推荐医生“<a href='".$url."'>".$doctorName."医生详情</a>”，方便您随时咨询医生。";
            return Wechat::textTpl($textContent);  //回复文本给微信
        }else{                                                                                           //扫用户二维码执行
            $textContent = " 欢迎关注极米健康。";
            return Wechat::textTpl($textContent);  //回复文本给微信
        }
    }

    /**
     * 医生推荐药方
     * @param $scene_id
     * @return string
     */
    public function presOrder($scene_id){
        $openid =   Wechat::getData('FromUserName');
        $key = explode(WechatSupport::SCAN_PRESCRIPTION_DS,$scene_id);
        $key = isset($key[1]) ? $key[1] : $key[0];                                  //$key[0]是空字符串   --参数格式 _pres_XXXXXXXXXXXX
        //取医生信息
        $where[] = "MD5(id) = '".strtolower($key)."'";
        $doctorId = $this->setModel('order')->where(implode(' AND ',$where))->value('doctor_id');
        $doctorName = $this->setModel('doctor')->where('doctor_id',$doctorId) ->value('real_name');
        $doctorName = empty($doctorName) ? "" : $doctorName;
        $userinfo = Wechat::getUserInfo($openid);
        $userName = isset($userinfo['nickname']) ? $userinfo['nickname'] : '用户';
        //推送微信端一个链接
        $url =getDomain().'html/patient/prescription-sure.html?order_key='.$key;
        $textContent = "尊敬的".$userName.", ".$doctorName."医生给您精心准备的药方已开好，请点击查看药方详情并付款“<a href='".$url."'>药方链接</a>”。祝您早日康复！";
        /*---------- 用户关注医生 ---------------*/
        $this->focusUser($doctorId);
        return Wechat::textTpl($textContent);  //回复文本给微信
    }


    /**
     * 业务员推荐医生
     * @param $scene_id
     * @return bool
     */
    public function saleRecommend($scene_id){
        $openid =   Wechat::getData('FromUserName');
        $key = explode(WechatSupport::SCAN_SALES_DS,$scene_id);
        $role = isset($key[1]) ? $key[1] : $key[0];                                  //$key[0]是空字符串   --参数格式 _sale_XXXXXXXXXXXX
        // 绑定医生openid和业务员id
        if(!empty($role)){         //存在才执行    sales_id
            WechatSupport::getWechatUserModel()->updateUser(['account_type'=>WechatUser::ACCOUNT_TYPE_SALES,'parent_id'=>$role],['openid'=>$openid,'wechat_account_id'=>WechatSupport::getWechatAccount()],true);
        }
        return  true;
    }


    /**
     * 添加到用户关注医生
     * @param $doctor_id
     * @return bool
     */
    public function focusUser($doctor_id){
        $openid =   Wechat::getData('FromUserName');
        $res = $this->setModel('user')->alias('user')->field('user.user_id')->view('wechatUser','openid,wechat_account_id','wechatUser.id=user.wechat_user_id')->where(['openid'=>$openid,'wechat_account_id'=>WechatAccount::USER])->find();

        if(!is_null($res)){
            $userId = isset($res->user_id) ? $res->user_id : 0;
            $userAttent = $this->setModel('userAttention')->where(['user_id'=>$userId,'doctor_id'=>$doctor_id])->find();
            $status = isset($userAttent->status) ? $userAttent->status : 0;
            if (!empty($userAttent)) {                                                                                                //已有记录修改
                $this->setModel("userAttention")->update(['status' => 1],['user_id'=>$userId,'doctor_id'=>$doctor_id,'update_time'=>time()]);
            } else {                                                                                                       //未有记录添加
               $this->setModel("userAttention")->create(['status' => 1,'user_id'=>$userId,'doctor_id'=>$doctor_id,'update_time'=>time()]);
            }
            //检查当前用户是否已经关注过了 -没有关注推送医生
            if(0 == $status){
                $this->serviceDoctor($doctor_id,$userId);
            }
            return true;
        }
        return false;
    }

    /**
     *  用户关注后主动推送给医生有人关注的信息
     * @param $doctorId
     * @param $userId
     */
    public function serviceDoctor($doctorId,$userId){
        $openid =   Wechat::getData('FromUserName');
        $userinfo = Wechat::getUserInfo($openid);
        $userName = isset($userinfo['nickname']) ? $userinfo['nickname'] : '';
        //推送文本

        //如果当前是用户端配置切换医生端微信公众号配置主动推(模板消息)
        if(WechatSupport::getWechatAccount() == WechatAccount::USER){
            $templateConfig = config('wechatConfig.new_patient_template');
            if(!empty($templateConfig)){
                $template = array(
                    'first'=>array('value'=>$templateConfig['first_text']),
                    'keyword1'=>array('value'=>$userName),                      //患者姓名：{{keyword1.DATA}}
                    'keyword2'=>array('value'=>date('Y-m-d H:i', time())),  // 加入时间：{{keyword2.DATA}}
                    'remark'=>array('value'=>$templateConfig['remark_text']),
                );
                $wechatUserId = $this->setModel('doctor')->where('doctor_id',$doctorId)->value('wechat_user_id');
                $url = getDomain().'html/doctor/patient-info.html?user_id='.$userId;
                WechatSupport::sendTemplate($wechatUserId,$templateConfig['template_id'],$template,$url);             //推送
            }
            //推送完成切换回原配置
            Wechat::init(WechatSupport::getConfig(WechatAccount::USER));
            Wechat::$userOpenId = $openid;
        }
    }


}