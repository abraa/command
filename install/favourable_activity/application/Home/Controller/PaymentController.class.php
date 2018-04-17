<?php
/**
 * ====================================
 * 支付相关 控制器
 * ====================================
 * Author: 9009123 (Lemonice)
 * Date: 2017-10-11 09:59
 * ====================================
 * File: PaymentController.class.php
 * ====================================
 */
namespace Home\Controller;
use Common\Controller\InitController;
use Common\Logic\Pay;

class PaymentController extends InitController {
    protected $dbModel = NULL;
    public function __construct(){
        parent::__construct();
        $this->dbModel = D('Common/Home/Payment');
    }

    /**
     * 支付异步校验
     */
    public function respond(){
        $paycode = I('request.paycode','','trim');

        if(empty($paycode)){
            $this->error('系统错误！');
        }
        $Payment = new Pay();
        $Payment->setData('paycode', $paycode);
        $Payment->setData('paytype', 'respond');

        $params = $Payment->handle();

        $params['verify_type'] = 1;  //同步返回
        $params['pay_id'] = \Common\Logic\Payment::getPayId($paycode);

        $this->respondCallBack($params);  //处理后续的操作、显示页面
    }

    /**
     * 支付异步校验
     */
    public function notify(){
        $paycode = I('request.paycode','','trim');

        if(empty($paycode)){
            $this->error('系统错误！');
        }
        $Payment = new Pay();
        $Payment->setData('paycode', $paycode);
        $Payment->setData('paytype', 'notify');

        $params = $Payment->handle();
        $params['verify_type'] = 2;  //异步返回
        $params['pay_id'] = \Common\Logic\Payment::getPayId($paycode);

        $this->notifyCallBack($params);  //支付成功，异步相关的处理
    }

    /**
     * 同步回调地址，同步支付成功后回调处理、显示页面
     */
    private function respondCallBack($params = array()){
        //记录日记，无论成功失败
        $params['log'] = serialize($params['data']);
        D('Common/Home/PayLog')->addPayLog($params);
        if(isset($params['result']) && $params['result'] == 1){  //支付成功
            D('Common/Home/PayInfo')->orderPaid($params);  //更新订单状态、信息
            $this->skipSuccess();
        }else{  //支付失败
            //微信的默认都显示成功
            $paycode = I('request.paycode','','trim');
            if($paycode == 'wechatpay'){
                //更新会员中心订单的支付状态为：付款中
                D('Common/Home/OrderInfoCenter')->where("order_sn = '".$params['order_sn']."'")->save(array(
                    'pay_status'=>PS_PAYING,
                ));
                $this->skipSuccess();
            }
        }
        $this->skipError('支付操作失败，请重试或联系客服！');
    }

    /**
     * 异步回调地址，异步支付成功后回调处理 - 此方法不能输出任何信息
     */
    private function notifyCallBack($params = array()){
        $paytype = I('request.paytype','','trim');
        if(empty($paytype)){
            return false;
        }
        $params['log'] = serialize($params['data']);
        D('Common/Home/PayLog')->addPayLog($params);

        $paytype = strtoupper($paytype);
        if($paytype == 'FACE2FACE'){  //二维码支付,面对面付款

        }else{  //默认支付
            if(isset($params['result']) && $params['result'] == 1){  //支付成功
                D('Common/Home/PayInfo')->orderPaid($params);  //更新订单状态、信息
            }
        }
    }

    /**
     * 支付宝当面付异步校验接口
     */
    public function notifyF2f(){
        $paycode = I('request.paycode','','trim');
        if(empty($paycode)){
            $this->error('系统错误！');
        }
        $Payment = new Pay();
        $Payment->setData('paycode', $paycode);
        $Payment->setData('paytype', 'face2FaceNotify');
        $params = $Payment->handle();

        $params['verify_type'] = 2;  //异步返回
        $params['pay_id'] = \Common\Logic\Payment::getPayId($paycode);
        $params['log'] = serialize($params['data']);
        D('Common/Home/PayLog')->addPayLog($params);

        if(isset($params['result']) && $params['result'] == 1){  //支付成功
            D('Common/Home/PayInfo')->orderPaid($params);  //更新订单状态、信息
        }
    }

    /**
     * 显示支付成功的页面
     * @param string $tplName
     */
    private function skipSuccess($tplName = 'pay_success'){
        $payOnlineOrderSn = session('pay_online_order_sn');    //新增订单的order_sn
        $userId = $this->dbModel->getUser('user_id');  //用户ID
        $userId = $userId ? $userId : 0;

        $newUser = array();
        //用户尚未登陆，查看该收货手机号在会员中心是否存在，不存在则初始注册（订单归属问题）
        if($userId == 0){
            $PaymentLogic = new \Common\Logic\Payment();
            $result = $PaymentLogic->addNewMenber();  //自动注册会员
            $userId = isset($result['user_id']) ? $result['user_id'] : (isset($result['new_user_id']) ? $result['new_user_id'] : 0);
            if(isset($result['new_user_id'])){  //新自动注册用户
                $newUser = $result['newUser'];
            }
        }
        //获取联系电话
        $campaign = I('request.campaign');
        $data = getAdvisoryInfo($campaign);
        $this->assign('tel',$data['tel']);
        $this->assign('new_user',$newUser);
        $this->assign('never_login', 1);  //未登录过的
        $this->assign('order_sn',$payOnlineOrderSn);
        $this->assign('user_id',$userId);
        $this->display($tplName);
        exit;
    }

    /**
     * 显示支付失败、错误的页面
     * @param string $msg 错误信息
     * @param string $tplName
     */
    private function skipError($msg = '', $tplName = 'pay_error'){
        $payOnlineOrderSn = session('pay_online_order_sn');    //新增订单的order_sn
        $userId = $this->dbModel->getUser('user_id');  //用户ID
        $userId = $userId ? $userId : 0;
        //获取联系电话
        $campaign = I('request.campaign');
        $data = getAdvisoryInfo($campaign);
        $this->assign('tel',$data['tel']);
        $this->assign('error_msg',$msg);
        $this->assign('order_sn',$payOnlineOrderSn);
        $this->assign('user_id',$userId);
        $this->display($tplName);
        exit;
    }

    /**
     * 跳转到Q站获取openid
     */
    public function getOpenId(){
        //测试的openid
        //session('sopenid', 'oFJj4s0X8rLPta-HwWvSoXWo5H3s');
        $openid = session('sopenid');

        $Payment = new Pay();
        $Payment->setData('paycode', 'wechatpay');
        if(empty($openid)){
            $WechatInit = new \Common\Logic\Wechat\WechatInit();
            $openid = $WechatInit->getOpenId();
            if(is_null($openid) || empty($openid)){
                $this->assign('error_msg', '您未授权获取微信认证信息！');
                $this->display('RespondPayment/error');
                exit;
            }
        }

        $wechatpayParams = session('wechatpay_params');
        $paySource = session('pay_source');
        session('pay_source', NULL);
        if(!$wechatpayParams || !isset($wechatpayParams['order_sn'])){
            if(!is_null($paySource)){
                header("Location: " . $paySource);  //跳转回商品页
            }
            $this->assign('error_msg','请不要重复刷新页面，若已经支付，您可以忽略并关闭此页面!');
            $this->display('RespondPayment/error');
            exit;
        }
        $paytype = I('request.paytype', '','trim');
        $Payment->setData('paytype', $paytype);
        $Payment->setData('openid', $openid);
        $Payment->setData('order_body', $wechatpayParams['order_body']);//商品描述
        $Payment->setData("order_sn", $wechatpayParams['order_sn']);
        $Payment->setData("order_amount", intval($wechatpayParams['order_amount']*100));//总金额
        $result = $Payment->handle();
        if($result === false){
            $this->assign('error_msg',$Payment->getError());
            $this->display('RespondPayment/error');
            exit;
        }
        echo $result;  //开始支付
        exit;
    }

    /**
     * 默认方法，暂时不用 - 预留
     */
    public function index(){
        send_http_status(404);
    }
}