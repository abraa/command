<?php
//==================================
// 客户资料接口(废弃，已经整合到QrcodeController.class.php)
// @Author 9006758
// Date: 2017-02-27
// File: UserCustomerController.class.php
//=================================
namespace Home\Controller;
use Common\Controller\InitController;
use Common\Extend\PhxCrypt;
use Common\Extend\Curl;
class UserCustomerController extends InitController
{

    public function index(){}
	
	/**
	 * 验证客户资料是否存在
	 * @param $phone 手机号
	 * return json
	 */
	public function validate(){
        $return = array('status'=>1, 'msg'=>'Success', 'data'=>array());
        $jsonp = trim(I('request.jsonp'));
		$phone = I('request.mobile', '', 'trim');
		
		if(!is_phone($phone)){
			$return['status'] = 0;
			$return['msg'] = '手机号错误';
		}else{
			$dbModel = D('UserCustomer');
			$phx_phone = PhxCrypt::phxEncrypt($phone);
			$result = json_decode(Curl::request('http://q.chinaskin.cn/Qrcode/queryLog.json',array('mobile'=>$phx_phone)), true);
			$has = $dbModel->where(array('phone'=>$phx_phone))->count();
			if($result){
				$return['data'] = array('query_id'=>$result['query_id']);
			}
			if(!$has){
				$return['status'] = 0;
				$return['msg'] = '用户不存在';
			}
		}
        if(!empty($jsonp)){
            echo $jsonp.'='.json_encode($return);
        }else{
            $this->ajaxReturn($return);
        }
        exit;
	}
}
