<?php
/**
 * ====================================
 * 支付相关信息模型
 * ====================================
 * Author: 9009123 (Lemonice)
 * Date: 2017-10-08 17:46
 * ====================================
 * File: PayInfoModel.class.php
 * ====================================
 */
namespace Common\Model\Home;
use Common\Model\PaymentsModel;
use Common\Extend\Time;

class PayInfoModel extends PaymentsModel{
    /**
     * 切割出主订单号
     * @param string $orderSn  订单号，包含下划线隔开的子订单号、或者无子订单号
     * @return string
     */
    public function explodeOrderSn($orderSn = ''){
        if(strstr($orderSn, '_') !== false){
            $tmp = explode('_', $orderSn);
            $orderSn = $tmp[0];
        }
        return $orderSn;
    }

    /**
     * 更新订单详情信息
     * @param int $order 订单号
     * @param int $time 时间戳，子单号
     * @return bool|mixed
     */
    public function insertPayInfo($order, $time = 0){
        if(!is_array($order) || empty($order)){
            return false;
        }
        $ordersn = $this->explodeOrderSn($order["order_sn"]);
        $order_money = isset($order['order_money']) ? $order['order_money'] : 0;

        $time = $time > 0 ? $time : Time::gmTime();
        $res = $this->field('id,status,order_money')->where(array('order_sn'=>$ordersn, 'pay_id'=>$order['pay_id']))->find();

        $site_id = isset($order['site_id']) ? $order['site_id'] : C('SITE_ID');
        $order_info_id = 0;
        if(!empty($res)){
            if($res['status'] == 0){  //还没有成功支付过的，更新包括金额在内的所有信息，有成功支付过的不更新，因为金额需要在成功后叠加
                $rs = $this->where(array('id'=>$res['id']))->save(array(
                    'site_id'=>$site_id,
                    'name'=>$order['consignee'],
                    'order_money'=>$order_money,
                    'order_amount'=>$order['order_amount'],
                ));
            }else{  //更新收货人名称
                $data = array(
                    'site_id'=>$site_id,
                    'name'=>$order['consignee'],
                );
                if($res['order_money'] <= 0){
                    $data['order_money'] = $order_money;
                }
                $rs = $this->where(array('id'=>$res['id']))->save($data);
            }
            if (!isset($rs->res) || !$rs){ //操作异常,记录异常日志
                \Think\Log::record('更新订单信息出错了, 订单号：'.$order['order_sn'].', SQL:'.$this->getLastSql());
            }
        }else{  //没有记录，插入新记录
            $data = array(
                'site_id'=>$site_id,
                'pay_id'=>$order['pay_id'],
                'order_sn'=>$ordersn,
                'name'=>($order['consignee'] ? $order['consignee'] : ''),
                'order_money'=>$order_money,
                'order_amount'=>$order['order_amount'],
                'add_time'=>$time,
            );
            $order_info_id = $this->add($data);
            if (!isset($rs->res) || !$rs){ //操作异常,记录异常日志
                \Think\Log::record('更新订单信息出错了, 订单号：'.$order['order_sn'].', SQL:'.$this->getLastSql());
            }
        }

        //记录到子单号详情
        $PayMultipleLogModel = D('Common/Home/PayMultipleLog');
        $order_info_id = $order_info_id > 0 ? $order_info_id : ($res['id'] ? $res['id'] : 0);
        $logId = $PayMultipleLogModel->where(array('site_id'=>$site_id, 'pay_id'=>$order['pay_id'],'order_sn_child'=>$order['order_sn']))->getField('id');
        $data = array(
            'order_info_id'=>$order_info_id,
            'site_id'=>$site_id,
            'pay_id'=>$order['pay_id'],
            'order_sn'=>$ordersn,
            'order_sn_child'=>$order['order_sn'],
            'pay_amount'=>$order['order_amount'],
            'add_time'=>$time,
        );
        if($logId > 0){
            $PayMultipleLogModel->where(array('id'=>$logId))->save($data);
        }else{
            $logId = $PayMultipleLogModel->add($data);
        }
        if (!isset($res->res) || !$res){ //操作异常,记录异常日志
            \Think\Log::record('更新订单信息出错了, 订单号：'.$order['order_sn'].', SQL:'.$PayMultipleLogModel->getLastSql());
        }
        return $logId;
    }

    /**
     * 修改订单的支付状态
     * @param $param
     * @return bool|int
     */
    public function orderPaid($param){
        if (!isset($param['order_sn'],$param['pay_id'],$param['order_amount'])){
            return false;
        }

        //为了避免微信重复支付时候订单号重复的问题
        $orderSnChild = $param['order_sn'];  //子订单号
        $param['order_sn'] = $this->explodeOrderSn($param['order_sn']);  //主订单号

        $nowTime = Time::gmTime();
        $payTime = isset($param['pay_time']) ? $param['pay_time'] : $nowTime;
        $addTime = isset($param['create_time']) ? $param['create_time'] : $nowTime;
        $siteId = isset($param['site_id']) ? $param['site_id'] : C('SITE_ID');

        $PayMultipleLogModel = D('Common/Home/PayMultipleLog');

        $order = $this->field("order_amount,id,status")->where(array('order_sn'=>$param['order_sn'],'pay_id'=>$param['pay_id']))->find();
        $result = 1;
        if (isset($order['order_amount'])){
            //检查该子单号是否已经支付过了，如果支付过了，则此次支付不再累加"已支付"金额
            $payAmount = $PayMultipleLogModel->getOrderPayMoney($param['order_sn'], $param['pay_id'], $orderSnChild);
            $data = array('pay_time'=>$payTime, 'status'=>1, 'note'=>'');
            if($payAmount > 0){
                $data['order_amount'] = $payAmount;
            }
            $this->where(array('order_sn'=>$param['order_sn'], 'pay_id'=>$param['pay_id']))->save($data);
        }else{
            if ($payTime < $nowTime-7*86400){ //不再记录一个星期以前的异常订单
                return false;
            }
            $data = array(
                'site_id'=>$siteId,
                'pay_id'=>$param['pay_id'],
                'order_sn'=>$param['order_sn'],
                'name'=>'异常用户',
                'order_amount'=>$param['order_amount'],
                'source'=>3,
                'status'=>1,
                'add_time'=>$addTime,
                'pay_time'=>$payTime
            );
            $insert_id = $this->add($data);
        }

        //插入子单号详情表
        $id = $PayMultipleLogModel->where("`order_sn_child`='".$orderSnChild."' AND `pay_id`='".$param['pay_id']."'")->getField('id');
        $synchro_status = $param['verify_type'] == 1 ? 1 : 0;
        $asynch_status = $param['verify_type'] == 2 ? 1 : 0;
        if($id > 0){
            $data = array(
                'site_id'=>$siteId,
                'pay_time'=>$nowTime,
            );
            if($synchro_status > 0){
                $data['synchro_status'] = $synchro_status;
            }
            if($asynch_status > 0){
                $data['asynch_status'] = $asynch_status;
            }
            if($param['order_amount'] > 0){
                $data['pay_amount'] = $param['order_amount'];
            }
            $PayMultipleLogModel->where("id = '$id'")->save($data);
        }else{
            $order_info_id = $order['id'] ? $order['id'] : (isset($insert_id) ? $insert_id : 0);
            $data = array(
                'order_info_id'=>$order_info_id,
                'site_id'=>$siteId,
                'pay_id'=>$param['pay_id'],
                'order_sn'=>$param['order_sn'],
                'order_sn_child'=>$orderSnChild,
                'pay_amount'=>$param['order_amount'],
                'synchro_status'=>$synchro_status,
                'asynch_status'=>$asynch_status,
                'pay_time'=>$nowTime,
            );
            $PayMultipleLogModel->add($data);
        }

        return $result;
    }
}