<?php
/**
 * ====================================
 * 客户资料模型
 * ====================================
 * Author: 9006758
 * Date: 2017-04-13
 * ====================================
 * Project: new.m.chinaskin.cn
 * File: UserCustomerModel.class.php
 * ====================================
 */
namespace Cpanel\Model;
use Common\Model\CpanelUserCenterModel;
use Common\Extend\PhxCrypt;

class UserCustomerModel extends CpanelUserCenterModel
{
    protected $tableName = 'user_customer';

	public function filter(&$params){

		if(!empty($params['keyword'])){
			$keyword = trim($params['keyword']);
			$where['custom_id']  = $keyword;
			$where['phone']  = PhxCrypt::phxEncrypt($keyword);
			$where['_logic'] = 'or';
			return $this->where($where);
		}
	}

    public function format($data){
        if(!empty($data['rows'])){
            foreach($data['rows'] as &$v){
                $v['phone'] = PhxCrypt::phxDecrypt($v['phone']);
            }
        }
        return $data;
    }

}
