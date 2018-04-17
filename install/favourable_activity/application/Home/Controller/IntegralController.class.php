<?php
/**
 * ====================================
 * 积分 控制器
 * ====================================
 * Author: 9009123 (Lemonice)
 * Date: 2017-02-07 15:31
 * ====================================
 * File: IntegralController.class.php
 * ====================================
 */
namespace Home\Controller;
use Common\Controller\InitController;

class IntegralController extends InitController{
	private $not_login_msg = '您还未登录，请先登录';  //当前没登录的提示信息
	
	private $dbModel = NULL;  //储存地址数据表对象
	
	protected $user_id = 0;  //当前登录的ID
	
	private $not_login_action = array();  //不需要登录的方法
	
	public function __construct(){
		parent::__construct();
		$this->dbModel = D('PointExchangeCenter');
		if(isset($this->not_login_action) && !in_array(ACTION_NAME, $this->not_login_action)){
			$this->user_id = $this->checkLogin();  //检查登录，获取用户ID
		}
	}
	
	/*
	*	积分商城商品列表
	*	@Author 9009123 (Lemonice)
	*	@return exit & Json
	*/
	public function goodsList(){
		$user_id = $this->user_id;
		$page = I('request.page',1,'intval');
		$pageSize = I('request.pageSize',8,'intval');
		
        $data = $this->dbModel->getPage($user_id, $page, $pageSize);
		$this->success($data);
	}

    /**
     * 积分商城商品详情
     */
	public function goodsInfo(){
		$user_id = $this->user_id;
		$exchange_id = I('request.exchange_id',0,'intval');
		if($exchange_id <= 0){
			$this->error('出错了');
		}
        $data = $this->dbModel->getInfo($exchange_id, $user_id);
		//偏远地区+15元
		if(isset($data['shipping_fee_remote']) && $data['shipping_fee_remote'] > 0){
			//检查收货地址是否有设置
			$consignee = D('Common/Home/UserAddress')->getUserDefaultAddress();
			$PaymentObject = new \Common\Logic\Payment();
            $provinceId = isset($consignee['province']) ? $consignee['province'] : 0;
            $data['shipping_fee'] = $PaymentObject->getShippingFee($orderAmount = 0, $provinceId, $data['shipping_fee'], false);
		}
		$this->success($data);
	}
	
	/*
	*	积分日记列表
	*	@Author 9009123 (Lemonice)
	*	@return exit & Json
	*/
	public function logList(){
        $user_id = $this->user_id;
		$page = I('request.page',1,'intval');
		$pageSize = I('request.pageSize',8,'intval');

        $custom_id = D('Users')->where(array('user_id'=>$user_id))->getField('custom_id');

		$where = array();
		$where[] = "customer_id = '$custom_id'";
        $where[] = "point_type IN(0,1,2,3)";
		
        $IntegralLogCenter = D('IntegralLogCenter');  //会员中心的订单表
		$field = 'order_sn,state,points,remark,add_time,point_type';
		$order = 'add_time desc';
        $data = $IntegralLogCenter->getPage($field,(!empty($where)?implode(' and ',$where):''), $order, $page, $pageSize);
        
		$this->success($data);
	}
	
	/*
	*	积分兑换列表 - 【我的兑换】
	*	@Author 9009123 (Lemonice)
	*	@return exit & Json
	*/
	public function exchangeList(){
        $user_id = $this->user_id;
		$page = I('request.page',1,'intval');
		$pageSize = I('request.pageSize',8,'intval');
		
		$site_id = C('SITE_ID');
		
		$where = array();
		$where[] = "user_id = '$user_id'";
		$where[] = "site_id = '$site_id'";
		
        $UserPointExchangeModel = D('UserPointExchangeCenter');
		$field = 'order_id,order_sn,data,addtime,goods_number';
		$order = 'addtime desc';
        $data = $UserPointExchangeModel->getPage($field,(!empty($where)?implode(' and ',$where):''), $order, $page, $pageSize);
        
		$this->success($data);
	}

    /**
     * 积分兑换 - 购买下单
     */
	public function createOrder(){
        $exchangeId = I('request.exchange_id',0,'intval');
        $addressId = I('request.address_id',0,'intval');
        $paymentId = I('request.payment_id',0,'intval');  //1是货到付款（不支持）
        $remark = I('request.remark','','trim');
        $sourceUrl = I('request.source_url','','trim');  //订单来源地址

        if($exchangeId <= 0){
            $this->error('此商品不存在');
        }
        if($paymentId <= 1){
            $this->error('选择的支付方式不存在');
        }

        $isWechat = isCheckWechat();
        if($isWechat == true && $paymentId == 4){  //微信不支持支付宝
            $this->error('请选择支付方式。');
        }

        $PaymentObject = new \Common\Logic\Integral();
        $PaymentObject->setData('exchange_id', $exchangeId);
        $PaymentObject->setData('address_id', $addressId);
        $PaymentObject->setData('payment_id', $paymentId);
        $PaymentObject->setData('remark', $remark);
        $PaymentObject->setData('source_url', $sourceUrl);
        $result = $PaymentObject->createOrder();
        if($result === false){
            $this->error($PaymentObject->getError());
        }
		$data = array(
			'order_sn'=>$result['order_sn'],
			'amount'=>$result['order_amount'],
			'shipping_fee'=>$result['shipping_fee'],
			'payment_id'=>(isset($result['bank_id']) ? $result['bank_id'] : $result['pay_id']),
			'payment_name'=>$result['pay_name'],
			'remark'=>$result['postscript'],
			'content'=>$result['content'],
		);
		$this->success($data);
	}
	
	/*
	*	获取订单的来源地址
	*	@Author 9009123 (Lemonice)
	*	@param array $order 订单详情
	*	@return array
	*/
	private function getSourceUrl($order = array()){
		$source_url = I('source_url','','trim');
		$cookie_source_url = cookie('source_url');
		$order['ip_info_text'] = $cookie_source_url!='' ? $cookie_source_url : ($source_url!='' ? $source_url : '');
        if(!empty($order['ip_info_text'])){
            $weixin = array();
            preg_match('/campaign=(\w*)_kefugw/',$order['ip_info_text'],$weixin);
            if(!empty($weixin)){
                $order['weixin'] = $weixin[1];
            }
        }
		//判断是否微信打开的，如果是则增加openid到来源地址
		$is_wechat = isCheckWechat();
		if($is_wechat == true){  //微信打开网页
			$openid = session('sopenid');
			if(strstr($order['ip_info_text'],'?')){
				$order['ip_info_text'] = $order['ip_info_text'] . '&openid='.$openid;
			}else{
				$order['ip_info_text'] = $order['ip_info_text'] . '?openid='.$openid;
			}
		}
		
		return $order;
	}
	
	/*
	*	检查当前是否登录
	*	@Author 9009123 (Lemonice)
	*	@return int [user_id]
	*/
	private function checkLogin(){
		$user_id = $this->getUserId();  //用户ID
		if($user_id <= 0){
			$this->error($this->not_login_msg);  //没登录
		}
		return $user_id;
	}
	
	/*
	*	获取当前登录用户ID
	*	@Author 9009123 (Lemonice)
	*	@return int [user_id]
	*/
	private function getUserId(){
		$user_id = $this->dbModel->getUser('user_id');  //用户ID
		$user_id = $user_id ? $user_id : 0;
		return $user_id;
	}
}