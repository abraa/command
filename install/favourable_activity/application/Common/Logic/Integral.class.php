<?php
/**
 * ====================================
 * 积分相关业务处理
 * ====================================
 * Author: 9009123 (Lemonice)
 * Date: 2018-01-22 17:40
 * ====================================
 * File: Integral.class.php
 * ====================================
 */
namespace Common\Logic;
use Common\Extend\Time;
use Common\Extend\PhxCrypt;

class Integral extends LogicData{
    /**
     * 会员ID，没登录则为0
     * @var int
     */
    private $userId = 0;
    protected $dbModel = NULL;
    public function __construct() {
        parent::__construct();
        $this->dbModel = D('Common/Home/PointExchangeCenter');
        $this->userId = $this->dbModel->getUser('user_id');
    }

    public function createOrder(){
        $exchangeId = $this->getData('exchange_id');
        $addressId = $this->getData('address_id');
        $paymentId = $this->getData('payment_id');  //1是货到付款（不支持）
        $remark = $this->getData('remark');

        $OrderInfoModel = D('Common/Home/OrderInfo');
        $siteId = C('SITE_ID');
        $realIp = get_client_ip();

        //获取积分商品详情
        $data = $this->getIntegralData();

        $UserAddressObject = new \Common\Logic\UserAddress();
        $UserAddressObject->setDatas($this->getDatas());
        $PaymentObject = new \Common\Logic\Payment();
        $PaymentObject->setDatas($this->getDatas());

        //检查收货地址是否有设置
        $consignee = $UserAddressObject->getUserAddress($addressId);
        //检查收货人信息是否完整
        if (empty($consignee)){
            $this->setError('请填写收货地址');
            return false;
        }

        //计算邮费，是否满200包邮（偏远地区15元），不满200基础邮费20（偏远地区35）
        if(isset($data['shipping_fee_remote']) && $data['shipping_fee_remote'] > 0){
            $province = isset($consignee['province']) ? $consignee['province'] : 0;
            $data['shipping_fee'] = $PaymentObject->getShippingFee($data['price'], $province, $data['shipping_fee'], false);
        }

        $order = array(
            'order_amount'    => $data['shipping_fee'] + $data['price'],  //应付金额
            'shipping_fee'    => $data['shipping_fee'],  //邮费，0=包邮
            'shipping_id'     => 15,  //intval($_POST['shipping']),指定配送方式,为EMS edit by lxm
            'shipping_type'   => 1,
            'pay_id'          => $paymentId,  //支付平台，1=货到付款（不支持）
            'pay_fee'         => 0,  //支付平台费用
            'payment_discount'=> 0,  //支付平台费用
            'pack_id'         => 0,  //包装
            'card_id'         => 0,  //卡片、贺卡
            'card_message'    => '',  //卡片文字
            'surplus'         => 0.00,  //余额
            'integral'        => $data['point'],  //使用的积分
            'integral_money'  => $data['point'],  //积分与抵消的金额一致，固定一比一的比例
            //'integral_money'  => $data['shop_price'] - $data['price'],  //使用积分抵消的金额
            'bonus'           => 0,
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
            'goods_amount'    => $data['price'],  //商品总金额
            'discount'        => 0,  //加上会员折扣
            'tax'             => 0,  //税收
            'parent_id'       => 0,
            'divide_region'   => '广州地区手机商城下单',
            'kefu'            => '手机商城下单',
            'bonus_id'        => 0,  //红包ID
            'order_sn'        => $PaymentObject->getOrderSn(),  //生成订单号
            'site_id'         => $siteId,
            //积分兑换特有的两个字段
            'extension_code'  => GAT_INTEGRAL_BUY,
            'extension_id'    => $exchangeId,
        );

        //处理pay_id/pay_name等支付详情
        $payInfo = $PaymentObject->getPaymentData();
        $order = array_merge($order, $payInfo);

        //获取订单的来源地址
        $orderSource = $PaymentObject->getOrderSource();
        $order = array_merge($order, $orderSource);
        //处理收货人信息
        $order = array_merge($order, $consignee);
        $order['mobile'] = isset($order['encode_mobile']) ? $order['encode_mobile'] : PhxCrypt::phxEncrypt($order['mobile']);

        /* 如果不用支付任何金额，修改订单状态为已确认、已付款 */
        if ($order['order_amount'] <= 0){
            $order['order_status'] = OS_CONFIRMED;
            $order['confirm_time'] = Time::gmtime();
            $order['pay_status']   = PS_PAYED;
            $order['pay_time']     = Time::gmtime();
            $order['order_amount'] = 0;
        }
        //开启事务
        $this->dbModel->startTrans();
        $OrderInfoModel->startTrans();
        //插入到自身站点的订单表
        $order['order_id'] = $OrderInfoModel->add($order);
        //插入到会员中心的订单表
        D('Common/Home/OrderInfoCenter')->add($order);
        //插入订单商品
        $order = $this->addOrderGoods($order, $data);
        if($order === false){
            $this->dbModel->rollback();  //回滚
            $OrderInfoModel->rollback();  //回滚
            return false;
        }
        //处理积分订单相关数据
        $order = $this->parseExchangeOrder($order, $data);
        if($order === false){
            $this->dbModel->rollback();  //回滚
            $OrderInfoModel->rollback();  //回滚
            return false;
        }
        $this->dbModel->commit();  //保存数据

        /* 取得支付信息，生成支付代码 */
        if ($order['order_amount'] > 0){
            $Payment = new \Common\Logic\Pay();
            $Payment->setData('paycode', $order['code']);
            $Payment->setData('pay_type', 'pay');
            $Payment->setData('paytype', 'getCode');
            $Payment->setData('order_sn', $order['order_sn']);
            $Payment->setData('body', '订单支付('.$order['order_sn'].')');
            $Payment->setData('order_amount', $order['order_amount']);
            $order["content"] = $Payment->handle();
            if(!$order["content"]){
                $error = $Payment->getError();
                $this->setError($error);
                return false;
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

        //记录当前所下的订单，用于在线支付时读取订单信息
        session('pay_online_order_sn', $order['order_sn']);

        return $order;
    }

    /**
     * 处理积分订单相关
     * @param array $order
     * @param array $data
     * @return array
     */
    private function parseExchangeOrder($order = array(), $data = array()){
        $exchangeId = $this->getData('exchange_id');
        $UserPointExchangeModel = D('Common/Home/UserPointExchangeCenter');
        //记录到兑换记录表
        $UserPointExchangeModel->add(array(
            'site_id'=>C('site_id'),
            'user_id'=>$this->userId,
            'goods_id'=>$data['goods_id'],
            'goods_name'=>$data['goods_name'],
            'goods_number'=>1,
            'points'=>$data['point'],
            'price'=>$data['price'],
            'order_id'=>$order['order_id'],
            'order_sn'=>$order['order_sn'],
            'client_ip'=>get_client_ip(),
            'data'=>serialize($data),
            'addtime'=>Time::gmtime(),
        ));
        //库存减1
        $this->dbModel->where(array('exchange_id'=>$exchangeId))->setDec('max_number');
        //扣掉积分
        $IntegralObject = new \Common\Extend\Integral();
        $remark = '积分兑换商品：'.$data['goods_name'];
        $extra = array('user_id'=>$this->userId,'order_sn'=>$order['order_sn'],'order_id'=>$order['order_id']);
        $result = $IntegralObject->newVariety($order['site_id'], intval('-'.$order['integral']), $remark, -3, false, $extra);
        /*
        //冻结积分
        $UserPointFreezeModel = D('UserPointFreeze');
        $UserPointFreezeModel->create(array(
            'site_id'=>$site_id,
            'user_id'=>$user_id,
            'order_sn'=>$order['order_sn'],
            'mobile'=>$order['mobile'],
            'type'=>1,  //类型：0：订单，1.换购
            'integral'=>'-'.$order['integral'],
            'create_time'=>Time::gmtime(),
        ));
        $result = $UserPointFreezeModel->add();
        */
        if(!$result){
            $this->setError('兑换失败，请重试或者联系客服。');
            return false;
        }
        D('Common/Home/Users')->setUserInfo($this->userId);  //刷新登录的积分缓存
        return $order;
    }

    /**
     * 插入订单商品
     * @param array $order
     * @param array $data
     * @return array
     */
    private function addOrderGoods($order = array(), $data = array()){
        $order_goods_data = array(
            'order_id'=>$order['order_id'],
            'order_sn'=>$order['order_sn'],
            'goods_id'=>$data['goods_id'],
            'goods_name'=>($data['goods_name'] ? $data['goods_name'] : ''),
            'goods_sn'=>($data['goods_sn'] ? $data['goods_sn'] : ''),
            'goods_number'=>1,  //($data['goods_number'] ? $data['goods_number'] : 1)
            'market_price'=>($data['market_price'] ? $data['market_price'] : 0),
            'goods_price'=>($data['shop_price'] ? $data['shop_price'] : 0),
            'goods_attr'=>($data['goods_attr'] ? $data['goods_attr'] : ''),
            'is_real'=>($data['is_real'] ? $data['is_real'] : 1),
            'extension_code'=>($data['extension_code'] ? $data['extension_code'] : ''),
            'parent_id'=>($data['parent_id'] ? $data['parent_id'] : 0),
            'is_gift'=>0,
            'site_id'=>$order['site_id'],
        );
        $OrderGoodsModel = D('Common/Home/OrderGoods');
        $OrderGoodsCenterModel = D('Common/Home/OrderGoodsCenter');
        $OrderGoodsModel->add($order_goods_data);  //插入商品数据到当前站点数据库
        $OrderGoodsCenterModel->add($order_goods_data);  //插入商品数据到ucenter数据库

        //检查如果是套装，则回去套装商品加入到订单商品
        if($order_goods_data['extension_code'] == 'package_buy'){  //是套装
            if(isset($data['package_goods']) && !empty($data['package_goods'])){
                foreach($data['package_goods'] as $key=>$children){
                    if($children['goods_name']){
                        $package_goods = array(
                            'order_id'=>$order_goods_data['order_id'],
                            'order_sn'=>$order_goods_data['order_sn'],
                            'goods_id'=>$children['goods_id'],
                            'goods_name'=>$children['goods_name'],
                            'goods_sn'=>$children['goods_sn'],
                            'goods_number'=>$children['goods_number'],
                            'market_price'=>$children['market_price'],
                            'goods_price'=>$children['shop_price'],
                            //'goods_attr'=>'',
                            //'is_real'=>$children['is_real'],
                            'extension_code'=>'package_goods',
                            'parent_id'=>($data['package_id'] ? $data['package_id'] : 0),
                            //'is_gift'=>$row['is_gift'],
                            'site_id'=>$order_goods_data['site_id'],
                        );
                        $OrderGoodsModel->add($package_goods);  //插入商品数据到当前站点数据库
                        $OrderGoodsCenterModel->add($package_goods);  //插入商品数据到ucenter数据库
                    }
                }
            }
        }
        return $order;
    }

    /**
     * 获取积分商品详情
     * @return bool
     */
    private function getIntegralData(){
        $exchangeId = $this->getData('exchange_id');
        //检查同IP是不是频繁下单
        $result = D('Common/Home/OrderInfo')->checkIpFrequently();
        if ($result === false){
            $this->setError('您刚下完单了不能重复下单，如有疑问请联系在线客服！');
            return false;
        }
        //获取积分
        $data = $this->dbModel->getInfo($exchangeId, $this->userId, true);
        if(empty($data)){
            $this->setError('此商品不存在或者积分不足于兑换，或者已经下线！');
            return false;
        }
        //检查购买数量
        if($data['per_number'] > 0 && $this->checkNumber($data) === false){
            return false;
        }
        $data['extension_code'] = $data['goods_type'] == 'package_goods' ? 'package_buy' : '';
        return $data;
    }

    /**
     * 检查某个商品当前用户兑换次数
     * @param array $data
     * @return int
     */
    private function checkNumber($data = array()){
        $count = 0;
        $OrderInfoModel = D('Common/Home/OrderInfo');
        $where = array('site_id'=>C('SITE_ID'), 'user_id'=>$this->userId, 'goods_id'=>$data['goods_id']);
        $orderIdArray = D('Common/Home/UserPointExchangeCenter')->field('order_id')->where($where)->select();
        if(!empty($orderIdArray)){
            $orderIds = array();
            foreach($orderIdArray as $oid){
                $orderIds[] = $oid['order_id'];
            }
            //查看对应的订单是否【未确认】【已确认】
            $count = $OrderInfoModel->where(array('order_id'=>array('IN', $orderIds),'order_status'=>array('IN', array(OS_UNCONFIRMED,OS_CONFIRMED))))->count();
        }
        //校验数量
        if($count >= $data['per_number']){
            $this->setData('每人限量兑换最高'.$data['per_number'].'个，您当前已经达到限制，请未支付的订单尽快支付。');
            return false;
        }
        return $count;
    }
}