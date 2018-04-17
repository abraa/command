<?php
/**
 * ====================================
 * 用户详情 - 用户扩展数据模型
 * ====================================
 * Author: 9009123 (Lemonice)
 * Date: 2017-09-07 17:41
 * ====================================
 * File: UserInfoModel.class.php
 * ====================================
 */
namespace Common\Model\Home;
use Common\Model\UserCenterModel;

class UserInfoModel extends UserCenterModel{
    /**
     * 获取帐号默认地址
     * @param int $userId
     * @return int|mixed
     */
	public function getDefaultAddressId($userId = 0){
        $userId = $userId>0 ? $userId : $this->getUser('user_id');
        if($userId <= 0){
            return 0;
        }
        $addressId = $this->where(array('user_id'=>$userId))->getField('default_address_id');
        return ($addressId>0 ? $addressId : 0);
    }

    /**
     * 设置帐号默认地址
     * @param int $addressId
     * @param int $userId
     * @return bool
     */
    public function setDefaultAddressId($addressId = 0, $userId = 0){
        $userId = $userId>0 ? $userId : $this->getUser('user_id');
        if($userId <= 0 || $addressId <= 0){
            return false;
        }
        $result = $this->where(array('user_id'=>$userId))->save(array('default_address_id'=>$addressId));
        return $result;
    }

    /**
     * 检查是否有存在默认地址，没有则自动设置
     * @param int $addressId  地址ID
     * @param int $userId  用户ID，如果不传则自动获取当前登录的
     * @return bool
     */
    public function checkDefaultAddress($addressId = 0, $userId = 0){
        $userId = $userId>0 ? $userId : $this->user_id;
        if($addressId > 0 && $userId > 0){
            $user = $this->field('default_address_id')->where(array('user_id'=>$userId))->find();
            if(!isset($user['default_address_id'])){  //不存在用户详情记录
                $this->add(array(
                    'user_id'=>$userId,
                    'default_address_id'=>$addressId
                ));
            }else if(empty($user['default_address_id'])){  //有详情，但是没设置默认地址
                $this->setDefaultAddressId($addressId, $userId);  //保存默认地址
            }
            return true;
        }
        return false;
    }

    /**
     * 获取当前登录用户的积分倍数，如果是生日则会双倍
     * @return int  倍数
     */
    public function checkBirthdayIntegralMultiple($userId = 0){
        $userId = $userId>0 ? $userId : $this->user_id;
        $multiple = 1;  //默认一倍
        if($userId > 0){
            $birthday = $this->where("user_id = '".$userId."'")->getField('birthday');
            if(date('m-d',$birthday) == date('m-d')){  //是否今天生日
                $multiple = 2;  //今天生日，两倍积分
            }
        }
        return $multiple;
    }
}