<?php
/**
 * ====================================
 * 用户
 * ====================================
 * Author: 9009123 (Lemonice)
 * Date: 2018-01-22 16:41
 * ====================================
 * File: UsersModel.class.php
 * ====================================
 */
namespace Common\Model\Home;
use Common\Model\UserCenterModel;

class UsersModel extends UserCenterModel{
    /**
     * 获取帐号手机号
     * @param int $userId
     * @return string|mixed
     */
    public function getMobile($userId = 0){
        $userId = $userId>0 ? $userId : $this->getUser('user_id');
        if($userId <= 0){
            return '';
        }
        $mobile = $this->where(array('user_id'=>$userId))->getField('mobile');
        return (!empty($mobile) ? $mobile : '');
    }

    /**
     * 查询手机号码对应的用户ID
     * @param string $mobile
     * @return int|mixed
     */
    public function mobileGetUserId($mobile = ''){
        if(empty($mobile)){
            return 0;
        }
        $userId = $this->where("mobile = '$mobile'")->getField('user_id');	//是否已存在
        return ($userId>0 ? $userId : 0);
    }

    /**
     * 新增会员（购物车模块添加）
     * @param $data
     * @return array
     */
    public function addNewMember($data){
        $userId = $this->where("mobile = '$data[mobile]'")->getField('user_id');	//是否已存在
        if($userId > 0){
            $this->setUserInfo($userId);  //自动登录
            return array('user_id'=>$userId);  //用户存在
        }
        $password = $data['password'];
        $data['password'] = md5(md5($data['password']));
        $data['state'] = 1;  //自动注册状态
        $data['auto_reg_time'] = \Common\Extend\Time::gmTime();  //注册时间
        $userId = $this->register($data);  //新注册帐号
        if($userId){
            $msg = "下单成功！登陆瓷肌会员中心激活账号即可查看订单物流，享受积分、生日礼包等福利，账号为当前手机号，密码：".$password;
            $smsClass = new \Common\Extend\Send();
            $smsClass->send_sms($data['sms_mobile'], $userId, $data['ip'], $msg);  //发短信，不管结果是否成功
        }
        return array('new_user_id'=>$userId);  //新用户，返回字段不同，区别
    }

    /**
     * 用户注册
     * @param array $data  会员数据
     * @return bool
     */
    public function register($data) {
        if ($data) {
            $userId = $this->add($data);
            if ($userId) {
                //自动登录
                $this->setUserInfo($userId);
                return $userId;
            } else {
                return false;
            }
        } else {
            return false;
        }
    }

    /**
     * 设置登录状态、用户详情
     * @param array|int $row 用户详情（array），或者用户ID（int）
     * @return bool
     */
    public function setUserInfo($row = 0){
        if(!is_array($row)){
            $where = array(
                'user_id'=>$row,  //会员ID
            );
            $this->where($where);
            $this->field('user_id,user_name,email,mobile,user_num,custom_id,custom_no');
            $row = $this->find();
        }
        //获取当前总积分
        $rank = D('Common/Home/UserRank')->getUserRank($row['custom_id']);
        $row = array_merge($row,$rank);

        //获取头像文件地址
        $user_info = D('Common/Home/UserInfo')->field('name,photo_url')->where("user_id = '$row[user_id]'")->find();
        $row['name'] = isset($user_info['name']) ? $user_info['name'] : '';
        $row['photo_url'] = isset($user_info['photo_url']) ? $user_info['photo_url'] : '';

        $result = D('Common/Home/IntegralCenter')->getPointsLeft(0, $row['custom_id']);  //查询用户可用积分, 会计算被冻结的积分在内

        $row['points_left'] = $result['user_points'];

        if(isset($row['user_num'])){
            unset($row['user_num']);
        }

        if(isset($row['mobile']) && !empty($row['mobile'])){
            $row['mobile'] = \Common\Extend\PhxCrypt::phxDecrypt($row['mobile']);
        }
        $userInfo[$row['user_id']] = $row;
        session('userInfo',$userInfo);
        return true;
    }
}