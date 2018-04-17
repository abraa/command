<?php
/**
 * ====================================
 * 会员中心 里面的订单详情模型
 * ====================================
 * Author: 9009123 (Lemonice)
 * Date: 2017-09-30 17:45
 * ====================================
 * File: OrderInfoCenterModel.class.php
 * ====================================
 */
namespace Common\Model\Home;
use Common\Model\CustomizeModel;

class OrderInfoCenterModel extends CustomizeModel{
	protected $_config = 'USER_CENTER';
    protected $_table = 'OrderInfo';

    /**
     * 获取订单号对应的订单ID
     * @param string $orderSn
     * @return int|mixed
     */
    public function getOrderId($orderSn = ''){
        $orderId = $this->where("order_sn = '$orderSn'")->getField('order_id');
        return $orderId>0 ? $orderId : 0;
    }

    /**
     * 获取订单总金额
     * @param $order_sn
     * @return int
     */
    public function getOrderAmount($order_sn){
        $order_amount = 0;
        $data = $this->field('order_amount,goods_amount,bonus,integral_money,shipping_fee,postscript,discount,money_paid,payment_discount,update_time')->where("order_sn = '$order_sn'")->find();

        if(!empty($data)){
            if($data['update_time'] >= \Common\Extend\Time::localStrtotime('2016-10-18 16:00:00') || $data['integral_money'] > 0){  //$data['integral_money'] > 0是积分兑换
                $order_amount = $data['order_amount'] + $data['money_paid'];
            }else{
                $order_amount = $data['goods_amount'] - $data['bonus'] - $data['integral_money'] + $data['shipping_fee'] - $data['discount'] - $data['payment_discount'];
            }
        }
        return $order_amount;
    }
}