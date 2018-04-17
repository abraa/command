<?php
/**
 * ====================================
 * API公共类
 * ====================================
 * Author: 9009123
 * Date: 2016-09-13 16:16
 * ====================================
 * File: PhoneEncodeController.class.php
 * ====================================
 */
namespace Api\Controller;
use Common\Controller\ApiController;
//use Common\Extend\PhxCrypt;

class UserCustomerController extends ApiController{
	
    //接收后台推送的客户资料
    public function saveData(){
        
        $data = array();
        $model = M('user_customer', null, 'USER_CENTER');
		
		$phone = I('request.phone', '', 'trim');
		$custom_no = I('request.custom_no', 0, 'intval');
        $custom_id = I('request.custom_id', 0, 'intval');

		$return = array('status'=>0, 'msg'=>'', 'data'=>array());
		if($phone && $custom_no){
            $exist_phone = array();//存放已经存在的客户资料
            if(is_array($phone)){
                /* 一个客户多个手机号 */
                foreach($phone as $k=>$v){
                    $exist = $model->where(array('custom_no'=>$custom_no, 'phone'=>$v))->count();
                    if(!$exist){
                        $data[$k]['custom_no'] = $custom_no;
                        $data[$k]['phone'] = $v;
                        $data[$k]['custom_id'] = $custom_id;
                    }else{
                        $exist_phone[] = $v;
                    }
                }
                if($data){
                    $result = $model->addAll($data);
                    if(!$result){
                        $result['status'] = 1;
                        $result['msg'] = '推送失败';
                        $return['data'] = array('insert_fail'=>$data);
                        $this->mkFileLog(array('custom_no'=>$custom_no,'phone'=>$phone, 'custom_id'=>$custom_id),$data,$model->getLastSql());
                    }
                }
                if($exist_phone){
                    $return['data'] = array('phone_exist'=>$exist_phone);
                }
                $this->ajaxReturn($return);

            }else{

                /* 单条推送 */
                $where['custom_no'] = $custom_no;
                $where['phone'] = $phone;
                $exist = $model->where($where)->count();
                if($exist){
                    $return['status'] = 1;
                    $return['msg'] = '客户已经存在';
                    $return['data'] = array('custom_no'=>$custom_no, 'phone'=>$phone, 'custom_id'=>$custom_id);
                    $this->mkFileLog(array($where), array('msg'=>$return['msg']),'');
                }else{
                    $where['custom_id'] = $custom_id;
                    $insert_id = $model->data($where)->add();
                    if($insert_id){
                        $return['msg'] = '推送成功';
                        $return['data'] = array('new_insert_id'=>$insert_id);
                    }else{
                        $return['status'] = 1;
                        $return['msg'] = '推送失败';
                        $return['data'] = array('error'=>$model->getError());
                        $this->mkFileLog(array($where), $where, $model->getLastSql());
                    }
                }

            }
			$this->ajaxReturn($return);
			
		}else{
		
			/* 批量推送 */
			$params = I('request.data');
			if(!$params){
				$this->mkFileLog(array($params), array('msg'=>'param missing'),'');
			}
            $exist_phone = array();//存放已经存在的客户资料
			foreach($params as $key=>$value){
				if($value['custom_no'] && $value['phone']){
					$exist = $model->where(array('custom_no'=>$value['custom_no'], 'phone'=>$value['phone']))->count();
					if(!$exist){
						$data[] = $value;
					}else{
                        $exist_phone[] = $value;
                    }
				}
			}
            $result = true;
			$array_max_num = 30;    //一次性插入的条数限制
			if($data){
				if(count($data) > $array_max_num){
					$data = array_chunk($data, $array_max_num);
					foreach($data as $v){
						$result = $model->addAll($v);
						if(!$result){
							$error_sql = $model->getLastSql();
							$this->mkFileLog($params, $v, $error_sql);
                            $return['status'] = 1;
                            $return['msg'] = $model->getError();
                            $return['data'] = $error_sql;
						}
					}
				}else{
					$result = $model->addAll($data);
					if(!$result){
						$error_sql = $model->getLastSql();
						$this->mkFileLog($params, $data, $error_sql);
                        $return['status'] = 1;
                        $return['msg'] = $model->getError();
                        $return['data'] = $error_sql;
					}
				}
			}
            if($exist_phone){
                $return['data']['phone_exist'] = $exist_phone;
            }
			if($result){
                $this->ajaxReturn($return);
            }

		}
    }

    //错误记录
    protected function mkFileLog($data, $insert, $sql=''){
        $temp_path = DATA_PATH.'user_custome_log/';
        if(!file_exists($temp_path)){
            @makeDir($temp_path, 0777);
        }
        $content = "//======================================== ".date('Y-m-d H:i:s')." ===================================================\n";
        $content .= json_encode(array('data'=>$data))."\n".json_encode(array('insert'=>$insert))."\n";
        $content .= "error_sql:".$sql."\n\n";

        $res = file_put_contents(DATA_PATH.'user_custome_log/'.date('Y-m-d').'.log', $content, FILE_APPEND );
    }
}
