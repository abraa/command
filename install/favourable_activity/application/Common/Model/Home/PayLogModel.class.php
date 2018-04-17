<?php
/**
 * ====================================
 * 订单日记模型
 * ====================================
 * Author: 9009123 (Lemonice)
 * Date: 2017-10-14 16:59
 * ====================================
 * File: PayLogModel.class.php
 * ====================================
 */
namespace Common\Model\Home;
use Common\Model\PaymentsModel;
use Common\Extend\Time;

class PayLogModel extends PaymentsModel{
    /**
     * 插入订单日记
     * @param $param
     * @return bool|mixed
     */
	public function addPayLog($param){
		if (!isset($param['pay_id'],$param['order_sn'],$param['verify_type'],$param['log'])){
			return false;
		}
        $where = array(
            'pay_id'=>$param['pay_id'],
            'order_sn'=>$param['order_sn'],
            'verify_type'=>$param['verify_type'],
        );
		$id = $this->where($where)->getField('id');
		if($id){
			return false;
		}
		$id = $this->add(array(
            'pay_id'=>$param['pay_id'],
            'order_sn'=>$param['order_sn'],
            'verify_type'=>$param['verify_type'],
            'log'=>$param['log'],
            'add_time'=>Time::gmTime(),
        ));
		return $id;
	}
}