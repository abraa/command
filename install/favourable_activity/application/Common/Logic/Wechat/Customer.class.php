<?php
/**
 * ====================================
 * 微信公众号 - 多客服
 * ====================================
 * Author: 9009123 (Lemonice)
 * Date: 2017-09-01 15:57
 * ====================================
 * File: Customer.class.php
 * ====================================
 */
namespace Common\Logic\Wechat;
use Common\Extend\Wechat;
use Common\Logic\Wechat\WechatData;

class Customer extends WechatData{
    private $notice = '您好，感谢您的咨询，现在人工客服不在线。有问题请拨打瓷肌售后热线：02022005555。测试阶段：人工服务时间14：00-18：30。带来不便，请多体谅。';

    /**
     * 进入多客服系统 - 客服接入
     */
    public function goToService() {
        $data = $this->getDatas();
        $openid = $data['FromUserName'];
        $mobile = D('BindUser')->where("openid = '$openid'")->getField('mobile');

        if (!$mobile) {
            $content = "您好，请先绑定您的手机号码\n<a href='" . siteUrl() . "#/check-code'>（手机认证）</a>";
            Wechat::serviceText($content);
            exit;
        }
        $list = Wechat::getAllService();  //获取所有客服资料
        $service_is_online = false;  //是否有客服在线
        if (isset($list['kf_online_list']) && is_array($list['kf_online_list']) && !empty($list['kf_online_list'])) {
            foreach ($list['kf_online_list'] as $value) {
                if (isset($value['status']) && $value['status'] == 1) {  //在线
                    $service_is_online = true;
                    break;
                }
            }
        }
        if ($service_is_online == true) {  //有客服在线
            echo Wechat::customerServiceTpl();
        } else {  //没有客服在线
            Wechat::serviceText($this->notice);
        }
        exit;
    }
}