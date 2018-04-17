<?php
/**
 * ====================================
 * 微信公众号 - 语音信息处理
 * ====================================
 * Author: 9009123 (Lemonice)
 * Date: 2017-09-01 17:4
 * ====================================
 * File: Voice.class.php
 * ====================================
 */
namespace Common\Logic\Wechat;
use Common\Extend\Wechat;
use Common\Logic\Wechat\WechatData;

class Voice extends WechatData{
    /**
     * 关键字匹配
     * @var array
     */
    private $keywordData = array(
        'logistics'=>array('物流','快递','发货','收货'),  //物流相关关键字
        'customer'=>array('客服','服务'),  //接入多客服相关关键字
        'fwcheck'=>array('防伪','伪造','真假'),  //防伪码相关关键字
        'protect'=>array('投诉','不满意','没效果'),  //投诉建议相关关键字
    );

    /**
     * 语音信息统一入口
     */
    public function index(){
        $data = $this->getDatas();
        if (empty($data) || empty($data['FromUserName']) || empty($data['Recognition'])) {
            return false;
        }
        $content = isset($data['Recognition']) ? $data['Recognition'] : '';  //微信自动解析好的语音文字
        if(empty($content)){
            return false;
        }
        $function = $this->checkKeyWord($content);
        if(!empty($function)){
            $this->$function();
        }else{
            Wechat::serviceText('小瓷正在努力学习，会越来越智能哦，可以说我要客服，主动联系客服服务！');
        }
    }

    /**
     * 匹配关键字，找到执行函数
     */
    private function checkKeyWord($content){
        if(!empty($this->keywordData)){
            foreach($this->keywordData as $function=>$keyword){
                if(!method_exists($this, $function)){
                    continue;
                }
                foreach($keyword as $kw){
                    if (strstr($content, $kw) !== false) {
                        return $function;
                    }
                }
            }
        }
        return false;
    }
/* ====================================================================关键字对应的执行函数原型 - Start================================================================================ */

    /**
     * 物流
     */
    private function logistics(){
        $Logistics = new \Common\Logic\Wechat\LogisticsObj();
        $Logistics->setDatas($this->getDatas());
		$Logistics->setTexts($this->getTexts());
        $Logistics->help();
    }

    /**
     * 接入多客服
     */
    private function customer(){
        $Customer = new \Common\Logic\Wechat\Customer();
        $Customer->setDatas($this->getDatas());
		$Customer->setTexts($this->getTexts());
        $Customer->goToService();
    }

    /**
     * 防伪码
     */
    private function fwcheck(){
        $Fwcheck = new \Common\Logic\Wechat\Fwcheck();
        $Fwcheck->setDatas($this->getDatas());
		$Fwcheck->setTexts($this->getTexts());
        $Fwcheck->help();
    }

    /**
     * 投诉建议
     */
    private function protect(){
        Wechat::serviceText("亲爱的顾客，您好，如要发起投诉，点以下链接，我们客服人员将尽快与您联系！\n<a href='http://q.chinaskin.cn/#/protect_right'>我要投诉</a>");
    }
/* ====================================================================关键字对应的执行函数原型 - End================================================================================ */
}