<?php
/**
 * 会员相关扩展类
 * Created by PhpStorm.
 * User: 9006758
 * Date: 2017/4/5
 * Time: 14:36
 */

namespace Common\Extend;


use Org\Util\Date;

class User
{

    private $userModel;
    private $customerModel;

    public function __construct()
    {
        if (is_null($this->userModel)) {
            $this->userModel = D("Home/Users");
        }
        if (is_null($this->customerModel)) {
            $this->customerModel = D('Home/UserCustomer');
        }
    }

    /**
     * 通过用户的手机号码获取用户ID
     * @param $mobile
     * @return int|mixed
     */
    public function getUserIdByMobile($mobile)
    {
        $mobile = is_phone($mobile) ? PhxCrypt::phxEncrypt($mobile) : $mobile;
        $userId = $this->userModel
            ->where(array('mobile' => $mobile))
            ->getField('user_id');
        return empty($userId) ? 0 : $userId;
    }


    /**
     * 根据客户编号返回相关会员user_id
     * @param string $custom_no 客户编号
     * @return bool|mixed
     */
    public function getUserIdByCustomNo($custom_no)
    {
        //客户编号已绑定用户的直接取出最活跃用户
        $user_id = $this->getUserId($custom_no);
        if (empty($user_id)) {
            $mobile = array();
            //根据客户编号获取手机号码
            $customerData = $this->customerModel
                ->where(array('custom_id' => $custom_no))
                ->select();
            if (!empty($customerData)) {
                foreach ($customerData as $customer) {
                    if(!empty($customer['phone'])){
                        $mobile[] = $customer['phone'];
                    }
                }
                if(empty($mobile)){
                    return (int)$user_id;
                }
                //根据手机号码获取用户
                $userInfo = $this->userModel
                    ->where(array('mobile' => array('in', $mobile)))
                    ->field('user_id,last_time')
                    ->order('last_time desc')
                    ->find();
                if (empty($userInfo) || $userInfo['last_time'] == 0) {
                    //获取不存在或用户不活跃,则已下过单的手机号为主
                    $orderModel = D('Home/OrderInfoCenter');
                    $where = array(
                        'mobile' => array('IN', $mobile),
                        'tel' => array('IN', $mobile),
                        '_logic' => 'OR'
                    );
                    $orderInfo = $orderModel
                        ->where($where)
                        ->order('add_time desc')
                        ->field('user_id,mobile,tel')
                        ->find();
                    if (!empty($orderInfo)) {
                        if (!empty($orderInfo['user_id'])) {
                            $user_id = $orderInfo['user_id'];
                        } else {
                            $mobile = !empty($orderInfo['mobile']) ? $orderInfo['mobile'] : (!empty($orderInfo['tel']) ? $orderInfo['tel'] : '');
                            if (!empty($mobile)) {
                                $user_id = $this->userModel->where(array('mobile' => $mobile))->getField('user_id');
                            }
                        }
                    }
                } else {
                    $user_id = $userInfo['user_id'];
                }
            }
        }

        if ($user_id) {
            $this->userModel->where(array('user_id' => $user_id))->setField('custom_id', $custom_no);
        }

        return $user_id;
    }

    /**
     * 根据客户编号获取用户资料（会员和客户编号已绑定）
     * @param $customNo
     * @return int
     */
    public function getUserId($customNo)
    {
        $user_id = M('IntegralMergeLog',NULL,'USER_CENTER')
            ->where(array('to_custom_id' => $customNo))
            ->getField('to_user_id');
        if(is_null($user_id) || $user_id <= 0){
            $user_id = $this->userModel
                ->where(array('custom_id' => $customNo))
                ->order('last_time desc')
                ->getField('user_id');
        }
        return empty($user_id) ? 0 : $user_id;
    }
}