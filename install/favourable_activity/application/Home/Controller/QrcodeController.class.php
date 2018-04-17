<?php
/**
 * ====================================
 * 客服二维码控制器
 * ====================================
 * Author: 9006758
 * Date: 
 * ====================================
 * Project: new.m.chinaskin.cn
 * File: QrcodeController.class.php
 * ====================================
 */
namespace Home\Controller;
use Common\Controller\InitController;
use Common\Extend\PhxCrypt;
use Common\Extend\CustomerQrcode;

class QrcodeController extends InitController
{
    public $dbModel;

    public function __construct(){
        parent::__construct();
        $this->dbModel = D('Qrcode');
    }

    public function index()
    {
        $return = array('status'=>1, 'msg'=>'Success', 'data'=>array());
        $jsonp = trim(I('request.jsonp'));
        $phone = I('request.mobile', '', 'trim');
        $security_code = I('request.security_code', '', 'trim');

        $customerModel = D('UserCustomer');
        if(is_phone($phone)){

            $phx_phone = PhxCrypt::phxEncrypt($phone);
            $now_time = time();

            /* 记录查询日志 */
            $queryLogModel = M('customer_query_log');
            $query_log_id = $queryLogModel->data(array('phone'=>$phx_phone, 'query_time'=>$now_time, 'security_code'=>$security_code))->add();
            $return['data']['query_id'] = $query_log_id;

            /* 客户是否存在 */
            $customer_exist = $customerModel->where(array('phone'=>$phx_phone))->count();
            if($customer_exist){
                $res = load_config(CONF_PATH.'config_wechat.php');

                //存在显示公众号
//                $return['data']['kefu'] = 0;
                $return['data']['qrcode_id'] = 0;
                $return['data']['weixin'] = $res[0]['weixin'];
                $return['data']['kefu_qrcode'] = $res[0]['qrcode'];
            }else{
                //显示客服二维码
                $qrcodeModel = M('customer_qrcode');
                //获取显示次数最低的二维码，并更新最后一次展示的时间，
                $where = array(
                    'is_show'=>0,
                    'locked'=>0,
                    '_string' => "FIND_IN_SET('1',qrcode_type)"
                );

                $info = $qrcodeModel
                    ->where($where)
                    ->order('show_time asc')
                    ->field('id,kefu_qrcode,weixin')
                    ->find();
                if($info){
                    //更新二维码展示的时间
                    $data['qrcode_id'] = $info['id'];
                    $data['weixin'] = $info['weixin'];
                    $data['show_time'] = $now_time;
                    $qrcodeModel->where(array('id'=>$info['id']))->setField('show_time',$data['show_time']);

                    //记录显示日志
                    M('customer_qrcode_log')->data($data)->add();

                    //ajax 返回的数据
//                    $return['data']['kefu'] = 1;
                    $return['data']['qrcode_id'] = $info['id'];
                    $return['data']['weixin'] = $info['weixin'];
                    $return['data']['kefu_qrcode'] = $info['kefu_qrcode'];
                }else{
                    $return['status'] = 0;
                    $return['msg'] = '无客服微信';
                }
            }
        }else{
            $return['status'] = 0;
            $return['msg'] = '手机号错误';
        }

        if(!empty($jsonp)){
            echo $jsonp.'='.json_encode($return);
        }else{
            $this->ajaxReturn($return);
        }
        exit;
    }


    /**
     * 二维码查询日志以及长按二维码记录
     */
    public function queryLog(){
        $queryLogModel = M('customer_query_log');
        $jsonp = trim(I('request.jsonp'));
        $id = I('request.query_id', 0, 'intval');
        $qrcode_id = I('request.qrcode_id', 0, 'intval');
        $weixin = I('request.weixin', '', 'trim');

        if($id<=0){
            $return['error'] = 0;
            $return['msg'] = 'param error';
        }else{
            $data['press_time'] = time();
            $data['qrcode_id'] = $qrcode_id;    //客服二维码表（ecs_customer_qrcode.id）
            $data['weixin'] = $weixin;          //微信公众号
            $res = $queryLogModel->where(array('id'=>$id))->save($data);
            if($res){
                $return['error'] = 1;
                $return['msg'] = 'Success';
            }else{
                $return['error'] = 0;
                $return['msg'] = 'Fail';
            }
        }
        if($jsonp){
            echo $jsonp.'='.json_encode($return);
        }else{
            $this->ajaxReturn($return);
        }
        exit;
    }
	
	//二线客服二维码获取
	public function getQrcode_old(){
		$customer_id = I('request.customer_id', 0, 'intval');
		$bindModel = M('customer_qrcode_bind');
		$qrcodeModel = M('customer_qrcode');
		$callback = trim(I('request.callback'));
		$return = array('status'=>1, 'msg'=>'', 'data'=>array());
		if($customer_id){
			/* 
			 * 查询客户是否已经绑定了有效的客服二维码，
			 * 1、有绑定时，查询二维码是否有效，无效时，则重新分配二维码，并绑定客户
			 * 2、未绑定是，分配二维码绑定客户
			 */
			$where_bind['customer_id'] = $customer_id;
			$info_bind = $bindModel->where($where_bind)->find();
            $is_new = 0;    //判断是否是新的客服二维码
			if($info_bind){
				$where_qrcode['id'] = $info_bind['cq_id'];
				$where_qrcode['is_show'] = 0;
				$where_qrcode['locked'] = 0;
				$where_qrcode['_string'] = "FIND_IN_SET('2',qrcode_type)";
				$info_qrcode = $qrcodeModel->where($where_qrcode)->field('id,weixin,kefu_qrcode')->find();

				if(!$info_qrcode){
                    $is_new = 1;
					$new_qrcode = $this->makeQrcode();
				}else{
					$new_qrcode = $info_qrcode;
				}
			}else{
                $is_new = 1;
				$new_qrcode = $this->makeQrcode();
			}
            $new_qrcode['new'] = $is_new;
			$return['data'] = $new_qrcode;
			
		}else{
			$return['status'] = 0;
			$return['msg'] = '网络数据错误，请稍后重试';
		}
		if($callback){
			$return  = json_encode($return);
			echo "{$callback}({$return})";
			exit;
		}else{
//			echo '<pre>';
//			print_r($return);
//			exit;
			$this->success($return);
		}
	}
	//分配客服二维码
	private function makeQrcode(){
		$qrcodeModel = M('customer_qrcode');
		$where['locked'] = 0;
		$where['is_show'] = 0;
        $where['_string'] = "FIND_IN_SET('2',qrcode_type)";
		$new_qrcode = $qrcodeModel->where($where)
						->order('show_time asc')
						->field('id,weixin,kefu_qrcode')
						->find();
        $qrcodeModel->where(array('id'=>$new_qrcode['id']))->setField('show_time', time());
		return $new_qrcode;
	}
	
	/**
	 * 客户长按二维码 执行绑定操作
	 * 
	 * @param int qr_id 表 ecs_customer_qrcode.id 值
	 * @param int customer_id 客户id
	 * @param string callback 返回数据类型函数名
	 *
	 * 功能：
	 * 1、判断绑定的客户id与二维码是否一致，并且二维码是有效的
	 * 2、未绑定时，执行绑定
	 */
	public function binding(){
		$bindModel = M('customer_qrcode_bind');
		$qrcodeModel = M('customer_qrcode');
		$cq_id = I('qr_id', 0, 'intval');
		$customer_id = I('request.customer_id', 0, 'intval');
		$callback = trim(I('request.callback'));
		$where_bind['customer_id'] = $customer_id;
		$return = array('status'=>1, 'msg'=>'', 'data'=>array());
        //不需要绑定操作
        if($callback){
            $return  = json_encode($return);
            echo "{$callback}({$return})";
            exit;
        }else{
            $this->success();
        }
		if($cq_id && $customer_id){
			$info_bind = $bindModel->field('id,cq_id,customer_id')->where($where_bind)->find();
			if($info_bind){
				//二维码不同时更新
				if($info_bind['id'] != $cq_id){
					$data['cq_id'] = $cq_id;
					$data['sync'] = 0;
					$bindModel->where(array('customer_id' => $customer_id))->data($data)->save();
				}
				
			}else{
				$data['cq_id'] = $cq_id;
				$data['customer_id'] = $customer_id;
				$data['sync'] = 0;
				$bindModel->data($data)->add();
			}
			$qrcodeModel->where(array('id'=>$cq_id))->setField('show_time', time());
		}else{
			$return['status'] = 0;
			$return['msg'] = '网络数据错误';
		}
		
		if($callback){
			$return  = json_encode($return);
			echo "{$callback}({$return})";
			exit;
		}else{
			$this->success();
		}
	}

    /**
     * 根据等级排列等级获取二维码轮询
     */
    public function getQrcode_old2(){

        $bindModel = M('customer_qrcode_bind');
        $customer_id = I('request.customer_id', 0, 'intval');
        $callback    = trim(I('request.callback'));
        $customerQrcode = new CustomerQrcode();
        $qrcode_type = 2;
        $return = array(
            'status'=>1,
            'msg'=>'',
            'data'=>array()
        );
        if($customer_id){

            $where_bind['customer_id'] = $customer_id;
            $info_bind = $bindModel->where($where_bind)->find();
            $is_new = 0;    //判断是否是新的客服二维码
            if($info_bind){
                /* 绑定的有效二维码的用户直接使用绑定的二维码，否则读取队列 */
                $where_qrcode['id'] = $info_bind['cq_id'];
                $where_qrcode['is_show'] = 0;
                $where_qrcode['locked'] = 0;
                $where_qrcode['_string'] = "FIND_IN_SET('{$qrcode_type}',qrcode_type)";
                $info_qrcode = $this->dbModel->where($where_qrcode)->field('id,weixin,kefu_qrcode')->find();

                if(!$info_qrcode){
                    $is_new = 1;
                    $new_qrcode = $customerQrcode->getQrcode($qrcode_type);
                }else{
                    $new_qrcode = $info_qrcode;
                }
            }else{
                $is_new = 1;
                $new_qrcode = $customerQrcode->getQrcode($qrcode_type);
            }
            $new_qrcode['new'] = $is_new;
            $return['data'] = $new_qrcode;
        }else{
            $return['status'] = 0;
            $return['msg'] = '网络数据错误，请稍后重试';
        }
        if($callback){
            $return  = json_encode($return);
            echo "{$callback}({$return})";
            exit;
        }else{
            $this->success($return);
        }
    }

    /**
     * 查看队列数据
     */
    public function queueShow(){
        $customerQrcode = new CustomerQrcode();
        $queue = $customerQrcode->queueShow();
        echo '<pre>';
        print_r($queue);
        exit;
    }

    /**
     * 根据绑定关系获取二维码
     */
    public function getQrcode(){

        $bindModel = M('customer_qrcode_bind');
        $customer_id = I('request.customer_id', 0, 'intval');
        $callback    = trim(I('request.callback'));
        $qrcode_type = 2;
        $return = array(
            'status'=>1,
            'msg'=>'',
            'data'=>array()
        );
        if($customer_id){
            $where = array(
                'bind.customer_id' => $customer_id,
                'qrcode.is_show' => 0,
                'qrcode.locked' => 0,
                '_string' => "FIND_IN_SET('{$qrcode_type}', qrcode.qrcode_type)",
            );
            $field = 'qrcode.id,qrcode.weixin,qrcode.kefu_qrcode';
            $info = $this->dbModel->alias('qrcode')
                    ->join("__CUSTOMER_QRCODE_BIND__ as bind on qrcode.job_number=bind.job_number")
                    ->where($where)
                    ->field($field)
                    ->find();
            if($info){
                $return['data'] = $info;
                $return['data']['new'] = 0;
            }else{
                $return['status'] = 0;
                $return['msg'] = '数据不存在或网络异常，请联系客服！';
            }
        }else{
            $return['status'] = 0;
            $return['msg'] = '网络数据错误，请稍后重试';
        }
        if($callback){
            $return  = json_encode($return);
            echo "{$callback}({$return})";
            exit;
        }else{
            $this->ajaxReturn($return);
        }
    }

}
