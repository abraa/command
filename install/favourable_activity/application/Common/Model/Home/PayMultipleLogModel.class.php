<?php
/**
 * ====================================
 * 支付日记相关信息模型
 * ====================================
 * Author: 9009123 (Lemonice)
 * Date: 2017-10-16 11:14
 * ====================================
 * File: PayMultipleLogModel.class.php
 * ====================================
 */
namespace Common\Model\Home;
use Common\Model\PaymentsModel;

class PayMultipleLogModel extends PaymentsModel{
    /**
     * 查询子订单是否已经支付
     * @param string $orderSnChild 子订单号
     * @param int $payId 支付id
     * @return mixed
     */
    public function childIsPayed($orderSnChild, $payId){
        return $this->where(array('order_sn_child'=>$orderSnChild,'pay_id'=>$payId, '_string'=>'synchro_status=1 OR asynch_status=1'))->count();
    }

    /**
     * 查询某个单号已支付的总金额
     * @param string $orderSn 订单号
     * @param int $payId  支付ID
     * @param string $orderSnChild  子订单号
     * @return mixed
     */
    public function getOrderPayMoney($orderSn = '', $payId = 0, $orderSnChild = ''){
        return $this->where(array('order_sn'=>$orderSn, 'pay_id'=>$payId, '_string'=>'synchro_status=1 OR asynch_status=1 OR order_sn_child="'.$orderSnChild.'"'))->sum('pay_amount');
    }
}