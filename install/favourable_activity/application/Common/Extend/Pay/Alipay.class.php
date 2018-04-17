<?php
/**
 * ====================================
 * 支付宝 API接口 - 第三方平台
 * ====================================
 * Author: 9009123 (Lemonice)
 * Date: 2017-10-09 17:47
 * ====================================
 * File: Alipay.class.php
 * ====================================
 */
namespace Common\Extend\Pay;

class Alipay extends Pay{
    public function __construct(){
        parent::__construct();
        $this->pay = new \Common\Extend\Pay\Alipay\Alipay();
        $this->pay->setAppId(ALIPAY_APPID);
        $this->pay->setAccount(ALIPAY_ACCOUNT);
        $this->pay->setKey(ALIPAY_KEY);
        $this->pay->setPartnerId(ALIPAY_PARTNER);
    }

    /**
     * 退款
     */
    public function refund() {
        $out_trade_no = $this->getData('out_trade_no');
        $refund_fee = $this->getData('refund_fee');
        $refund_desc = $this->getData('refund_desc');

        $this->pay->setData('out_trade_no', $out_trade_no);  //商户订单号
        $this->pay->setData('refund_amount', $refund_fee);  //订单金额
        if(!empty($refund_desc)){
            $this->pay->setData('refund_reason', $refund_desc);
        }
        $this->pay->setData('charset', 'utf-8');
        $this->pay->setData('sign_type', 'RSA');
        $this->pay->setData('timestamp', date('Y-m-d H:i:s'));
        $this->pay->setData('version', '1.0');

        //私有接口参数装包biz_content参数
        $privateParamsField = array(
            'out_trade_no',
            'refund_amount',
        );
        if(!empty($refund_desc)){
            $privateParamsField[] = 'refund_reason';
        }
        $this->pay->setBizContent($privateParamsField);

        $this->pay->setMethod('alipay_trade_refund');
        $this->pay->setSign();
        $result = $this->pay->request();

        if($result['code'] != '10000') {
            $msg = (isset($result['sub_msg']) ? $result['sub_msg'] : '') . ' - ' . $result['msg'];
            $this->setError($msg);
            return false;
        }
        return array(
            'transaction_id'=>$result['trade_no'],  //支付宝交易号
            'out_trade_no'=>$result['out_trade_no'],  //商户订单号
            'refund_fee'=>round($result['refund_fee'], 2),  //退款总金额
        );
    }

    /**
     * 查询退款结果
     */
    public function queryRefund() {
        $out_trade_no = $this->getData('out_trade_no');
        $out_request_no = $this->getData('out_refund_no');

        $this->pay->setData('out_trade_no', $out_trade_no);  //商户订单号
        $this->pay->setData('out_request_no', $out_request_no);  //订单金额
        $this->pay->setData('charset', 'utf-8');
        $this->pay->setData('sign_type', 'RSA');
        $this->pay->setData('timestamp', date('Y-m-d H:i:s'));
        $this->pay->setData('version', '1.0');

        //私有接口参数装包biz_content参数
        $privateParamsField = array(
            'out_trade_no',
            'out_request_no',
        );
        $this->pay->setBizContent($privateParamsField);

        $this->pay->setMethod('alipay_trade_fastpay_refund_query');
        $this->pay->setSign();
        $result = $this->pay->request();

        if($result['code'] != '10000') {
            $msg = (isset($result['sub_msg']) ? $result['sub_msg'] : '') . ' - ' . $result['msg'];
            $this->setError($msg);
            return false;
        }else if(!isset($result['out_request_no'])){
            return array();  //未申请退款
        }
        return array(
            'out_refund_no'=>$result['out_request_no'],
            'out_trade_no'=>$result['out_trade_no'],
            'refund_fee'=>$result['refund_amount'],
            'transaction_id'=>$result['trade_no'],
        );
    }

    /**
     * 生成支付HTML代码
     */
    public function getCode(){
        $params = $this->getDatas();
        $body = isset($params['body']) ? $params['body'] : $params['order_sn'];
        $charset = 'utf-8';
        $this->pay->setData('subject', $body);
        $this->pay->setData('out_trade_no', $params['order_sn']);
        $this->pay->setData('total_amount', $params['order_amount']);
        $this->pay->setData('charset', $charset);
        $this->pay->setData('version', '1.0');
        $this->pay->setData('product_code', 'QUICK_WAP_PAY');
        $this->pay->setData('timestamp', date('Y-m-d H:i:s'));
        $this->pay->setData('sign_type', 'RSA');
        $this->pay->setData('return_url', $this->getRespondUrl());
        $this->pay->setData('notify_url', $this->getNotifyUrl());

        //私有接口参数装包biz_content参数
        $privateParamsField = array(
            'subject',
            'out_trade_no',
            'total_amount',
            'product_code',
        );
        $this->pay->setBizContent($privateParamsField);

        $this->pay->setMethod('trade_wap_pay');
        $this->pay->setSign();
        $params = $this->pay->getDatas();

        $button = "<form id='alipaysubmit' action='".$this->pay->getGateWay()."?charset=".$charset."' method='POST'>";
        foreach($params as $key=>$value){
            $value = str_replace("'","&apos;",$value);
            $button .= "<input type='hidden' name='".$key."' value='".$value."'/>";
        }
        $button .= '<input type="submit" value="支付宝支付"></form>';
        return $button;
    }

    /**
     * 面对面支付
     * @return bool|string
     * @throws \Exception
     */
    public function face2Face(){
        $params = $this->getDatas();
        $body = isset($params['body']) ? $params['body'] : $params['order_sn'];
        //公共请求参数
        $this->pay->setData('app_id', $this->pay->getAppId());
        $this->pay->setData('format', 'JSON');
        $this->pay->setData('charset', 'utf-8');
        $this->pay->setData('sign_type', 'RSA');
        $this->pay->setData('timestamp', date('Y-m-d H:i:s'));
        $this->pay->setData('version', '1.0');
        $this->pay->setData('notify_url', $this->getNotifyF2fUrl());

        //请求参数
        $this->pay->setData('out_trade_no', $params['order_sn']);
        $this->pay->setData('total_amount', $params['order_amount']);
        $this->pay->setData('subject', $body);
        //私有接口参数装包biz_content参数
        $privateParamsField = array(
            'out_trade_no',
            'total_amount',
            'subject',
        );
        $this->pay->setBizContent($privateParamsField);
        $this->pay->setMethod('alipay_trade_precreate');
        $this->pay->setSign();
        $result = $this->pay->request();
        if($result === false){
            $error = $this->pay->getError();
            $this->setError($error);
            return false;
        }
        import('Common/Extend/QrCode');
        return \QRcode::png($result['qr_code'],null,'L','5',0);
    }

    /**
     * 面对面支付异步校验
     * @return bool
     */
    public function face2FaceNotify(){
        $params = $this->getDatas();
        $this->pay->setDatas($params);
        return $this->pay->notifyF2f();
    }
}