<?php
/**
 * ====================================
 * 刷卡支付 控制器
 * ====================================
 * Author: 9009123 (Lemonice)
 * Date: 2017-07-18 10:50
 * ====================================
 * File: MicroPayController.class.php
 * ====================================
 */
namespace Home\Controller;
use Common\Controller\InitController;

class MicroPayController extends InitController{
	public function __construct(){
		parent::__construct();
	}

    /**
     * 刷卡支付 - 微信
     */
	public function wechatPay(){
        $auth_code = I('request.auth_code','','trim');
        $body = I('request.body','','trim');
        $out_trade_no = I('request.out_trade_no','','trim');
        $total_fee = I('request.total_fee',0,'intval');

        if(empty($auth_code)){
            $this->error('商户单号不能为空！');
        }
        if(empty($out_trade_no)){
            $this->error('交易单号不能为空！');
        }
        if($total_fee <= 0){
            $this->error('支付金额不能小于1分钱！');
        }
        if(empty($body)){
            $this->error('交易描述不能为空！');
        }
        import('Common/Extend/Payment/Wechatpay/WxPay');
        //使用统一支付接口
        $MicroPay = new \MicroPay();
        $MicroPay->setParameter("auth_code", $auth_code);
        $MicroPay->setParameter("body", $body);
        $MicroPay->setParameter("total_fee", $total_fee);
        $MicroPay->setParameter("out_trade_no", $out_trade_no);

        $result = $MicroPay->pay();

        if($result === false){
            $this->error('接口调用失败！',array(
                'err_code'=>'CURL_ERROR_1',  //错误代码
                'err_code_des'=>'请求微信服务器通讯失败',  //错误代码描述
            ));
        }
        if($result['return_code'] && $result['return_code'] == 'SUCCESS'){  //通讯成功，不代表交易成功
            if($result['result_code'] == 'SUCCESS'){  //支付成功
                $this->success(array(
                    'openid'=>(isset($result['openid']) ? $result['openid'] : ''),  //用户微信openid
                    'is_subscribe'=>(isset($result['is_subscribe']) ? ($result['is_subscribe']=='Y'?1:0) : 0),  //是否有关注,取值范围：Y或N;Y-关注;N-未关注
                    'total_fee'=>(isset($result['total_fee']) ? $result['total_fee'] : ''),  //订单总金额，单位为分，只能为整数
                    'settlement_total_fee'=>(isset($result['settlement_total_fee']) ? $result['settlement_total_fee'] : ''),  //应结订单金额,应结订单金额=订单金额-免充值优惠券金额。
                    'coupon_fee'=>(isset($result['coupon_fee']) ? $result['coupon_fee'] : ''),  //代金券金额,“代金券”金额<=订单金额，订单金额-“代金券”金额=现金支付金额
                    'cash_fee'=>(isset($result['cash_fee']) ? $result['cash_fee'] : ''),  //现金支付金额,订单现金支付金额
                    'transaction_id'=>(isset($result['transaction_id']) ? $result['transaction_id'] : ''),  //微信支付订单号,微信交易号
                    'out_trade_no'=>(isset($result['out_trade_no']) ? $result['out_trade_no'] : ''),  //商户系统内部订单号
                    'time_end'=>(isset($result['time_end']) ? $result['time_end'] : ''),  //支付完成时间,格式：20141030133525
                ));
            }else{  //支付失败了
                $this->error('支付失败！',array(
                    'err_code'=>isset($result['err_code']) ? $result['err_code'] : 'ERROR',  //错误代码
                    'err_code_des'=>isset($result['err_code_des']) ? $result['err_code_des'] : '未知错误',  //错误代码描述
                ));
            }
        }else{
            $this->error('微信服务器通讯失败！['.json_encode($result).']',array(
                'err_code'=>'CURL_ERROR_2',  //错误代码
                'err_code_des'=>'请求微信服务器通讯失败',  //错误代码描述
            ));
        }
	}

    /**
     * 暂时不使用本控制器默认方法，预留
     */
	public function index(){
		send_http_status(404);
	}
}