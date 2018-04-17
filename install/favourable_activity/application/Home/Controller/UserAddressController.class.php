<?php
/**
 * ====================================
 * 发货地址 控制器
 * ====================================
 * Author: 9009123 (Lemonice)
 * Date: 2016-06-27 17:14
 * ====================================
 * File: UserAddressController.class.php
 * ====================================
 */
namespace Home\Controller;
use Common\Controller\InitController;
use Common\Logic\UserAddress;


class UserAddressController extends InitController{
    /**
     * 当前没登录的提示信息
     * @var string
     */
	private $not_login_msg = '您还未登录，请先登录';

    /**
     * 储存用户地址业务对象
     * @var UserAddress|null
     */
    private $logicUserAddress = NULL;
	
	private $dbModel = NULL;  //储存地址数据表对象
	
	//不用登录的方法名称
	private $not_login = array(
		'save','info','defaults'
	);
	
	public function __construct(){
		parent::__construct();
		$this->dbModel = D('Common/Home/UserAddress');
		$this->user_id = $this->checkLogin();  //检查登录，获取用户ID

        $this->logicUserAddress = new UserAddress();
	}

    /**
     * 获取当前登录用户的地址列表 - 需要登录才有数据
     */
	public function lists(){
		$page = max(I('request.page',1,'intval'), 1);
        $pageSize = I('request.pageSize',0,'intval');

        $this->logicUserAddress->setData('page', $page);
        $this->logicUserAddress->setData('pageSize', $pageSize);
        $result = $this->logicUserAddress->getPage();
        if($result === false){
            $error = $this->logicUserAddress->getError();
            $this->error($error);
        }
        $this->success($result);
	}

    /**
     * 保存地址
     */
	public function save(){
		$data = I('request.');  //更新、添加的数据
        if(empty($data['consignee'])){
            $this->error('请输入收货人');
        }
        if(mb_strlen($data['consignee'], 'utf8')<2 && !preg_match('/^[\x{4e00}-\x{9fa5}]+$/u', $data['consignee'])){
            $this->error('收货人必须最少2位中文');
        }
        if(mb_strlen($data['address'], 'utf8')<5 && !preg_match('/^[\x{4e00}-\x{9fa5}]+$/u', $data['address'])){
            $this->error('收货人的详细地址必须大于5位中文');
        }
        if($data['province'] <= 0){
            $this->error('请选择所在省份');
        }
        if($data['city'] <= 0){
            $this->error('请选择所在城市');
        }
        if($data['district'] <= 0){
            $this->error('请选择所在区域');
        }
        $this->logicUserAddress->setDatas($data);
        $result = $this->logicUserAddress->save();
        if($result === false){
            $error = $this->logicUserAddress->getError();
            $this->error($error);
        }
        $this->success($result);
	}

    /**
     * 删除当前登录的发货地址
     */
	public function delete(){
		$addressId = I('request.address_id',0,'intval');  //地址ID
		if($addressId <= 0){
			$this->error('请选择一个地址');
		}
        $this->logicUserAddress->setData('address_id', $addressId);
        $result = $this->logicUserAddress->delete();
        if($result === false){
            $error = $this->logicUserAddress->getError();
            $this->error($error);
        }
		$this->success(array(
			'address_id'=>$addressId,
			'affected'=>$result,
		));
	}

    /**
     * 获取地址详情
     */
	public function info(){
		$addressId = I('request.address_id',0,'intval');  //地址ID
        $this->logicUserAddress->setData('address_id', $addressId);
        $result = $this->logicUserAddress->info();
        if($result === false){
            $error = $this->logicUserAddress->getError();
            $this->error($error);
        }
        $this->success($result);
	}

    /**
     * 获取当前登录用户的默认地址详情
     */
	public function defaults(){
        $info = $this->logicUserAddress->getDefaultAddress();
		$this->success((empty($info) ? false : $info));
	}

    /**
     * 设置地址为默认地址 - 需要登录
     */
	public function setDefaults(){
		$addressId = I('request.address_id',0,'intval');  //地址ID
        if($addressId <= 0){
            $this->error('地址不存在');
        }
        $this->logicUserAddress->setData('address_id', $addressId);
        $result = $this->logicUserAddress->setDefaultAddress();
        $this->success((empty($result) ? false : $result));
	}

    /**
     * 检查当前是否登录
     * @return mixed
     */
	private function checkLogin(){
		$user_id = $this->dbModel->getUser('user_id');  //用户ID
		//检查当前方法是否不用登录
		if(in_array(strtolower(ACTION_NAME), $this->not_login)){
			return $user_id;  //不用强制登录
		}
		if(is_null($user_id) || $user_id <= 0){
			$this->error($this->not_login_msg);  //没登录
		}
		return $user_id;
	}

    /**
     * 暂时不使用本控制器默认方法，预留
     */
    public function index(){
        send_http_status(404);
    }
}