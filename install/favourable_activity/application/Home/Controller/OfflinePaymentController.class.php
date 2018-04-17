<?php
/**
 * ====================================
 * 离线支付 控制器
 * ====================================
 * Author: 9009123 (Lemonice)
 * Date: 2016-07-04 13:50
 * ====================================
 * File: OfflinePaymentController.class.php
 * ====================================
 */
namespace Home\Controller;
use Common\Controller\InitController;
use Common\Extend\Time;
use Common\Extend\PhxCrypt;

class OfflinePaymentController extends InitController{
	private $dbModel = NULL;  //储存地址数据表对象

	public function __construct(){
		parent::__construct();
		$this->dbModel = D('PayInfo');
	}

    /**
     * 后台生成URL链接，获取URL上的参数详情，解密URL上的加密串
     */
	public function getOrderInfo(){
		$param = I('request.param_code','','trim');
		if(trim($param) == ''){
			$this->error('请传加密串！');
		}

        $Payment = new \Common\Logic\Payment();
        $params = $Payment->decodeOrderString($param);  //解密订单加密串

        $orderSnArray = strstr($params['order_sn'],'_') !== false ? explode('_',$params['order_sn']) : array($params['order_sn']);
        $orderSn = $orderSnArray[0];

        if(!isset($orderSnArray[1]) || empty($orderSnArray[1]) || !is_numeric($orderSnArray[1])){
            $this->error('时间参数缺失！');
        }
        $diffTime = Time::localGettime() - $orderSnArray[1];
        if($diffTime > (60*30)){
            $this->error('此连接超时！');
        }

        $orderInfo = D('Common/Home/OrderInfoCenter')->where(array('order_sn' => $orderSn,'is_chinaskin' => 0))->find();
        if(!empty($orderInfo)){
            if(in_array($orderInfo['order_status'],array(2,3,4))|| in_array($orderInfo['shipping_status'],array(2)) || in_array($orderInfo['pay_status'],array(2))){
                $this->error('该订单状态异常，请联系客服');
            }
        }

		//先保存到session，支付时候需要用到订单金额
		$offlineOrderAmount = session('offline_order_amount');
        $offlineOrderAmount = is_array($offlineOrderAmount)&&!empty($offlineOrderAmount) ? $offlineOrderAmount : array();
        $offlineOrderAmount[$params['order_sn']] = $params['payamount'];

		session('offline_order_amount', $offlineOrderAmount);

		$this->success(array(
			'payAmount'=>(isset($params['payamount']) ? $params['payamount'] : ''),
			'payerName'=>(isset($params['payerName']) ? $params['payerName'] : ''),
			'orderSn'=>(isset($params['order_sn']) ? $params['order_sn'] : ''),
		));
	}

    /**
     * 创建订单 - 下一步：跳转到第三方平台支付
     */
	public function create(){
		$order  = array();
		$order["order_sn"]      = I('request.order_sn','','trim');
		$order["order_amount"]  = round(I('request.payamount',0,'floatval'),2);
		$order["consignee"]     = substr(I('request.payerName','','trim'),0,10);
		$order["mobile"]        = substr(I('request.payerTelephone','','trim'),0,13);
		$paymentType            = I('request.payment',0,'intval');
        $order['pay_id']        = $paymentType;
				
		if(empty($order["order_amount"])){
			$this->error('亲爱的用户，请填写支付金额！');
		}
		if(empty($order["consignee"])){
			$this->error('亲爱的用户，请填写填写付款人！');
		}
		if(empty($order["mobile"])){
			$this->error('亲爱的用户，请填写联系电话！');
		}
		if(empty($paymentType)){
			$this->error('亲爱的用户，请选择支付方式！');
		}
        $order["order_sn"] = empty($order["order_sn"]) ? $order["mobile"] . '_' . date("His") : $order["order_sn"];
        //获取验支付Code
        $order['code'] = $this->getCode($paymentType);

		$time = Time::gmTime();

        //为了避免提示重复交易问题
        $order['order_sn'] = $this->checkRepeat($order['order_sn'], $paymentType);
		
		//记录相关信息到session，目的是为了供回调使用
		$array = array('mobile'=>$order["mobile"]);
		session('new_consignee', $array);
		session('pay_online_order_sn', $order["order_sn"]);
		
		//更新订单详情信息
		$order['order_money'] = 0;  //此子订单已付金额0元
        D('Common/Home/PayInfo')->insertPayInfo($order, $time);

        //生成支付代码
        $order["content"] = $this->goToPay($order['code'], $order['order_sn'], $order['order_amount']);

        $result = array(
            'payment'=>$paymentType,
            'content'=>$order["content"],
        );
		$this->success($result);
	}

    /**
     * 重新支付某个订单
     */
	public function RePay(){
		$order_id = I('request.order_id','','trim');  //订单ID
		if(intval($order_id) <= 0){
			$this->error('订单不存在');
		}
        //查询订单详情，做相关校验
        $order = D('Common/Home/OrderInfoCenter')->field('order_sn,user_id,order_status,pay_status,consignee,pay_id,order_amount')->where(array('order_id'=>$order_id))->find();
		if(empty($order)){
			$this->error('订单不存在');
		}
		if($order['order_amount'] <= 0){
			$this->error('您的订单应付金额剩余0元，无需支付');
		}
		if($order['pay_id'] == 1){
			$this->error('您的订单是货到付款，无需在线支付');
		}
		$paymentType            = $order['pay_id'];
        //获取验支付Code
        $order['code'] = $this->getCode($paymentType);

		$time = Time::gmTime();

		//为了避免提示重复交易问题
        $order['order_sn'] = $this->checkRepeat($order['order_sn'], $paymentType);
		
		//记录相关信息到session，目的是为了供回调使用
		session('pay_online_order_sn', $order["order_sn"]);
		
		//更新订单详情信息
		$order['order_money'] = 0;  //此子订单已支付0元
		D('Common/Home/PayInfo')->insertPayInfo($order, $time);
        //生成支付代码
        $order["content"] = $this->goToPay($order['code'], $order['order_sn'], $order['order_amount']);

        $result = array(
            'payment'=>$paymentType,
            'content'=>$order["content"],
        );
		$this->success($result);
	}

    /**
     * 获取订单列表 - 离线支付页面
     */
    public function getOrderSnList(){
        $mobile = I('request.mobile','','trim');
        if(empty($mobile) || !isMobile($mobile)){
            $this->error('手机号码不存在或格式错误！');
        }
        $mobile = PhxCrypt::phxEncrypt($mobile);
        $field = 'order_sn,consignee,order_amount,goods_amount,bonus,integral_money,shipping_fee,discount,money_paid,payment_discount,update_time';
        $where = "pay_status in (0,3) AND (tel = '$mobile' OR mobile = '$mobile') AND is_chinaskin = 0";
        $list = D('Common/Home/OrderInfoCenter')->field($field)->where($where)->order('add_time DESC')->select();

        if(!empty($list)){
            foreach($list as $key=>$value){
                if($value['update_time'] >= Time::localStrtotime('2016-10-18 16:00:00') || $value['integral_money'] > 0){
                    $orderAmount = $value['order_amount'] + $value['money_paid'];
                }else{
                    $orderAmount = $value['goods_amount'] - $value['bonus'] - $value['integral_money'] + $value['shipping_fee'] - $value['discount'] - $value['payment_discount'];
                }
                $list[$key] = array(
                    'order_sn'=>$value['order_sn'],
                    'consignee'=>$value['consignee'],
                    'order_amount'=>$orderAmount,
                );
            }
        }
        $this->success($list);
    }

    /**
     * 检查订单是否重复发起请求
     * @param string $orderSn
     * @param int $paymentType
     * @return string
     */
    private function checkRepeat($orderSn = '', $paymentType = 0){
        //为了避免提示重复交易问题
        $orderSn = (strstr($orderSn,'_')===false) ? $orderSn .'_'. Time::gmTime() : $orderSn;
        $num = D('Common/Home/PayMultipleLog')->childIsPayed($orderSn, $paymentType);  //查看该订单是否已经支付完成
        if ($num > 0){
            $this->error('该订单已经完成支付，无法重复操作！');
        }
        return $orderSn;
    }

    /**
     * 获取支付ID对应的支付Code
     * @param int $paymentId
     * @return string
     */
    private function getCode($paymentId = 0){
        //获取并且校验支付Code
        $code = isset(\Common\Logic\Payment::$paymentData[$paymentId]['code']) ? \Common\Logic\Payment::$paymentData[$paymentId]['code'] : '';
        if(empty($code)){
            $this->error('您当前的站点不支持使用该支付方式，请重新选择！');
        }
        return $code;
    }

    /**
     * 生成支付HTML代码
     * @param string $code  支付类型code
     * @param string $orderSn  订单号
     * @param int $orderAmount  订单应付金额
     * @return bool
     */
    private function goToPay($code = '', $orderSn = '', $orderAmount = 0){
        $Payment = new \Common\Logic\Pay();
        $Payment->setData('paycode', $code);
        $Payment->setData('pay_type', 'pay');
        $Payment->setData('paytype', 'getCode');
        $Payment->setData('order_sn', $orderSn);
        $Payment->setData('body', '订单支付('.$orderSn.')');
        $Payment->setData('order_amount', $orderAmount);
        $content = $Payment->handle();
        if($content === false){
            $error = $Payment->getError();
            $this->error($error);
        }
        return $content;
    }

    /**
     * 暂时不使用本控制器默认方法，预留
     */
    public function index(){
        send_http_status(404);
    }
}