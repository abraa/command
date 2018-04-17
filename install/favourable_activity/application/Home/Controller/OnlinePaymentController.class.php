<?php
/**
 * ====================================
 * 在线支付 控制器
 * ====================================
 * Author: 9009123 (Lemonice)
 * Date: 2016-06-29 15:31
 * ====================================
 * File: OnlinePaymentController.class.php
 * ====================================
 */
namespace Home\Controller;
use Common\Controller\InitController;
use Common\Extend\Time;
use Common\Extend\Send;
use Common\Logic\BonusLogic;
use Common\Logic\FavourableActivityLogic;

class OnlinePaymentController extends InitController{
    /**
     * session id
     * @var null|string
     */
	private $sessionId = NULL;

    /**
     * 储存购物车模型的对象
     * @var null
     */
	private $CartModel = NULL;
    /**
     * 地址业务层对象
     * @var null
     */
    private $logicUserAddress = NULL;
    /**
     * 下单、支付业务层对象
     * @var null
     */
    private $logicPayment = NULL;
    /**
     * 用于储存购物车业务层对象
     * @var null
     */
    private $logicCart = NULL;
	
	public function __construct(){
		parent::__construct();
		$this->CartModel = D('Common/Home/Cart');

        $this->user_id = $this->CartModel->getUser('user_id');  //用户ID

        $this->sessionId = session_id();

        $this->logicUserAddress = new \Common\Logic\UserAddress();
        $this->logicCart = new \Common\Logic\Cart();
        $this->logicPayment = new \Common\Logic\Payment();
	}

    /**
     * 设置某个参数的I函数可获取 - 为了兼容旧Q站的文章推广页面
     * @param string $name 字段名称、参数名称
     * @param string $value 值
     */
    private function setRequest($name = '', $value = ''){
        $_REQUEST[$name] = $value;
        $_POST[$name] = $value;
        $_GET[$name] = $value;
    }

    /**
     * 获取订单应付金额 - 文章页面支付
     */
	public function quickAggregate(){
		//设置地址
		$address['consignee'] = I('request.consignee','','trim');  //姓名
		$address['mobile'] = I('request.mobile','','trim');  //手机号码
		$address['province'] = I('request.province',0,'intval');  //省份
		$address['city'] = I('request.city',0,'intval');  //城市
		$address['district'] = I('request.district',0,'intval');  //区域
		$address['town'] = I('request.town',0,'intval');  //街道
		$address['address'] = I('request.address','','trim');  //详细地址
		$address['attribute'] = I('request.attribute','','trim');  //属性
		
		if($address['consignee'] != '' && 
			$address['mobile'] != ''
			&& $address['province'] > 0
			&& $address['city'] > 0
			&& $address['district'] > 0
			&& $address['address'] != ''
			&& $address['attribute'] != ''
		){
            $this->logicUserAddress->setDatas($address);
            $result = $this->logicUserAddress->save();  //保存地址
            if($result === false){
                $error = $this->logicUserAddress->getError();
                $this->error($error);
            }
			$this->setRequest('address_id',(isset($result['address_id']) ? intval($result['address_id']) : 0));  //设置提交了的地址ID
		}else{  //没提交地址、没选择地址，注销保存的旧地址
			session('new_consignee',null);
			session('default_address_id',null);
		}
		
		//检查优惠券号码
		$bonus_sn = I('request.bonus_sn','','trim');
		if(!empty($bonus_sn)){
			$bonus_info = $this->checkBonus(true);
			if(!is_array($bonus_info)){
				$this->error($bonus_info);
			}
			$this->setRequest('bonus_type',$bonus_info['type_id']);  //设置提交了的优惠券类型
		}

		//new2.3g.chinaskin.cn/Home/OnlinePayment/quickAggregate.json?bonus_sn=1407281547&payment_id=4&consignee=程赐明&mobile=13711458538&province=6&city=76&district=693&town=29033&address=测试的地址&attribute=公司
		
		$total = $this->aggregate(true,$address['province']);  //获取总金额
		
		$this->success($total);
	}

    /**
     * 提交订单 - 文章页面支付
     */
	public function quickOrder(){
		$code = I('code','','trim');
		if (!Send::checkMobileCode($code)) {
			$this->error('验证码不正确');
		}
		$this->createOrder();  //调用创建订单
	}

    /**
     * 获取购物车勾选的商品 -  - 确认订单页面
     */
	public function getGoodsList(){
		//获取购物车商品
        $this->logicPayment->setData('is_show', 1);
        $this->logicPayment->setData('get_image', 1);
		$cart_goods = $this->logicPayment->getCartGoods();

		$list = array();
		if(!empty($cart_goods)){
			foreach($cart_goods as $value){
				$list[] = array(
					'rec_id'=>$value['rec_id'],
					'is_gift'=>$value['is_gift'],
					'goods_id'=>$value['goods_id'],
					'goods_price'=>$value['goods_price'],
					'goods_number'=>$value['goods_number'],
					'goods_sn'=>$value['goods_sn'],
					'goods_name'=>$value['goods_name'],
					'market_price'=>$value['market_price'],
					'amount'=>$value['amount'],
					'goods_thumb'=>C('domain_source.img_domain').$value['goods_thumb'],
					'goods_img'=>C('domain_source.img_domain').$value['goods_img'],
					'original_img'=>C('domain_source.img_domain').$value['original_img'],
					'discount'=>$value['discount'],
					'formated_discount'=>$value['formated_discount'],
					'formated_market_price'=>$value['formated_market_price'],
					'formated_goods_price'=>$value['formated_goods_price'],
					'formated_amount'=>$value['formated_amount'],
				);
			}
		}
		$this->success($list);
	}
	
	/*
	*	获取当前购物车可用与不可用的优惠券 - 确认订单页面
	*	@Author 9009123 (Lemonice)
	*	@return exit & Json
	*/
	public function getBonusList(){
		$page = 1;  //不分页了
        $pageSize = 0;  //不分页了

		$BonusObject = new BonusLogic();
		if($page <= 1){
            $BonusObject->setData('page', $page);
            $BonusObject->setData('page_size', $pageSize);
			$list = $BonusObject->getAvailableBonus(true);  //获取当前会员可用的购物车优惠券、以及不可用的优惠券
		}else{
			$list = array();  //不分页了，第二页开始为空
		}
		
		$result = array(
			'page'=>1,
			'pageSize'=>$pageSize,
			'total'=>count($list),
			'pageTotal'=>1,
			'list'=>$list,
		);
		
		if(isset($result['list']) && !empty($result['list'])){
			$list = array();
			foreach($result['list'] as $bonus){
				$list[] = array(
					'type_id'=>$bonus['type_id'],
					'type_name'=>$bonus['type_name'],
					'type_money'=>$bonus['type_money'],
					'count'=>$bonus['count'],
					'is_payonline_discount'=>$bonus['is_payonline_discount'],
				);
			}
			$result['list'] = $list;
		}else{
			$result['page'] = 1;
			$result['pageSize'] = $pageSize;
			$result['total'] = 0;
			$result['pageTotal'] = 1;
			$result['page'] = 1;
			$result['page'] = 1;
			$result['list'] = array();
		}
		$this->success($result);
	}
	
	/*
	*	获取当前购物车可用与不可用的优惠券 - 确认订单页面
	*	@Author 9009123 (Lemonice)
	*	@param  true or false  $is_return  是否返回结果，否则终端输出
	*	@return exit & Json
	*/
	public function checkBonus($is_return = false){
		$bonus_sn = I('request.bonus_sn','','trim');
		if($bonus_sn == ''){
			if($is_return === false){
				$this->error('请输入优惠券编码！');
			}else{
				return '请输入优惠券编码！';
			}
		}
		$BonusObject = new BonusLogic();
		if($BonusObject->checkBonusToBonusSn($bonus_sn)){  //校验优惠券，校验通过
            $result = $BonusObject->getCoupon();
			//查看是否实物券
			if(isset($result['coupon_type']) && $result['coupon_type'] == 2 && !empty($result['front_actid'])){
                if(is_array($result['front_actid']) && !empty($result['front_actid'])){  //如果是实物券，并且有绑定活动，则应该
                    $favourableActivity = new FavourableActivityLogic();
                    $actList = $favourableActivity->getActivityGoodsListToActId($result['front_actid']);

					$CartModel = D('Cart');
                    $gift_act_id = array_keys($actList);  //实物券活动id
                    foreach($actList as $act_id => $act){
                        //TODO....添加进购物车  ....
                        /*====== 套装 ========*/
                        foreach($act['gift_package'] as $gift){                         //套装
                             $CartModel->addTempGift(array(  //添加商品到购物车
                                'goods_id'=>$gift['id'],
                                'goods_price'=>$gift['price'],
                                'goods_number'=>$gift['num'],
                                'extension_code'=>'package_buy',                //套装  'extension_code'=>'package_buy'| 单品 'extension_code'=>''
                                'is_gift'=>$act_id
                             ));
                        }
                        /*====== 单品 ========*/
                        foreach($act['gift'] as $gift){                         //套装
                           //TODO...
                        }
                    }
					//记录这些商品，如果有取消优惠券功能，则需要删除购物车对应的商品
					//session('gift_bonus',array_unique($gift_act_id));
                }
            }
			
			session('use_bonus_id.' . $result['type_id'], $result['bonus_id']);  //把优惠券ID记录到session
			
			//优惠券详情
			$data = array(
				'bonus_id'=>$result['bonus_id'],
				'type_id'=>$result['type_id'],
				'type_name'=>$result['type_name'],
				'type_money'=>$result['type_money'],
				'coupon_type'=>$result['coupon_type'],
				'is_payonline_discount'=>$result['is_payonline_discount'],
				'count'=>1,
			);
			
			if($is_return === false){
				$this->success($data);
			}else{
				return $data;
			}
		}else{
			if($is_return === false){
				$this->error($BonusObject->getError());
			}else{
				return $BonusObject->getError();
			}
		}
	}

    /**
     * 获取订单总额+运费等优惠信息，总计、合计 - 确认订单页面
     * @param bool $is_return  是否返回结果
     * @param int $province  省份，计算邮费用的
     * @return array
     */
	public function aggregate($is_return = false, $province = 0){
		//获取购物车商品
		$cartGoods = $this->logicPayment->getCartGoods();
		
		$bonusType = I('request.bonus_type',0,'intval');  //红包类型
		$paymentId = I('request.payment_id',0,'intval');  //选择了什么支付
		$addressId = I('request.address_id',0,'intval');  //地址ID，等于0的话就是新添加的地址，从session获取

		$is_wechat = isCheckWechat();
		if($is_wechat == true && $paymentId == 4){  //微信不支持支付宝
			$this->error('请选择支付方式。');
		}
		
		//$bonus_id = session('use_bonus_id.' . $bonus_type);
		//$bonus_id = $bonus_id ? $bonus_id : 0;

        $this->logicPayment->setData('cart_goods', $cartGoods);  //购物车商品
        $this->logicPayment->setData('address_id', $addressId);  //选择的地址ID
        $this->logicPayment->setData('province', $province);  //省份
        $this->logicPayment->setData('payment_id', $paymentId);  //选择的支付方式
        $this->logicPayment->setData('bonus_type', $bonusType);  //优惠券类型

		$total = $this->logicPayment->getOrderFree();
		$total['token'] = $this->logicPayment->token();  //获取token，提交订单时候用的，避免重复点击

		//保存这些数据到session
		session('payment_data',array(
			'bonus_type'=>($bonusType > 0 ? $bonusType : 0),  //有优惠券才保存
			'payment_id'=>$paymentId,
			'address_id'=>$addressId,
		));
		
		if($is_return === false){
			$this->success($total);
		}else{
			return $total;
		}
	}

    /**
     * 提交订单 - 创建订单 -  - 确认订单页面
     */
	public function createOrder(){
        //订单备注
		$remark = I('request.remark','','trim');
        $this->logicPayment->setData('remark', $remark);

        //来源地址
        $sourceUrl = I('source_url','','trim');
        $this->logicPayment->setData('source_url', $sourceUrl);

		//校验token，避免重复提交
		$token = I('request.token','','trim');
        $this->logicPayment->setData('token', $token);
        $cartGoods = $this->logicPayment->checkCreateOrder();
		if($cartGoods === false){
			$this->error($this->logicPayment->getError());
		}
        //创建订单基础数据
        $paymentData = session('payment_data');

        $this->logicPayment->setData('cart_goods', $cartGoods);  //购物车商品
        $this->logicPayment->setData('address_id', $paymentData['address_id']);  //选择的地址ID
        $this->logicPayment->setData('payment_id', $paymentData['payment_id']);  //选择的支付方式
        $this->logicPayment->setData('bonus_type', $paymentData['bonus_type']);  //优惠券类型
        $order = $this->logicPayment->createOrder();
        if($order === false){
            $this->error($this->logicPayment->getError());
        }

		//取得支付信息，生成支付代码
		if ($order['order_amount'] > 0 && $order['pay_id'] != 1){  //非货到付款
            $Payment = new \Common\Logic\Pay();
            $Payment->setData('paycode', $order['code']);
            $Payment->setData('pay_type', 'pay');
            $Payment->setData('paytype', 'getCode');
            $Payment->setData('order_sn', $order['order_sn']);
            $Payment->setData('body', '订单支付('.$order['order_sn'].')');
            $Payment->setData('order_amount', $order['order_amount']);
            $order["content"] = $Payment->handle();

            if($order["content"] === false){
                $error = $Payment->getError();
                $this->error($error);
            }

            if(!$order["content"]){
                $this->error('您当前的站点不支持使用该支付方式，请重新选择！');
            }

            //将支付信息写入支付对账数据库
            D('PayInfo')->add(array(
                'site_id'=>$order['site_id'],
                'pay_id'=>$order['pay_id'],
                'name'=>($order["consignee"] ? $order["consignee"] : ''),
                'order_sn'=>$order["order_sn"],
                'order_amount'=>$order["order_amount"],
                'source'=>2,
                'add_time'=>Time::gmtime(),
            ));
		}
		//使用优惠券		
		if ($order['bonus_id'] > 0 && $order['order_amount'] > 0){
            $BonusObj = new BonusLogic();
			$result = $BonusObj->useBonus($order['bonus_id'], $order['order_id'], $order['site_id'], $this->user_id);  //使用优惠券
			if(false === $result){
				$this->error($BonusObj->getError());
			}
		}
		
		/* 清空购物车 ,两种情况，登录了，保留，不登录，刷新之后清除*/
		$this->logicCart->cleanSelect();  //删除购物车选中的商品
		
		//记录当前所下的订单，用于在线支付时读取订单信息
		session('pay_online_order_sn', $order['order_sn']);
	
		//new_consignee
		session('use_bonus_id',NULL);
		//session('cart_statistics',NULL);
		session('gift_bonus',NULL);
		session('payment_data',NULL);
		
		session('client_ip',NULL);
		cookie('error',NULL);

		$data = array(
			'order_id'=>$order['order_id'],
			'order_sn'=>$order['order_sn'],
			'amount'=>$order['order_amount'],
			'discount'=>$order['discount'],
			'bonus'=>$order['bonus'],
			'shipping_fee'=>$order['shipping_fee'],
			'payment_id'=>(isset($order['bank_id']) ? $order['bank_id'] : $order['pay_id']),
			'payment_name'=>$order['pay_name'],
			'remark'=>$order['postscript'],
			'user_id'=>$order['user_id'],
			'add_time'=>$order['add_time'],
			'add_date'=>Time::localDate('Y-m-d H:i:s',$order['add_time']),
			'content'=>$order['content'],
		);
		
		$this->success($data);
	}

    /**
     * 检测是否可以使用微信支付
     */
	public function checkWechatPay(){
		$data = array('result'=>0);
		$result = isCheckWechat();
		if($result == false){  //不是微信打开网页
			$this->success($data);
		}
		$data['result'] = 1;  //支持微信支付
		$this->success($data);
	}

    /**
     * 暂时不使用本控制器默认方法，预留
     */
	public function index(){
		send_http_status(404);
	}
}