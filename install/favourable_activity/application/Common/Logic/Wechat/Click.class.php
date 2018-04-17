<?php
/**
 * ====================================
 * 微信公众号 - 点击菜单事件处理
 * 注意：****此类的方法名必须全部小写****
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

class Click extends WechatData{
    /**
     * 提示订单物流查询方式
     */
    public function getgift(){
        Wechat::serviceText("亲，点击链接即可免费领取礼品哦！\nhttp://q.chinaskin.cn/#wx_flow?openid=".$this->getData('FromUserName'));
    }

    /**
     * 与我交朋友
     */
    public function autoreply(){
        Wechat::serviceText("想跟我聊天吐槽、学护肤化妆的美女，在公众号留言回复关键词【学变美】，或者留下你的微信号，看到泥萌的消息后，我会马上加你哈~加过菲爷的不用再加啦，直接在微信找我聊天吧");
    }

    /**
     * 每周活动 -> 本期活动
     */
    public function actWeekly(){
        Wechat::serviceText("【见证最美的你】发送变美前后2张照片，免费送你防晒礼盒！还包邮！<a href='http://mp.weixin.qq.com/s/5mkmtYfkwm_wGMji1J0MHQ'>活动详情</a>\n");
    }

    /**
     * 提示订单物流查询方式
     */
    public function orderinquiry(){
        $Logistics = new \Common\Logic\Wechat\LogisticsObj();
        $Logistics->setDatas($this->getDatas());
		$Logistics->setTexts($this->getTexts());
        $Logistics->help();
    }

    /**
     * 提示订单物流查询方式
     */
    public function querylogistics(){
        $this->orderinquiry();
    }

    /**
     * 提示如何查询防伪码信息
     */
    public function fwcheck(){
        $Fwcheck = new \Common\Logic\Wechat\Fwcheck();
        $Fwcheck->setDatas($this->getDatas());
		$Fwcheck->setTexts($this->getTexts());
        $Fwcheck->help();
    }

    /**
     * 进入微信多客服系统 - 客服接入
     */
    public function customerservice(){
        $Customer = new \Common\Logic\Wechat\Customer();
        $Customer->setDatas($this->getDatas());
		$Customer->setTexts($this->getTexts());
        $Customer->goToService();
    }

    /**
     * 签到
     */
    public function signin(){
        $data = $this->getDatas();
        $signinMsg = D('BindUser')->SignIn($data);
        if (empty($signinMsg)) {
            Wechat::serviceText('签到人数过多，签到失败，如再次签到失败请联系客服');
        } else {
            Wechat::serviceText($signinMsg);
        }
    }

    /**
     * 会员权益
     */
    public function benefits(){
        $domain_source = C('DOMAIN_SOURCE');
        $tpl = array(
            'title'         => '韩国瓷肌会员权益',
            'description'   => '赶紧来看看，会员有哪些特权？',
            'url'           => 'http://mp.weixin.qq.com/s/Uul4kR566Uy3MscxwmnPzg',
            'picurl'        => $domain_source['img_domain'] . '/pic/weChatImg/benefits.jpg',
        );
        echo Wechat::newsTpl($tpl);
        exit;
    }

    /**
     * 积分兑换
     */
    public function redeem(){
        $domain_source = C('DOMAIN_SOURCE');
        $tpl = array(
            'title'         => '韩国瓷肌积分换好礼',
            'description'   => '韩国瓷肌会员积分兑换产品明细，不定期更新。会员赶紧来看看！',
            'url'           => 'http://mp.weixin.qq.com/s/sHjKvdn5zO5xs3iF3iXfYw',
            'picurl'        => $domain_source['img_domain'] . '/pic/weChatImg/redeem.jpg',
        );
        echo Wechat::newsTpl($tpl);
        exit;
    }

    /**
     * 门店地址
     */
    public function store(){
        $domain_source = C('DOMAIN_SOURCE');
        $tpl = array(
            'title'         => '韩国瓷肌，定格你的美',
            'description'   => '韩国瓷肌中韩线下医美整形门店查询，欢迎来电咨询。',
            'url'           => 'http://mp.weixin.qq.com/s/YnAuzBliq82YI1-zr81Dgw',
            'picurl'        => $domain_source['img_domain'] . '/pic/weChatImg/store.png',
        );
        echo Wechat::newsTpl($tpl);
        exit;
    }

    /**
     * 医师坐诊
     */
    public function physician(){
        $domain_source = C('DOMAIN_SOURCE');
        $tpl = array(
            'title'         => '皮肤科医师在线坐诊，等你来‘战’',
            'description'   => '韩国瓷肌皮肤科医师团队每周定时微信在线坐诊，解决你的肌肤问题。',
            'url'           => 'http://mp.weixin.qq.com/s/kdF6Akr3ISWbKA3fNYCpfg',
            'picurl'        => $domain_source['img_domain'] . '/pic/weChatImg/physician.png',
        );
        echo Wechat::newsTpl($tpl);
        exit;
    }
}