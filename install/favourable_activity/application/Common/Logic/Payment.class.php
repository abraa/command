<?php
/**
 * ====================================
 * 下单、支付相关业务处理
 * ====================================
 * Author: 9009123 (Lemonice)
 * Date: 2017-09-14 15:15
 * ====================================
 * File: Payment.class.php
 * ====================================
 */
namespace Common\Logic;
use Common\Extend\Time;
use Common\Extend\PhxCrypt;

class Payment extends LogicData{
    /**
     * 定义支付方式
     * @var array
     */
    public static $paymentData = array(
        4=>array(
            'code'=>'Alipay',
            'pay_name'=>'支付宝',
        ),
        6=>array(  //暂不支持
            'code'=>'chinaskinpay',
            'pay_name'=>'钱包支付',
        ),
        7=>array(
            'code'=>'Tenpay',
            'pay_name'=>'财付通',
        ),
        8=>array(
            'code'=>'KuaiQian',
            'pay_name'=>'快钱支付',
        ),
        18=>array(
            'code'=>'Wechatpay',
            'pay_name'=>'微信支付',
        ),
    );
    /**
     * 用于储存购物车业务层对象
     * @var null
     */
    private $logicCart = NULL;
    /**
     * 用于储存用户收货地址的业务层对象
     * @var null
     */
    private $logicUserAddress = NULL;
    /**
     * 会员ID，没登录则为0
     * @var int
     */
    private $userId = 0;
    public function __construct() {
        parent::__construct();
        $this->CartModel = D('Common/Home/Cart');
        $this->userId = $this->CartModel->getUser('user_id');
        $this->logicCart = new Cart();  //购物车业务逻辑层
        $this->logicUserAddress = new UserAddress();  //用户收货地址业务逻辑层
    }

    /**
     * 获取对应支付码的支付ID
     * @param string $payCode
     * @return int|string
     */
    public static function getPayId($payCode = ''){
        if(empty($payCode)){
            return 0;
        }
        $paymentData = self::$paymentData;
        foreach($paymentData as $payId=>$data){
            if($data['code'] == $payCode){
                return $payId;
            }
        }
        return 0;
    }

    /**
     * 获取购物车选中的商品
     * @return mixed
     */
    public function getCartGoods(){
        $isShow = intval($this->getData('is_show'));  //是否为显示到页面，如果是显示到页面，字段会不同，1=显示页面，0=仅获取数据
        $getImage = intval($this->getData('get_image'));  //是否获取缩略图、详情图链接，1=获取，0=不获取
        $this->logicCart->setData('select','1');
        $this->logicCart->setData('is_show',$isShow);
        $this->logicCart->setData('get_image', $getImage);

        $cartList = $this->logicCart->getList();
        if($cartList === false){
            $error = $this->logicCart->getError();
            $this->setError($error);
            return false;
        }
        if(empty($cartList)){
            $this->setError('您没勾选购物车商品');
            return false;
        }
        $GoodsActivityModel = D('Common/Home/GoodsActivity');
        foreach($cartList as $key=>$value){
            //获取商品图
            if($getImage > 0) {
                $image = $this->getGoodsImageInfo($value);
                if(!empty($image)){
                    $cartList[$key] = array_merge($cartList[$key], $image);
                }
            }
            if(isset($value['extension_code']) && $value['extension_code'] == 'package_buy'){  //是套装
                $cartList[$key]['package_id'] = $value['goods_id'];  //套装ID
                $cartList[$key]['goods_id'] = $GoodsActivityModel->getGoodsId($value['goods_id']);  //把套装ID换成绑定的商品ID
            }
        }
        return $cartList;
    }

    /**
     * 解析离线支付的加密串
     * @param string $param
     * @return array
     */
    public function decodeOrderString($param = ''){
        $params = array();
        if(empty($param)){
            return $params;
        }
        if(isCheckWechat()){  //检查是否微信
            $param = str_replace(' ','+',$param);
        }
        $param = base64_decode(rawurldecode($param));
        $queryParts = explode('&', $param);
        foreach ($queryParts as $value){
            $item = explode('=', $value);
            $params[$item[0]] = $item[1];
        }
        return $params;
    }

    /**
     * 统计购物车总金额、优惠等
     * @return array
     */
    public function getOrderFree(){
        $cartGoods = $this->getData('cart_goods');  //购物车商品
        $province = intval($this->getData('province'));  //省份ID，如果有地址ID可不传或者传0
        $bonusType = intval($this->getData('bonus_type'));  //优惠券类型

        //获取商品的应付总金额、市场价总金额、会员折扣金额（未对会员折扣扣除应付金额）
        $total = $this->getGoodsPriceTotal($cartGoods);
        //是否包邮 (1.在线支付 2.货到付款)
//        $total = $this->CartModel->totalCart($cartGoods);               // 购物车商品信息统计
//      返回示例 array("goods_price"=>100, 'market_price'=>2000,'have_package'=>1,'have_gift'=>1,'goods_number'=>10)

        $total  = array(
            'goods_price'      => $total['goods_price'],
            'market_price'     => $total['market_price'],
            'discount'         => 0,
            'shipping_fee'     => 0,
            'bonus'            => 0,
            'pay_fee'          => 0,  //在线支付的手续费等费用
            'pay_fee_discount' => 0,  //在线支付优惠金额
            'have_package'     => $this->havePackage($cartGoods),  //是否包含有套装
            'have_gift'        => $this->haveGift($cartGoods),  //是否包含有活动商品
            'member_discount'  => $total['member_discount'],
        );

        /* 优惠券 */
        $bonus = array('free_postage'=>0);  //free_postage：是否使用免邮优惠券
        if (intval($bonusType) > 0){
            $bonus = $this->checkBonus($total);
            $total = $bonus['total'];
        }

        /* 计算邮费 */
        if($bonus['free_postage'] != 1){  //free_postage=1: 使用免邮券
            $orderAmount = $total['goods_price'] - $total['member_discount'] - $total['bonus'];  //总应付金额
            $total['shipping_fee'] = $this->getShippingFee($orderAmount, $province);  //计算邮费
        }

        /* 检查是否有商品是包邮的 */
        if(!empty($cartGoods)){
            foreach($cartGoods as $goods){
                $goodsAttrId = empty($goods['goods_attr_id']) ? empty($goods['goods_attr_id']) : unserialize($goods['goods_attr_id']);
                if(isset($goodsAttrId['shipping_free'])){
                    $paymentId = $this->getData('payment_id');
                    //是否包邮 (1.在线支付 2.货到付款)
                    if((in_array(1, $goodsAttrId['shipping_free']) && $paymentId != 1) || in_array(2, $goodsAttrId['shipping_free']) && $paymentId == 1){
                        $total['shipping_fee'] = 0;  //免邮
                        break;
                    }
                }
            }
        }

        /* 计算订单总应付金额 */
        $total['amount'] = $total['goods_price'] - $total['discount'] + $total['shipping_fee'];

        // 减去优惠券金额
        $useBonus = min($total['bonus'], $total['goods_price']); // 使用优惠券的金额, 优惠券最多能支付的金额为商品总额
        $total['bonus']   = $useBonus;  //使用了多少钱的优惠券
        $total['amount'] -= $useBonus; // 还需要支付的订单金额

        /* 支付费用 */
        $total['pay_fee_discount'] = 0;  //在线支付优惠多少钱

        /* 如果是在线支付则减免xx元 */
        $total = $this->onlinePayFee($total);

        $total['amount'] -= $total['member_discount']; //订单总额减去会员优惠

        /* 可以得到的积分 */
        $total['will_get_integral'] = $total['goods_price'] - $total['discount'] - $total['member_discount'] - $total['integral_money'] - $total['bonus'];

        //登录会员生日两倍积分
        $multiple = D('Common/Home/UserInfo')->checkBirthdayIntegralMultiple();  //检查今天是否生日，如果是，则会多倍获得积分
        $total['will_get_integral'] = intval($total['will_get_integral'] * max($multiple, 1));  //两倍积分

        return $total;
    }

    /**
     * 获取套装或者单品的商品图
     * @param array $goods
     * @return mixed
     */
    private function getGoodsImageInfo($goods = array()){
        if(isset($goods['extension_code']) && $goods['extension_code'] == 'package_buy'){  //是套装
            $image = D('Common/Home/GoodsActivity')->getImage($goods['goods_id']);  //获取商品图
        }else{  //单品
            $image = D('Common/Home/Goods')->getImage($goods['goods_id']);  //获取商品图
        }
        return !empty($image) ? $image : array();
    }

    /**
     * 在线支付减多少钱
     * @param array $total
     * @return array
     */
    private function onlinePayFee($total = array()){
        $paymentId = $this->getData('payment_id');  //选择的支付方式
        if (ONLINE_PAYMENT_DISCOUNT > 0 && $paymentId != 1){  //不是货到付款，减xx元
            $total['discount'] += ONLINE_PAYMENT_DISCOUNT_AMOUNT;

            //暂时去掉在线支付优惠xx元的
            $total['amount']   -= ONLINE_PAYMENT_DISCOUNT_AMOUNT;
            $total['pay_fee_discount'] = ONLINE_PAYMENT_DISCOUNT_AMOUNT;
        }else{
            $total['pay_fee_discount'] = 0;
        }
        return $total;
    }

    /**
     * 检查优惠券金额
     * @param array $total
     * @return array
     */
    private function checkBonus($total = array()){
        $cartGoods = $this->getData('cart_goods');  //购物车商品
        //$paymentId = $this->getData('payment_id');  //选择的支付方式
        $bonusType = $this->getData('bonus_type');  //优惠券类型

        if($bonusType <= 0 || empty($cartGoods)){
            return $total;
        }
        $BonusObj = new BonusLogic($cartGoods);
        $bonusResult = NULL;
        if($BonusObj->checkBonus($bonusType)){  //校验优惠券
            $bonusResult = $BonusObj->getCoupon();
        }
        if(!empty($bonusResult)){               //如果返回了数组，则是校验通过
            $total['bonus'] = $bonusResult['type_money'];
        }else{
            $total['bonus'] = 0;
        }
        return array('total'=>$total, 'free_postage'=>(isset($bonusResult['free_postage']) ? $bonusResult['free_postage'] : 0));  //free_postage: 是否免邮优惠券
    }

    /**
     * 获取邮费
     * @param int $orderAmount  订单总应付金额
     * @param int $province  省份ID，可不传，此参数是为了兼容软文推广的下单
     * @param int $shippingFee  基础邮费
     * @param bool $countAmount  是否根据价格减免基础邮费
     * @return int
     */
    public function getShippingFee($orderAmount = 0, $province = 0, $shippingFee = -1, $countAmount = true){
        $isShipping = $this->CartModel->haveShippingGoods();  //检查购物车是否有包邮商品
        if($countAmount == true && $orderAmount >= 200 || $isShipping === true){
            $shippingFee = 0;
        }
        $addressId = $this->getData('address_id');  //地址ID，如果是新添加的没登录的可以传0
        $this->logicUserAddress->setData('province', $province);
        $this->logicUserAddress->setData('address_id', $addressId);
        $this->logicUserAddress->setData('shipping_fee', $shippingFee);
        $shippingFee = $this->logicUserAddress->calculateFee();  //计算费用

        return ($shippingFee<0 ? 0 : $shippingFee);
    }

    /**
     * 检查购物车是否有活动商品
     * @param array $cartGoods
     * @return int
     */
    private function haveGift($cartGoods = array()){
        if(empty($cartGoods)){
            return 0;
        }
        foreach ($cartGoods as $val){
            if($val['is_gift'] > 0){
                return 1;
            }
        }
        return 0;
    }

    /**
     * 检查购物车是否有套装存在
     * @param array $cartGoods
     * @return int
     */
    private function havePackage($cartGoods = array()){
        if(empty($cartGoods)){
            return 0;
        }
        foreach ($cartGoods as $val){
            if($val['extension_code'] == 'package_buy' || $val['extension_code'] == 'package_goods'){
                return 1;
            }
        }
        return 0;
    }

    /**
     * 获取商品的应付总金额、市场价总金额、会员折扣金额（未对会员折扣扣除应付金额）
     * @param array $cartGoods
     * @param array $total
     * @return array
     */
    private function getGoodsPriceTotal($cartGoods = array(), $total = array()){
        $total['market_price'] = 0;
        $total['goods_price'] = 0;
        $total['member_discount'] = 0;
        $total['goods_price_formated'] = priceFormat(0, false);
        $total['market_price_formated'] = priceFormat(0, false);
        if(empty($cartGoods)){
            return $total;
        }
        //会员折扣，会员：9.5折  --  废弃
        $preferential = $this->getMemberDiscount();  //获取会员打多少折扣
        foreach ($cartGoods as $val){
            $total['market_price'] += $val['market_price'] * $val['goods_number'];  //市场价总和
            $total['goods_price'] += $val['goods_price'] * $val['goods_number'];  //商品应付金额总和
            $total['member_discount'] += intval($val['is_gift']) == 0 ? $val['goods_price'] * $val['goods_number'] * $preferential : 0;  //会员的总优惠金额
        }
        //去掉会员折扣金额的小数点
        $total['member_discount'] = round(floor($total['member_discount']));  //会员优惠四舍五入
        $total['goods_price_formated']  = priceFormat($total['goods_price'], false);
        $total['market_price_formated'] = priceFormat($total['market_price'], false);
        return $total;
    }

    /**
     * 获取会员折扣
     * @return int
     */
    private function getMemberDiscount(){
        return 0;  //会员暂时不做折扣,返回的是会员打折的力度，比如：95折返回0.05
    }

    /**
     * 创建订单
     * @return array
     */
    public function createOrder(){
        $cartGoods = $this->getData('cart_goods');  //购物车商品
        $paymentId = intval($this->getData('payment_id'));  //选择的支付方式
        $remark = trim($this->getData('remark'));  //优惠券类型
        $addressId = $this->getData('address_id');  //地址ID

        //统计订单总价等相关数据
        $total = $this->getOrderFree();

        $realIp = get_client_ip();

        $order = array(
            'order_amount'    => $total['amount'],  //应付金额
            'shipping_fee'    => $total['shipping_fee'],  //邮费，0=包邮
            'shipping_id'     => 15,  //intval($_POST['shipping']),指定配送方式,为EMS edit by lxm
            'shipping_type'   => 1,
            'pay_id'          => $paymentId,  //支付平台，1=货到付款
            'pay_fee'         => $total['pay_fee'],  //支付平台费用
            'payment_discount'=> $total['pay_fee_discount'],  //支付平台费用
            'pack_id'         => 0,  //包装
            'card_id'         => 0,  //卡片、贺卡
            'card_message'    => '',  //卡片文字
            'surplus'         => 0.00,  //余额
            'integral'        => 0,  //使用的积分
            'integral_money'  => isset($total['integral_money']) ? $total['integral_money'] : 0,  //使用积分抵消的金额
            'bonus'           => isset($total['bonus']) ? intval($total['bonus']) : 0,  //优惠券金额
            'need_inv'        => 0,
            'inv_type'        => '',
            'inv_payee'       => '',
            'inv_content'     => '',
            'postscript'      => htmlspecialchars($remark),  //订单备注
            'how_oos'         => '',
            'need_insure'     => 0,  //保险
            'user_id'         => $this->userId,  //用户
            'add_time'        => Time::gmtime(),  //下单时间
            'order_status'    => OS_UNCONFIRMED,  //订单状态
            'shipping_status' => SS_UNSHIPPED,  //物流状态
            'pay_status'      => PS_UNPAYED,  //支付状态
            'agency_id'       => 0,  //收货地址所在的办事处ID
            'ip_address' 	  => $realIp,  //客户端IP地址
            'goods_amount'    => $total['goods_price'],  //商品总金额
            'discount'        => $total['discount']+$total['member_discount'],  //加上会员折扣
            'tax'             => 0,  //税收
            'parent_id'       => 0,
            'divide_region'   => '广州地区手机商城下单',
            'kefu'            => '手机商城下单',
        );

        //获取支付信息
        $paymentData = $this->getPaymentData();
        $order = array_merge($order, $paymentData);

        //获取订单的来源等相关数据
        $data = $this->getOrderSource();
        if(!empty($data)){
            $order = array_merge($order, $data);
        }

        //判断是否是通过新增地址而添加的新用户，提示初始账号密码
        $consignee = $this->logicUserAddress->getUserAddress($addressId);
        if(empty($consignee)){
            $this->setError('请填写收货地址');
            return false;
        }

        //（订单归属问题）
        if($this->userId == 0 && $order['pay_id']==1){  //未登陆，并且是货到付款
            $order = $this->registerUser($order, $consignee);
        }
        //用户ID，可能有登录，可能是新注册的
        $order['user_id'] = $this->userId;

        /* 收货人信息 */
        if(!empty($consignee)){
            $order = array_merge($order, $consignee);
        }

        /* 如果订单金额为0，修改订单状态为已确认、已付款 */
        if ($order['order_amount'] <= 0){
            $order['order_status'] = OS_CONFIRMED;
            $order['confirm_time'] = Time::gmtime();
            $order['pay_status']   = PS_PAYED;
            $order['pay_time']     = Time::gmtime();
            $order['order_amount'] = 0;
        }

        //获取广告相关的数据
        $order = $this->getFromAdvert($order);

        //生成唯一的订单号
        $order['order_sn'] = $this->getOrderSn();

        //获取使用的优惠券ID
        $order['bonus_id'] = $this->getBonusId();

        //加密手机号码
        $order['mobile'] = isset($order['encode_mobile']) ? $order['encode_mobile'] : PhxCrypt::phxEncrypt($order['mobile']);

        $OrderInfoModel = D('Common/Home/OrderInfo');
        $OrderInfoCenterModel = D('Common/Home/OrderInfoCenter');

        //开启事务
        $OrderInfoModel->startTrans();
        $OrderInfoCenterModel->startTrans();

        //插入到自身站点的订单表
        $order['order_id'] = $OrderInfoModel->add($order);
        if($order['order_id'] === false){
            $this->setError('创建订单失败，请重试！');
            return false;
        }
        $order['site_id'] = C('SITE_ID');
        //插入到会员中心的订单表
        $insert_id = $OrderInfoCenterModel->add($order);
        if($insert_id === false){
            $OrderInfoModel->rollback();  //事务回滚
            $this->setError('创建订单失败，请重试！');
            return false;
        }

        $GoodsActivityModel = D('Common/Home/GoodsActivity');
        $OrderGoodsCenterModel = D('Common/Home/OrderGoodsCenter');  //ucenter数据库订单商品表
        $OrderGoodsModel = D('Common/Home/OrderGoods');  //当前站点数据库订单商品表

        /* 插入订单商品 */
        $addRollback = false;
        foreach($cartGoods as $k=>$row){
            $data = array(
                'order_id'=>$order['order_id'],
                'order_sn'=>$order['order_sn'],
                'goods_id'=>(isset($row['package_id']) ? $row['package_id'] : $row['goods_id']),
                'goods_name'=>($row['goods_name'] ? $row['goods_name'] : ''),
                'goods_sn'=>($row['goods_sn'] ? $row['goods_sn'] : ''),
                'goods_number'=>($row['goods_number'] ? $row['goods_number'] : 1),
                'market_price'=>($row['market_price'] ? $row['market_price'] : 0),
                'goods_price'=>($row['goods_price']>0 ? $row['goods_price'] : 0),
                'goods_attr'=>($row['goods_attr'] ? $row['goods_attr'] : ''),
                'is_real'=>($row['is_real'] ? $row['is_real'] : 1),
                'extension_code'=>($row['extension_code'] ? $row['extension_code'] : ''),
                'parent_id'=>($row['parent_id'] ? $row['parent_id'] : 0),
                'is_gift'=>$row['is_gift'],
                'site_id'=>$order['site_id'],
            );
            $result = $OrderGoodsCenterModel->add($data);  //插入商品数据到ucenter数据库
            if($result === false){
                $addRollback = true;
                break;
            }
            $result = $OrderGoodsModel->add($data);  //插入商品数据到当前站点数据库
            if($result === false){
                $addRollback = true;
                break;
            }

            //检查如果是套装，则回去套装商品加入到订单商品
            if($data['extension_code'] == 'package_buy'){  //是套装
                $package = $GoodsActivityModel->getPackageInfo(0, $data['goods_id']);
                if(isset($package['package_goods']) && !empty($package['package_goods'])){
                    $rollback = false;
                    foreach($package['package_goods'] as $key=>$children){
                        if($children['goods_name']){
                            $package_goods = array(
                                'order_id'=>$data['order_id'],
                                'order_sn'=>$data['order_sn'],
                                'goods_id'=>$children['goods_id'],
                                'goods_name'=>$children['goods_name'],
                                'goods_sn'=>$children['goods_sn'],
                                'goods_number'=>$children['goods_number'],
                                'market_price'=>$children['market_price'],
                                'goods_price'=>$children['shop_price'],
                                'goods_attr'=>'',
                                'is_real'=>$children['is_real'],
                                'extension_code'=>'package_goods',
                                'parent_id'=>($children['package_id'] ? $children['package_id'] : 0),
                                'is_gift'=>$row['is_gift'],
                                'site_id'=>$data['site_id'],
                            );
                            $result = $OrderGoodsCenterModel->add($package_goods);  //插入商品数据到ucenter数据库
                            if($result === false){
                                $rollback = true;
                                break;
                            }
                            $result = $OrderGoodsModel->add($package_goods);  //插入商品数据到当前站点数据库
                            if($result === false){
                                $rollback = true;
                                break;
                            }
                        }
                    }
                    if($rollback === true){
                        $OrderInfoModel->rollback();  //事务回滚
                        $OrderInfoCenterModel->rollback();  //事务回滚
                        $this->setError('订单商品有误，请重试！');
                        return false;
                    }
                }
            }
        }
        if($addRollback === true){
            $OrderInfoModel->rollback();  //事务回滚
            $OrderInfoCenterModel->rollback();  //事务回滚
            $this->setError('订单商品有误，请重试！');
            return false;
        }
        $OrderInfoModel->commit();  //提交事务
        $OrderInfoCenterModel->commit();  //提交事务

        return $order;
    }

    /**
     * 未登录的订单自动注册会员
     * @return array
     */
    public function addNewMenber(){
        //判断是否是通过新增地址而添加的新用户，提示初始账号密码
        $newConnsignee = session('new_consignee');
        if(!empty($newConnsignee)){   //收获地址，初始注册
            $mobileSource = isset($newConnsignee['mobile_source']) ? $newConnsignee['mobile_source'] : $newConnsignee['mobile'];  //明文手机号码
            $password = substr($mobileSource,-6);  //获取手机号码后六位做为密码
            $data = array(
                'mobile'=>(isMobile($mobileSource) ? \Common\Extend\PhxCrypt::phxEncrypt($mobileSource) : $mobileSource),
                'sms_mobile'=>$mobileSource,
                'ip'=>get_client_ip(),
                'email'=>isset($newConnsignee['email']) ? $newConnsignee['email'] : '',
                'source'=>$_SERVER['HTTP_HOST'],
                'sex'=>0,
                'password'=>$password,
            );
            //验证存在或初始注册，存在，则返回user_id,不存在，则返回初始注册的user_id和随机密码
            $result = D('Common/Home/Users')->addNewMember($data);
            $userId = isset($result['user_id']) ? $result['user_id'] : (isset($result['new_user_id']) ? $result['new_user_id'] : 0);

            if($userId > 0){
                //保存地址到会员中心（user_id：为刚初始注册或以新增地址中的手机号的user_id）
                D('Common/Home/UserAddress')->saveAddress($userId, $newConnsignee);

                //更新本地订单user_id
                $payOnlineOrderSn = session('pay_online_order_sn');    //新增订单的order_sn
                if($payOnlineOrderSn != ''){
                    D('OrderInfo')->where("order_sn = '$payOnlineOrderSn'")->save(array('user_id'=>$userId));
                    //更新会员中心订单中的user_id
                    D('OrderInfoCenter')->where("order_sn = '$payOnlineOrderSn'")->save(array('user_id'=>$userId));
                }

                $result['newUser'] = array(
                    'new_user'=>substr($newConnsignee['mobile'],0,-4) . '****',
                    'password'=>substr($password,0-4) . '****',
                );
                return $result;
            }
        }
        return array();
    }

    /**
     * 获取使用的优惠券ID
     * @return int
     */
    private function getBonusId(){
        $bonusType = intval($this->getData('bonus_type'));  //优惠券类型
        $bonusId = intval(session('use_bonus_id.' . $bonusType));  //优惠券ID如果有使用
        if($bonusId <= 0 && $bonusType > 0){  //自身帐号有的优惠券，或者可自动获取的优惠券类型
            $bonusId = D('Common/Home/UserBonusCenter')->getUserBonusId($bonusType);
        }
        return $bonusId;
    }

    /**
     * 获取支付方式信息
     * @return array
     */
    public function getPaymentData(){
        $paymentId = intval($this->getData('payment_id'));  //选择的支付方式
        $order = array();
        if($paymentId != 1){
            $paymentData = self::$paymentData;
            if(isset($paymentData[$paymentId])){
                $order['code'] = $paymentData[$paymentId]['code'];
                $order['pay_name'] = $paymentData[$paymentId]['pay_name'];
            }else{  //默认网银支付
                $order['code'] = 'tenpay';
                $order['pay_name'] = '网银支付';
                $order["bank_id"] = $order['pay_id'];  //网银识别
                $order['pay_id'] = 7;  //  > 10 是网银，强制使用财付通
            }
        }else{
            $order['code'] = '';
            $order['pay_name'] = '货到付款';
        }
        return $order;
    }

    /**
     * 注册用户 - 在线下单用户自动注册，并且默认保存临时地址到用户
     * @param array $consignee
     * @return bool
     */
    private function registerUser($consignee = array()){
        $data = array(
            'mobile'=>PhxCrypt::phxEncrypt($consignee['mobile']),
            'sms_mobile'=>$consignee['mobile'],
            'ip'=>get_client_ip(),
            'email'=>isset($consignee['email']) ? $consignee['email'] : '',
            'source'=>$_SERVER['HTTP_HOST'],
            'sex'=>0,
            'password'=>substr($consignee['mobile'],-6),  //获取手机号码后六位做为密码
        );
        $UserObject = new \Common\Logic\User();
        $UserObject->setDatas($data);
        //验证存在或初始注册，存在，则返回user_id,不存在，则返回初始注册的user_id和随机密码
        $registerResult = $UserObject->register();
        if($registerResult === false){
            $this->setError($UserObject->getError());
            return false;
        }

        $this->userId = isset($registerResult['user_id']) ? $registerResult['user_id'] : (isset($registerResult['new_user_id']) ? $registerResult['new_user_id'] : 0);

        if($this->userId > 0){
            //保存地址到会员中心（user_id：为刚初始注册或以新增地址中的手机号的user_id）
            $this->logicUserAddress->setDatas($consignee);
            $result = $this->logicUserAddress->save();
            if($result === false){
                $this->setError($this->logicUserAddress->getError());
                return false;
            }
            session('default_address_id', $result['address_id']);
        }
        return true;
    }

    /**
     * 判断获取广告来源
     * @param array $order
     * @return array
     */
    private function getFromAdvert($order = array()){
        /*
         *判断cookie，将广告来源（yiqifa）记录表中
        */
        if(!empty($_COOKIE['yiqifa'])){
            $yiqifa = urldecode(stripslashes($_COOKIE['yiqifa']));
            $union = explode(":",$yiqifa);
            $union_info['from_url'] = $union[0];
            $union_info['channel'] = $union[1];
            $union_info['cid'] = $union[2];
            $union_info['wi'] = $union[3];
        }
        if(isset($union_info['from_url'])){
            $from_url = $union_info['from_url'];
            if(isset($copartner_array[$from_url])){
                $union_info['from_ad'] = $copartner_array[$from_url][0];
                $union_info['referer'] = $copartner_array[$from_url][2];
            }
        }

        $order['from_ad']          = session('from_ad');
        $order['from_ad']          = $order['from_ad'] ? $order['from_ad'] : '0';
        $order['referer']          = session('referer');
        $order['referer']          = $order['referer'] ? addslashes($order['referer']) : '';

        $sitename                  = cookie('sitename');
        $order['referer']          = $sitename ? trim($sitename) : $order['referer'];
        $order['from_ad']		   = $union_info['from_ad'] > 0 ? $union_info['from_ad'] : $order['from_ad'];
        $order['referer']		   = !empty($union_info['referer']) ? $union_info['referer'] : $order['referer'] ;
        return $order;
    }

    /**
     * 获取来源地址等相关数据
     * @return array
     */
    public function getOrderSource(){
        $order = array();
        $cookie_source_url = cookie('source_url');
        $source_url = trim($this->getData('source_url'));
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
        $source_ident = I('request.source_ident','','trim');  //药品订单来源标识,来源域名
        if(!empty($source_ident) && !strstr($order['ip_info_text'],'source_ident')){
            if(strstr($order['ip_info_text'],'?')){
                $order['ip_info_text'] = $order['ip_info_text'] . '&source_ident='.$source_ident;
            }else{
                $order['ip_info_text'] = $order['ip_info_text'] . '?source_ident='.$source_ident;
            }
        }
        return $order;
    }

    /**
     * 检查提交下单的各个参数
     * @return bool
     */
    public function checkCreateOrder(){
        //获取购物车商品
        $cartGoods = $this->getCartGoods();
        if(empty($cartGoods)){
            $this->setError('您的购物车未有勾选商品！');
            return false;
        }
        //检查同IP是不是频繁下单
        $result = D('Common/Home/OrderInfo')->checkIpFrequently();
        if ($result === false){
            $this->setError('您刚下完单了不能重复下单，如有疑问请联系在线客服！');
            return false;
        }

        //获取支付信息
        $paymentData = session('payment_data');
        if(!$paymentData || !isset($paymentData['bonus_type']) || !isset($paymentData['payment_id']) || !isset($paymentData['address_id'])){
            $this->setError('请您选择支付方式 和 收货地址！');
            return false;
        }

        $addressId = $paymentData['address_id'] ? intval($paymentData['address_id']) : 0;
        $paymentId = $paymentData['payment_id'] ? intval($paymentData['payment_id']) : 0;  //1是货到付款

        $is_wechat = isCheckWechat();
        if($is_wechat == true && $paymentId == 4){  //微信不支持支付宝
            $this->setError('请选择支付方式');
            return false;
        }

        //检查收货地址是否有设置
        $this->logicUserAddress->setData('address_id', $addressId);
        $consignee = $this->logicUserAddress->checkConsigneeInfo();  //获取填写的收货地址
        if ($consignee === false){
            $this->setError('请填写收货地址');
            return false;
        }

        //校验token，避免重复提交
        $token = trim($this->getData('token'));
        if(empty($token)){
            $this->setError('页面已过期，请刷新后重试！');
            return false;
        }
        $result = $this->token();  //校验token，如果不正确，会直接提示错误
        if($result === false){
            return false;
        }
        return $cartGoods;  //返回购物车商品
    }

    /**
     * 生成token - 此方法是为了避免前端重复提交、多次点击
     * @return bool|mixed|string
     */
    public function token(){
        $checkToken = trim($this->getData('token'));
        $token = session('token');
        $tokenTime = session('token_time');
        $experTime = 300;  //有效时间，秒

        if(!empty($checkToken)){
            if((!$checkToken || $checkToken == '') && (!$token || $token == '') ){
                if((microtime(true)-$tokenTime)>$experTime){
                    $this->setError('页面已过期');
                    return false;
                }
                $this->setError('正在处理您的订单，再次购买请稍后'.ceil(($experTime-(microtime(true)-$tokenTime))/60).'分钟！');
                return false;
            }
            if(!$token || $token == '' || $checkToken == '' ||  ($token!=$checkToken)){
                if((microtime(true)-$tokenTime)>$experTime){
                    $this->setError('页面已过期，请刷新');
                    return false;
                }
                $this->setError('若您提交过订单，再次购买请稍后'.ceil(($experTime-(microtime(true)-$tokenTime))/60).'分钟！');
                return false;
            }
            session('token',NULL);
            return true;
        }else{  //获取token
            if(!$token || !$tokenTime || (microtime(true)-$tokenTime) > $experTime) {
                $tokenTime = microtime(true);
                $token = md5($tokenTime);
                session('token_time', $tokenTime);
                session('token', $token);
            }
            return $token;
        }
    }

    /**
     * 生成订单号
     * @return string
     */
    public function getOrderSn(){
        /* 选择一个随机的方案 */
        mt_srand((double) microtime() * 1000000);
        //$sn = date('Ymd') . str_pad(mt_rand(1, 99999), 5, '0', STR_PAD_LEFT);
        $orderSn = date('ymd').'1'.rand(1000000,9999999);

        $result = D('Common/Home/OrderInfoCenter')->getOrderId($orderSn);
        if($result > 0){
            $orderSn = $this->getOrderSn();
        }
        return $orderSn;
    }
}