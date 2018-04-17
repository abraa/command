<?php
/**
 * ====================================
 * 微信公众号 - 事件处理
 * ====================================
 * Author: 9009123 (Lemonice)
 * Date: 2017-09-01 13:44
 * ====================================
 * File: Event.class.php
 * ====================================
 */
namespace Common\Logic\Wechat;
use Common\Extend\Wechat;
use Common\Logic\Wechat\WechatData;

class Event extends WechatData{
    /**
     * 微信数据
     * @var array
     */
    protected $data = array();
    public function __construct() {
        parent::__construct();
    }

    /**
     * 关注
     */
    public function subscribe(){
        $data = $this->getDatas();
        $Subscribe = new \Common\Logic\Wechat\Subscribe();
        $Subscribe->setDatas($data);
		$Subscribe->setTexts($this->getTexts());

        $this->user_activity_log(USER_ACT_SUBSCRIBE);        //记录最后活动时间

        if (isset($data['EventKey']) && !empty($data['EventKey'])) {  //带参数二维码关注
            $Subscribe->eventKey();
        } else {  //普通关注
            $Subscribe->general();
        }
    }

    /**
     * 取消关注
     */
    public function unsubscribe(){
        $data = $this->getDatas();
        $Subscribe = new \Common\Logic\Wechat\Subscribe();
        $Subscribe->setDatas($data);
		$Subscribe->setTexts($this->getTexts());

        $this->user_activity_log(USER_ACT_UNSUBSCRIBE);        //记录最后活动时间

        $Subscribe->unSubscribe($data);
    }

    /**
     * 带参数二维码
     */
    public function scan(){
        $Subscribe = new \Common\Logic\Wechat\Subscribe();
        $Subscribe->setDatas($this->getDatas());
		$Subscribe->setTexts($this->getTexts());
        $Subscribe->eventKey();
    }

    /**
     * 点击菜单事件
     */
    public function click(){
        $data = $this->getDatas();
        $this->user_activity_log(USER_ACT_MENU);        //记录最后活动时间

        $Click = new \Common\Logic\Wechat\Click();
        $Click->setDatas($this->getDatas());
		$Click->setTexts($this->getTexts());
        $EventKey = strtolower($this->getData('EventKey'));
        if(method_exists($Click, $EventKey)){
            $Click->setDatas($this->getDatas());
            $Click->$EventKey();
        }else{  //找不到匹配的点击事件，进入关键字匹配
            $text_content = \Common\Extend\WechatKeyword::filterKeyword($data['EventKey'], USER_ACT_MENU);
            if ($text_content !== false) {
                echo Wechat::textTpl($text_content);  //回复文本给微信
                exit;
            }
        }
    }
}