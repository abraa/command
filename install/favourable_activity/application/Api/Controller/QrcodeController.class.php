<?php
/**
 * ====================================
 * 客服二维码数据接收控制器（后台推送）
 * ====================================
 * Author: 9006758
 * Date: 
 * ====================================
 * Project: new.m.chinaskin.cn
 * File: QrcodeController.class.php
 * ====================================
 */
namespace Api\Controller;
use Common\Controller\ApiController;
use Common\Extend\CustomerQrcode;

class QrcodeController extends ApiController{

    private $_dbModel;
    public function __construct(){
        parent::__construct();
        $this->_dbModel = D('Home/Qrcode');
    }

    /**
     * 客服二维码推送接口
     */
    public function index(){
        $data = I('request.data');
        if(empty($data)){
            $this->ajaxReturn('false');
        }
        $return = true;
        foreach($data as $key => $val){

            $time = time();
            $_data['job_number']  = $val['job_number'];
            $_data['real_name']   = $val['real_name'];
            $_data['weixin']      = $val['weixin'];
            $_data['kefu_qrcode'] = $val['qrcode'];
            $_data['create_time'] = $time;
            $_data['is_show']     = $val['is_show'];
            $_data['show_time']   = $time;
            $_data['locked']      = $val['locked'];
            $_data['qrcode_type'] = $val['type'];
            $_data['kefu_level']  = $val['kefu_level'];

            $where['qrcode_id'] = $val['id'];
            $info = $this->_dbModel->where($where)->field('id,kefu_level,is_show,locked')->find();
            if(empty($info)){

                /* 需要增加的数据 */
                $_data['qrcode_id'] = $val['id'];
                $insert_data[] = $_data;

            }else{

                /* 更新数据 */
                $this->_dbModel->where($where)->save($_data);

                /* 如果更新的数据的等级在设置中，并且更新的等级跟旧数据等级不同，则需刷新队列 */
                if($info['kefu_level'] != $val['kefu_level']){
                    $level_update[] = $val['id'];
                }else if(intval($val['is_show']) == 0 && intval($val['locked']) == 0){
                    //推过来的如果是显示，则判断与旧数据的显示状态做比较，如果是重新显示，则需刷新队列
                    if($info['is_show'] != $val['is_show'] || $info['locked'] != $val['locked']){
                        $level_update[] = $val['id'];
                    }
                }
            }

        }

        if(!empty($insert_data)){
            $return = $this->_dbModel->addAll($insert_data);
        }

        /* 刷新队列操作 */
        if(!empty($level_update) || !empty($insert_data)){
            $this->flushQueue(2);
        }

        $this->ajaxReturn($return);
    }

    /**
     * 刷新队列
     */
    private function flushQueue($qrcode_type = 2){
        $queue = new CustomerQrcode();
        $queue->flushQueue($qrcode_type);
    }


    //======================================================================================================================================
    public function index_old(){
        $config = C('db_config.1');
        $connection = array_merge($config['CONFIG'], array('DB_TYPE' => C('DB_TYPE')));
		$qrcodeModel = M('customer_qrcode',$connection['DB_PREFIX'],$connection);
		$params = I('request.');

        $resturn = true;
		$data = $params['data'];
		if(!empty($data)){
            $insert_data = array();
            $resturn = true;
//            $i = 0;
            $checkId = array();
            foreach($data as $key=>$val){
                $qrcode_id = $val['id'];

                $where['qrcode_id'] = $qrcode_id;
                $is_exist = $qrcodeModel->where($where)->count();
                $time = time();
                $_data['job_number']  = $val['job_number'];
                $_data['real_name']   = $val['real_name'];
                $_data['weixin']      = $val['weixin'];
                $_data['kefu_qrcode'] = $val['qrcode'];
                $_data['create_time'] = $time;
                $_data['is_show']     = $val['is_show'];
                $_data['show_time']   = $time;
                $_data['locked']      = $val['locked'];
                $_data['qrcode_type'] = $val['type'];

                if($is_exist){
                    $res = $qrcodeModel->where($where)->save($_data);
                    /*if($res){
                        $i++;
                    }*/
                }else{
                    $checkId[] = $_data['qrcode_id'] = $qrcode_id;
                    $insert_data[] = $_data;
                }
            }

            if(!empty($insert_data)){
                $res = $qrcodeModel->addAll($insert_data);
            }
            if(!$res){
                $resturn = false;
            }
            /*$addnum = 0;
            if(!empty($checkId) && !empty($insert_data)){
                if($insert_data == $checkId){
                    $addnum = count($insert_data);
                    $i += count($insert_data);
                }else{
                    $where['qrcode_id'] = array('IN',$checkId);
                    $total = $qrcodeModel->where($where)->count();
                    $addnum = $total;
                    $i += $total;
                }
            }*/
        }
        $this->ajaxReturn($resturn);
    }

    /**
     * 推送绑定关系
     */
    public function relationships(){
        $bindModel = D('Home/CustomerQrcodeBind');
        $job_number = I('request.job_number', 0, 'intval');
        $customer_id = I('request.customer_id', 0, 'intval');
		$status = 0; //0-参数有误，1-成功，2-失败，3-已存在
        if($job_number && $customer_id){
			$exist = $bindModel->where(array('job_number'=>$job_number, 'customer_id'=>$customer_id))->count();
			if($exist){
				$status = 3;
			}else{
				$res = $bindModel->data(array('job_number'=>$job_number, 'customer_id'=>$customer_id))->add();
				if($res){
					$status = 1;
				}else{
					$status = 2;
				}
			}
        }
		echo $status;
    }
	
}