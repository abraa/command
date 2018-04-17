<?php
/**
 * ====================================
 * 微信支付 API接口 - 第三方平台
 * ====================================
 * Author: 9009123 (Lemonice)
 * Date: 2017-10-09 17:47
 * ====================================
 * File: Wechatpay.class.php
 * ====================================
 */
namespace Common\Extend\Pay;

use Common\Extend\Time;

class Wechatpay extends Pay{
    public function __construct(){
        parent::__construct();
        $this->pay = new \Common\Extend\Pay\Wechatpay\Library\WxPay();
        $this->pay->setAppId(WECHAT_APPID);
        $this->pay->setAppSecret(WECHAT_APPSECRET);
        $this->pay->setMachineId(WECHAT_MACHINE_ID);
        $this->pay->setKey(WECHAT_PAY_KEY);
    }

    /**
     * 生成支付HTML代码
     */
    public function getCode(){
        $params = $this->getDatas();
        session('wechatpay_params', $params);
        $button = '<form action="'.siteUrl().'Payment/getOpenId.shtml">';  //此地址是为了先获取openid，然后再操作其他微信的接口
        $button .= "<input type='hidden' name='paycode' value='wechatpay' />";
        $button .= "<input type='hidden' name='paytype' value='".$this->getPayType()."' />";
        $button .= '<input id="wechatpay_button" type="submit" value="微信支付"></form>';
        return $button;
    }

    /**
     * HTML5支付 - 默认支付类型
     */
    public function pay(){
        $prepayId = $this->getPrepayId();  //获取支付ID
        if($prepayId === false){
            return $prepayId;
        }
        $timeStamp = time();
        $params = array(
            'appId'=>$this->pay->getAppId(),
            'timeStamp'=>"$timeStamp",
            'nonceStr'=>$this->pay->getNonceStr(),
            'package'=>"prepay_id=" . $prepayId,
            'signType'=>'MD5',
        );

        $this->pay->setDatas($params);
        $params['paySign'] = $this->pay->makeSign();

        //提供给同步校验用的订单号
        session('wechatpay_respond_data', array(
            'order_sn'=>$this->getData('order_sn'),
            'order_amount'=>$this->getData('order_amount'),
        ));
        $respond_url = $this->getRespondUrl();
        return $this->startToPay($params, $respond_url);
    }

    /**
     * 生成支付二维码
     * @return bool
     */
    public function face2Face(){
        $params = $this->getDatas();  //获取参数
        $body = isset($params['body']) ? $params['body'] : $params['order_sn'];
        $this->pay->setData('body', $body);  //商品描述
        $this->pay->setData('out_trade_no', $params['order_sn']);//订单号
        $this->pay->setData("total_fee", intval($params['order_amount']*100));//总金额
        $this->pay->setData("notify_url", $this->getNotifyF2fUrl());//通知地址
        $this->pay->setData("spbill_create_ip", get_client_ip());
        $this->pay->setData("trade_type", "NATIVE");//交易类型
        $this->pay->setData("appid", $this->pay->getAppId());
        $this->pay->setData("mch_id", $this->pay->getMachineId());

        $result = $this->pay->request('unifiedorder');

        if(isset($result['result_code']) && $result['result_code'] == 'SUCCESS'){
            import('Common/Extend/QrCode');
            return \QRcode::png($result['code_url'],null,'L','5',0);
        }else{
            if(isset($result['return_code']) && $result['return_code'] == 'SUCCESS'){  //通讯成功
                $this->setError($result['err_code']);
                $this->setError($result['err_code_des']);
            }else{
                $error = $this->pay->getError();
                if(!empty($error)){
                    $this->setError($error);
                }
                !isset($result['return_msg']) or $this->setError($result['return_msg']);
            }
            return false;
        }
    }

    /**
     * 面对面支付异步校验
     * @return bool
     */
    public function face2FaceNotify(){
        return $this->notify();
    }

    /**
     * 查询退款结果
     */
    public function queryRefund() {
        $out_trade_no = $this->getData('out_trade_no');
        $out_refund_no = $this->getData('out_refund_no');

        $params = array(
            'appid'=>$this->pay->getAppId(),
            'mch_id'=>$this->pay->getMachineId(),
            'out_trade_no'=>$out_trade_no,  //商户订单号
            'out_refund_no'=>$out_refund_no,  //退款单号
        );
        $this->pay->setDatas($params);
        $result = $this->pay->request('refundquery');

        if(strtolower($result['return_code']) == 'fail') {
            $this->setError($result['return_msg']);
            return false;
        }
        if(strtolower($result['result_code']) == 'fail') {
            $this->setError($result['err_code_des']);
            return false;
        }
        return array(
            'out_refund_no'=>(isset($result['out_refund_no_0']) ? $result['out_refund_no_0'] : ''),
            'out_trade_no'=>$result['out_trade_no'],
            'refund_fee'=>round($result['refund_fee']/100, 2),
            'transaction_id'=>$result['transaction_id'],
        );
    }

    /**
     * 退款
     */
    public function refund() {
        $out_trade_no = $this->getData('out_trade_no');
        $out_refund_no = $this->getData('out_refund_no');
        $total_fee = $this->getData('total_fee');
        $refund_fee = $this->getData('refund_fee');
        $refund_desc = $this->getData('refund_desc');
        $params = array(
            'appid'=>$this->pay->getAppId(),
            'mch_id'=>$this->pay->getMachineId(),
            'out_trade_no'=>$out_trade_no,  //商户订单号
            'out_refund_no'=>$out_refund_no,  //商户退款单号
            'total_fee'=>intval($total_fee*100),  //订单金额
            'refund_fee'=>intval($refund_fee*100),  //退款金额
        );
        if(!empty($refund_desc)){
            $params['refund_desc'] = $refund_desc;  //退款原因
        }
        //退款需要证书
        \Common\Extend\Curl::$certtype_cert_name = 'PEM';
        \Common\Extend\Curl::$certtype_cert_file = APP_PATH . 'Common/Extend/Payment/Wechatpay/cert/apiclient_cert.pem';
        \Common\Extend\Curl::$certtype_key_name = 'PEM';
        \Common\Extend\Curl::$certtype_key_file = APP_PATH . 'Common/Extend/Payment/Wechatpay/cert/apiclient_key.pem';

        $this->pay->setDatas($params);
        $result = $this->pay->request('refund');

        if(strtolower($result['return_code']) == 'fail') {
            $this->setError($result['return_msg']);
            return false;
        }
        if(strtolower($result['result_code']) == 'fail') {
            $this->setError($result['err_code_des']);
            return false;
        }
        return array(
            //'refund_id'=>$result['refund_id'],  //微信退款单号
            'transaction_id'=>$result['transaction_id'],  //微信订单号
            'out_trade_no'=>$result['out_trade_no'],  //商户订单号
            //'out_refund_no'=>$result['out_refund_no'],  //商户退款单号
            //'total_fee'=>round($result['total_fee']/100, 2),  //标价金额
            'refund_fee'=>round($result['refund_fee']/100, 2),  //退款金额
        );
    }

    /**
     * 异步回调
     * @return bool
     */
    public function notify(){
        $result = $this->pay->notify();
        if($result === false){  //校验失败，数据有问题
            $error = $this->pay->getError();
            if(!empty($error)){
                $this->setError($error);
            }
            return false;
        }else{  //校验成功，有数据
            $data = array(
                'result'=>0,  //0=支付失败，1=支付成功
                'order_sn'=>$result['out_trade_no'],
                'order_amount'=>($result['total_fee']/100),
                'create_time'=>0,
                'pay_time'=>isset($data['time_end']) ? Time::gmstr2time($data['time_end']) : 0,
                'data'=>$result,
            );
            if($result["return_code"] == "SUCCESS" && $result["result_code"] == "SUCCESS"){
                $data['result'] = 1;  //支付成功
            }
            return $data;
        }
    }

    /**
     * 同步回调 - 微信没有真正意义上的同步回调页面，默认成功
     * @return bool
     */
    public function respond(){
        $order = session('wechatpay_respond_data');
        session('wechatpay_respond_data', NULL);
        $data = array(
            'result'=>(isset($order['order_sn'])&&!empty($order['order_sn']) ? 1 : 0),  //0=支付失败，1=支付成功
            'order_sn'=>$order['order_sn'],
            'order_amount'=>($order['order_amount']/100),
            'create_time'=>0,
            'pay_time'=>0,
            'data'=>array()
        );
        return $data;
    }

    /**
     * 获取唤起微信支付的JS代码
     * @param array $params 微信接口的相关参数
     * @param string $respond_url 获取到openid后的跳转回来地址
     * @return string
     */
    private function startToPay($params, $respond_url){
        //调用微信JS api 支付
        $string = '<script type="text/javascript">
		function jsApiCall(){
			WeixinJSBridge.invoke("getBrandWCPayRequest",'.json_encode($params).',function(res){
				WeixinJSBridge.log(res.err_msg);
				if(res.err_desc) {
					alert(res.err_code+"-支付出错："+res.err_desc+res.err_msg);
				}
				if(res.err_msg.indexOf("ok")>0){
					window.location.href="'.$respond_url.'";
				}
			});
		}
		function callpay(){
			if (typeof WeixinJSBridge == "undefined"){
			    if( document.addEventListener ){
			        document.addEventListener("WeixinJSBridgeReady", jsApiCall, false);
			    }else if (document.attachEvent){
			        document.attachEvent("WeixinJSBridgeReady", jsApiCall);
			        document.attachEvent("onWeixinJSBridgeReady", jsApiCall);
			    }
			}else{
			    jsApiCall();
			}
		}
		callpay();
		</script>';
        return $string;
    }

    /**
     * 获取prepay_id
     */
    private function getPrepayId() {
        $wechatpayParams = session('wechatpay_params');
        session('wechatpay_params', NULL);
        if(empty($wechatpayParams) || empty($wechatpayParams['order_sn'])){
            $this->setError('系统错误！');
            return false;
        }
        $body = !empty($wechatpayParams['body']) ? $wechatpayParams['body'] : $wechatpayParams['order_sn'];
        $params = array(
            'openid'=>session('sopenid'),
            'body'=>$body,
            'out_trade_no'=>$wechatpayParams['order_sn'],
            'total_fee'=>$wechatpayParams['order_amount'] * 100,
            'notify_url'=>$this->getNotifyUrl(),
            'trade_type'=>'JSAPI',
            'appid'=>$this->pay->getAppId(),
            'mch_id'=>$this->pay->getMachineId(),
        );

        $this->pay->setDatas($params);
        $result = $this->pay->request('unifiedorder');

        if(strtolower($result['result_code']) == 'fail') {
            $this->setError($result['err_code_des']);
            return false;
        }
        $prepay_id = $result["prepay_id"];
        return $prepay_id;
    }
}