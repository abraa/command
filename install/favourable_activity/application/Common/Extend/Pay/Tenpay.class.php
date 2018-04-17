<?php
/**
 * ====================================
 * 财付通 API接口 - 第三方平台
 * ====================================
 * Author: 9009123 (Lemonice)
 * Date: 2017-10-14 10:14
 * ====================================
 * File: Tenpay.class.php
 * ====================================
 */
namespace Common\Extend\Pay;

class Tenpay extends Pay{
    public function __construct(){
        parent::__construct();
        $this->pay = new \Common\Extend\Pay\Tenpay\Tenpay();
        $this->pay->setAccount(TENPAY_ACCOUNT);
        $this->pay->setSecret(TENPAY_SECRET);
    }

    /**
     * 生成支付HTML代码
     */
    public function getCode(){
        $params = $this->getDatas();
        $bankId = !isset($params['bank_id'])||empty($params['bank_id']) ? "DEFAULT" : $params['bank_id'];
        $body = isset($params['body']) ? $params['body'] : $params['order_sn'];
        $orderAmount = floatval($params['order_amount']) * 100;    //字符金额 以 分为单位 比如 10 元， 应写成 1000

        //----------------------------------------
        //设置支付参数
        //----------------------------------------
        $this->pay->setData("partner", $this->pay->getAccount());
        $this->pay->setData("out_trade_no", $params['order_sn']);
        $this->pay->setData("total_fee", $orderAmount);  //总金额
        $this->pay->setData("return_url", $this->getRespondUrl());
        $this->pay->setData("notify_url", $this->getNotifyUrl());
        $this->pay->setData("body", $body);
        $this->pay->setData("bank_type", $bankId);  	  //银行类型，默认为财付通
        //用户ip
        $this->pay->setData("spbill_create_ip", get_client_ip());//客户端IP
        $this->pay->setData("fee_type", "1");               //币种
        $this->pay->setData("subject",$params['order_sn']);          //商品名称，（中介交易时必填）

        //系统可选参数
        $this->pay->setData("sign_type", "MD5");  	 	  //签名方式，默认为MD5，可选RSA
        $this->pay->setData("service_version", "1.0"); 	  //接口版本号
        $this->pay->setData("input_charset", "utf-8");   	  //字符集
        $this->pay->setData("sign_key_index", "1");    	  //密钥序号

        //业务可选参数
        $this->pay->setData("attach", "");             	  //附件数据，原样返回就可以了
        $this->pay->setData("product_fee", "");        	  //商品费用
        $this->pay->setData("transport_fee", "0");      	  //物流费用
//        $this->pay->setData("time_start", date("YmdHis"));  //订单生成时间
//        $this->pay->setData("time_expire", "");             //订单失效时间
        $this->pay->setData("buyer_id", "");                //买方财付通帐号
        $this->pay->setData("goods_tag", "");               //商品标记
        $this->pay->setData("trade_mode",1);              //交易模式（1.即时到帐模式，2.中介担保模式，3.后台选择（卖家进入支付中心列表选择））
        $this->pay->setData("transport_desc","");              //物流说明
        $this->pay->setData("trans_type","1");              //交易类型
        $this->pay->setData("agentid","");                  //平台ID
        $this->pay->setData("agent_type","");               //代理模式（0.无代理，1.表示卡易售模式，2.表示网店模式）
        $this->pay->setData("seller_id","");                //卖家的商户号

        $sign = $this->pay->makeSign();                   //签名
        $params = $this->pay->getSignData();
        $params['sign'] = $sign;

        $url = $this->pay->getGateWay();

        $button  = '<form method="GET" action="'.$url.'">';
        foreach ($params as $key=>$val){
            $button  .= '<input type="hidden" name="'.$key.'" value="'.$val.'" />';
        }
        $button  .= '<input id="tenpay_button" type="submit" value="财付通支付"></form>';
        return $button;
    }
}