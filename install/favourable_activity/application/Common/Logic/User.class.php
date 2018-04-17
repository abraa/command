<?php
/**
 * ====================================
 * 用户相关业务层
 * ====================================
 * Author: 9009123 (Lemonice)
 * Date: 2017-09-30 13:55
 * ====================================
 * File: User.class.php
 * ====================================
 */
namespace Common\Logic;
use Common\Extend\Time;
use Common\Extend\Send;

class User extends LogicData{
	private $userId = 0;                       //当前登录的用户ID
    private $UsersInfoModel = NULL;
	
	public function __construct(){
        $this->UsersInfoModel = D('Common/Home/UserInfo');
        $this->userId = $this->UsersInfoModel->getUser('user_id');
    }

    /**
     * 注册会员
     * @return array
     */
    public function register(){
        $data = $this->getDatas();
    	$userId = D('Common/Home/Users')->mobileGetUserId($data['mobile']);
    	if($userId > 0){
    		return array('user_id'=>$userId);  //用户存在
    	}
    	$password = $data['password'];
    	$data['password'] = md5(md5($data['password']));
		$data['state'] = 1;  //自动注册状态
		$data['auto_reg_time'] = Time::gmTime();  //注册时间
		$userId = $this->UsersInfoModel->add($data);  //新注册帐号
		if($userId){
            session('user_id',$userId);  //新注册的会员ID
			$msg = "下单成功！登陆瓷肌会员中心激活账号即可查看订单物流，享受积分、生日礼包等福利，账号为当前手机号，密码：".$password;
            $Send = new Send();
            $Send->send_sms($data['sms_mobile'], $userId, $data['ip'], $msg);  //发短信，不管结果是否成功
		}
		return array('new_user_id'=>$userId);  //新用户，返回字段不同，区别
    }
}
?>