<?php
/**
 * ====================================
 * 微信公众号 - 点击菜单事件处理
 * ====================================
 * Author: 9009123 (Lemonice)
 * Date: 2017-09-01 15:37
 * ====================================
 * File: Click.class.php
 * ====================================
 */
namespace Common\Logic\Wechat;
use Common\Extend\Wechat;
use Common\Logic\Wechat\WechatData;

class Text extends WechatData{
    /**
     * 自动回复的内容
     * @var null
     */
    private $autoReplyText = NULL;
    /**
     * 文本信息统一入口
     */
    public function index(){
        $data = $this->getDatas();
        $content = trim($data['Content']);

        $Fwcheck = new \Common\Logic\Wechat\Fwcheck();
        $Fwcheck->setDatas($this->getDatas());
        $Menu = new \Common\Logic\Wechat\Menu();
        $Menu->setDatas($this->getDatas());
		$Menu->setTexts($this->getTexts());

        if (isMobile($content)) {
            $Menu->show();
        } elseif ($Fwcheck->isFwcode()) {
            $Fwcheck->check();
        } elseif (preg_match('/^快递单号[：:]+([a-zA-Z0-9]+)$/', $content, $match)) {
            $Logistics = new \Common\Logic\Wechat\LogisticsObj();
            $Logistics->setDatas($this->getDatas());
			$Logistics->setTexts($this->getTexts());
            $Logistics->send($match[1]);
        }elseif ($Menu->isMenu()) {
            $Menu->execMenu();
        } //签到
        elseif (strstr($content, '签到')) {
            $this->signIn();
        } else {  //其他所有未识别的，找关键字回复表，如果没有则接入多客服
            //查找关键字
            $this->keyWord();
            //自动回复文字
            if($this->checkTime() && !is_null($this->autoReplyText)){
                echo Wechat::textTpl($this->autoReplyText);
                exit;
            }
            //接入多客服
            $Customer = new \Common\Logic\Wechat\Customer();
            $Customer->setDatas($data);
			$Customer->setTexts($this->getTexts());
            $Customer->goToService();
        }
    }

    /**
     * 关键字匹配
     */
    private function keyWord(){
        $content = trim($this->getData('Content'));
        $text_content = \Common\Extend\WechatKeyword::filterKeyword($content, USER_ACT_REPLY);
        if ($text_content !== false) {
            echo Wechat::textTpl($text_content);  //回复文本给微信
            exit;
        }
    }

    /**
     * 签到操作
     */
    private function signIn(){
        $data = $this->getDatas();
        $signinMsg = D('BindUser')->SignIn($data);
        if (empty($signinMsg)) {
            Wechat::serviceText('签到人数过多，签到失败，如再次签到失败请联系客服');
        } else {
            Wechat::serviceText($signinMsg);
        }
    }

    /**
     * 判断当前时间是否在自动回复的时间段
     * @return bool
     */
    private function checkTime(){
        $dbModel = new \Cpanel\Model\ConfigModel();
        $data = $dbModel->where(array('name'=>'WECHAT_AUTO_REPLAY'))->getField('value');
        if(empty($data)){
            return false;
        }
        $account_id = $this->getText('account_id');
        $data = unserialize($data);
        if(!isset($data[$account_id]) || $data[$account_id]['locked'] == 1){
            return false;
        }
        $data = $data[$account_id];

        $start = strtotime(date('Y-m-d').$data['effect_start_time']);
        $end = strtotime(date('Y-m-d').$data['effect_end_time']);
        $time = time();

        $result = false;

        //判断时间段
        if($start > $end && ($time < $end ||  $time > $start)){  //时间段跨度到明天
            $result = true;
        }else if($time >= $start && $time <= $end){  //时间段在当天一天内
            $result = true;
        }
        if($result === true){
            $this->autoReplyText = !empty($data['content']) ? $data['content'] : NULL;
        }
        return $result;
    }
}