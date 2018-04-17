<?php
/**
 * ====================================
 * 数据权限相关操作
 * ====================================
 * Author: 9009123 (Lemonice)
 * Date: 2016 2016/11/29 15:58
 * ====================================
 * File: DataPowerController.class.php
 * ====================================
 */
namespace Cpanel\Controller;
use Common\Controller\CpanelController;
use Cpanel\Model\DataPowerModel;

class DataPowerController extends CpanelController {
    protected $tableName = 'DataPower';

    protected $allowAction = array('starQQCompany','starQQGroup');

	/*
	*	获取特殊权限select数据
	*	@Author Lemonice
	*	@param  array $params 提交上来的参数
	*	@return nothing
	*/
	public function power(){
		if(isset($_POST['dosubmit'])){
			$post = I('post.');
            $user_id = isset($post['user_id']) ? intval($post['user_id']) : 0;
			$power = isset($post['power']) ? $post['power'] : array();
            if($user_id <= 0){
                $this->error('请选择用户');
            }
            $this->dbModel->where(array('user_id'=>$user_id))->delete();
            if(!empty($power)){
                foreach($power as $power_name=>$value){
                    if(is_array($value)){
                        $power_value = array_filter($value);
                        $power_value = !empty($power_value) ? serialize($power_value) : '';
                    }else{
                        $power_value = serialize(array(trim($value)));
                    }
                    $id = $this->dbModel->where(array('user_id'=>$user_id,'power_name'=>$power_name))->getField('id');
                    $this->dbModel->create(array('user_id'=>$user_id,'power_name'=>$power_name,'power_value'=>$power_value));
                    if($id > 0){
                        $this->dbModel->where(array('id'=>$id))->save();
                    }else{
                        $this->dbModel->add();
                    }
                }
            }
			$this->success(L('SAVE') . L('SUCCESS'), '', true);
		}

		$user_id = I('request.user_id',0,'intval');
		$power = D('DataPower')->getPower($user_id);  //获取已经设置的权限
        $power_list = D('DataPower')->getPowerList();  //获取所有权限

        $this->assign('power', $power);
		$this->assign('power_list', $power_list);
		$this->assign('user_id', $user_id);
		
        $this->display();
	}
}