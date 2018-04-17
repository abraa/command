<?php
/**
 * ====================================
 * 退货申请
 * ====================================
 * Author: 9006765
 * Date: 2017-04-17
 * ====================================
 * File: RefundController.class.php
 * ====================================
 */
namespace Home\Controller;
use Common\Controller\InitController;
use Common\Extend\PhxCrypt;
use Common\Extend\Time;


class RefundController extends InitController {


    public function _initialize() {
		parent::_initialize();
    }

    /**
     * 保存退货申请
     */
    public function save(){
        $ip = get_client_ip();
        $ipKey = md5($ip);
        $IpCount = S($ipKey);
        $filter = array(
            '14.23.109.186'
        );
        if (empty($IpCount)) {
            S($ipKey, 1, array('expire' => 600));
        } else {
            if(!in_array($ip,$filter)){
                $this->error('不能频繁的提交信息');
            }
        }
        //验证手机验证码
        if( $_POST['phoneNum'] != $_SESSION['mobileCode']){
            $this->error('手机验证码不正确');
        }
        if( empty($_POST['username']) && preg_match("/^[\\u4E00-\\u9FA5\\uF900-\\uFA2D]{2,}$/", $_POST['username'])){
            $this->error('请填写签收人');
        }
        if( empty($_POST['phone']) && preg_match("/^1[34578][0-9]{9}$/", $_POST['phone'])){
            $this->error('请输入正确的手机号');
        }
        if( empty($_POST['order'])){
            $this->error('请输入单号');
        }
        if(empty($_POST['refund_reason'])){
            $this->error('请选择退款原因');
        }
        if(empty($_POST['rf_type'])){
            $this->error('请选择退款类型');
        }
        if(!in_array($_POST['rf_type'], array(1,2))){
            $this->error('退款类型有误');
        }
        if(empty($_POST['memo'])){
            $this->error('请填写退款说明');
        }


        $phones = phxCrypt::phxEncrypt(trim($_POST['phone']));
        $order_sn = '';
        $kd_number= '';

        //获取订单号
        if($_POST['list_num'] == '2') {
            //快递单号
            $kd_number = $_POST['order'];
        }elseif($_POST['list_num'] == '1'){
            //订单号;
            $order_sn = $_POST['order'];

        }

        //有 银行信息
        if($_POST['cod_num'] == '2'){
            if(empty($_POST['banks'])){
                $this->error('请选择银行');
            }
            if(empty($_POST['bank'])){
                $this->error('请输入开户银行');
            }
            if(empty($_POST['bank_name'])){
                $this->error('请输入开户人姓名');
            }
            if(empty($_POST['bank_accounts'])){
                $this->error('请输入银行账号');
            }
            //银行
            $banks = array(
                '1'=>'中国银行',
                '2'=>'建设银行',
                '3'=>'农业银行',
                '4'=>'工商银行',
            );
            $bank_info['banks'] = $banks[$_POST['banks']];
            $bank_info['bank'] = $_POST['bank'];
            $bank_info['bank_name'] = $_POST['bank_name'];
            $bank_info['bank_accounts'] = $_POST['bank_accounts'];

        }

        $bank_info = addslashes(json_encode($bank_info));
        if($bank_info == 'null'){
            $bank_info = "";
        }

        //写入数据库
        $data['username'] = trim($_POST['username']);
        $data['phone']  = trim($phones);
        $data['sn_type'] =  trim($_POST['list_num']);
        $data['order_sn'] = trim($order_sn);
        $data['refund_type'] = trim($_POST['cod_num']);
        $data['bank_info'] =  trim($bank_info);
        $data['memo'] = trim($_POST['memo']);
        $data['kd_number'] = trim($kd_number);
        $data['create_time'] = time();
        $data['refund_reason'] = trim($_POST['refund_reason']);
        $data['rf_type'] = trim($_POST['rf_type']);
        $refundModel = D('PayRefund');
        $refundModel -> create($data);

        if($_POST['cod_num'] == '1'){
            $type = "退款到原支付账号";
        }elseif($_POST['cod_num'] == '2'){
            $type = "退款到指定银行账号";
        }


        if($refundModel -> add()){
            $log_data['refund_id'] =  $refundModel->getLastInsID();
            $log_data['refund_mark'] =  "编号为:". $log_data['refund_id']  ."提交申请";
            $log_data['status'] = 0;
            $log_data['create_time'] = $data['create_time'];
            $refundLogModel = D('PayRefundLog');
            $refundLogModel->create($log_data);
            $refundLogModel->add();

            $outdata['username'] = $_POST['username'];
            $outdata['phone'] = $_POST['phone'];
            $outdata['type'] = $type;
            $outdate['time'] = Time::localDate('Y-m-d H:i:s',$data['create_time']);
            $outdata['order'] = $_POST['order'];
            $this->success($outdata);
            exit;
        }else{
            $this->error('提交失败，可能系统繁忙！');
        }
    }


    /**
     * 查询退货申请
     */
   public function query(){
       if($_POST['type'] == 'status'){
           if($_POST['sn_type'] == 1){
               $_POST['sn'] = phxCrypt::phxEncrypt(trim($_POST['sn']));
           }
           $res = $this->getStatus($_POST['sn_type'], $_POST['sn']);
           if(!isset($res) || empty($res) || $res == null){
               $this->error('无记录');
           }
           $res = $this->getTimes($res);
           $this->success($res);
       }
   }

   private function getStatus($type,$sn){
        $refundModel = D('PayRefund');
        $where = array();
        if($type == '1'){
            $where['phone'] = $sn;
        }elseif($type == '2'){
            $where['order_sn'] = $sn;
        }elseif($type == '3'){
            $where['kd_number'] = $sn;
        }
        return $refundModel->where($where)->select();
    }


    //将时间戳转换时间格式
   private function getTimes($datalist){
        $comments = array();
        foreach($datalist as $key=>$val){
            foreach($val as $k=>$v){
                if($k == 'create_time'){
                    $comments[$key][$k] = date('Y-m-d  H:i:s',$v);

                }else if($k == 'end_time' && $v !=0){

                    $comments[$key][$k] = date('Y-m-d  H:i:s',$v);
                }else{
                    $comments[$key][$k] = $v;
                }
            }
        }
        return $comments;
   }

}