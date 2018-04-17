<?php
/**
 * ====================================
 * 扫码支付二维码生成接口
 * ====================================
 * Author: 9004396
 * Date: 2016-09-28 16:09
 * ====================================
 * File:ScanPaymentController.class.php
 * ====================================
 */
namespace Api\Controller;
use Common\Controller\ApiController;
use Common\Extend\Time;

class ScanPaymentController extends ApiController {
    //密钥权限
    protected $_permission = array(
        'index' => array('scan_payment')
    );

    private $_payType = array(
        '4' => 'alipay',
        '7' => 'tenpay',
        '8' => 'kuaiqian',
        '18'=> 'wechatpay',
    );

    public function index(){
        $params = I('request.');
        $payId = empty($params['pay_id']) > 100 ? 7 : $params['pay_id'];
        if(empty($params['order_sn'])){
            $this->error('20005');
        }
        if(empty($params['subject'])){
            $this->error('20007');
        }
        if(empty($params['order_amount'])){
            $this->error('20006');
        }

        $para = $this->paraFilter($params);

        $isSign = $this->verify($para,$params['sign'],$para['key']);
        if(!$isSign){
            $this->error('20002');
        }
        if($payId != 4 && $payId != 18){
            $this->error('20001');
        }

		$PayInfoModel = D('Common/Home/PayInfo');  //payment库
		$PayMultipleLogModel = D('Common/Home/PayMultipleLog');
		$time = Time::gmTime();
		
		//为了避免提示重复交易问题
		$para["order_sn"] = (strstr($para["order_sn"],'_')===false) ? $para["order_sn"] .'_'. $time : $para["order_sn"];
		$num = $PayMultipleLogModel->childIsPayed($para['order_sn'], $payId);  //查看该订单是否已经支付完成
		if ($num > 0){
			$this->error('20003');
		}

        $order = array(
            'site_id'       => $para['site_id'],
            'pay_id'        => $para['pay_id'],
            'order_sn'      => $para['order_sn'],
            'consignee'     => $para['name'],
			'order_money'   => 0,  //订单已支付金额0元
            'order_amount'  => $para['order_amount']
        );
        //更新订单详情信息
        $id = $PayInfoModel->insertPayInfo($order,$time);
        if(empty($id) || $id < 0){
            $this->error('20004');
        }

        $params['code'] = \Common\Logic\Payment::$paymentData[$payId]['code'];
        $Payment = new \Common\Logic\Pay();
        $Payment->setData('paycode', $params['code']);
        $Payment->setData('pay_type', 'face2Face');
        $Payment->setData('paytype', 'face2Face');
        $Payment->setData('order_sn', $params['order_sn']);
        $Payment->setData('body', $params['subject']);
        $Payment->setData('order_amount', $params['order_amount']);
        $content = $Payment->handle();
        if($content !== false){
            $this->success(base64_encode($content));
        }else{
            $this->error('20008');
        }
    }
}