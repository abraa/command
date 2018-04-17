<?php
/**
 * ====================================
 * 用户发货地址 模型
 * ====================================
 * Author: 9009123 (Lemonice)
 * Date: 2017-09-07 17:18
 * ====================================
 * File: UserAddressModel.class.php
 * ====================================
 */
namespace Common\Model\Home;
use Common\Model\UserCenterModel;

class UserAddressModel extends UserCenterModel{
    /**
     * 用于储存默认地址ID
     * @var null
     */
    private $defaultAddressId = NULL;

    /**
     * 获取分页列表
     * @param int $page  当前页数，pageSize为0时无效
     * @param int $pageSize  每页显示多少条数据
     * @param int $userId  用户ID，默认为当前登录的帐号ID
     * @return array
     */
    public function getPage($page = 1, $pageSize = 0, $userId = 0){
        $total = 0;
        $pageTotal = 1;
        $userId = $userId>0 ? $userId : $this->user_id;
        $where = array('user_id'=>$userId);
        //是否启用分页
        if($pageSize > 0){
            $total = $this->where($where)->count();  //统计总记录数
            $this->page($page.','.$pageSize);
            $pageTotal = ceil($total / $pageSize);  //计算总页数
        }else{
            $page = 1;
        }
        $this->field('address_id,user_id,consignee,province,city,district,town,address,mobile,add_time,update_time');
        $defaultAddressId = $this->getUserDefaultAddressId();  //获取这个用户的默认地址ID
        $this->order("address_id = '$defaultAddressId' desc,update_time desc,add_time desc");
        $list = $this->where($where)->select();
        $total = $total > 0 ? $total : count($list);
        return array('page' => $page, 'pageSize' => $pageSize, 'total' => (int)$total, 'pageTotal' => $pageTotal, 'list' => $list);
    }

    /**
     * 获取地址详情
     * @param int $addressId  地址ID
     * @return array|mixed
     */
    public function getAddress($addressId = 0){
        if($addressId <= 0){
            return array();
        }
        $userId = $this->getUser('user_id');
        $this->field('address_id,user_id,consignee,province,city,district,town,address,mobile,add_time,update_time');
        $where = array('address_id'=>$addressId);
        if($userId > 0){
            $where['user_id'] = $userId;
        }
        $info = $this->where($where)->find();
        return empty($info) ? array() : $info;
    }

    /**
     * 获取某用户的默认地址详情，没有默认的话将获取最近更新的那条
     * @return mixed
     */
    public function getUserDefaultAddress(){
        $userId = $this->getUser('user_id');
        $defaultAddressId = $this->getUserDefaultAddressId();  //获取默认地址的ID
        $this->field('address_id,user_id,consignee,province,city,district,town,address,mobile,add_time,update_time');
        if($defaultAddressId > 0){  //有默认地址
            $this->where("address_id = '$defaultAddressId'");
        }else{  //没默认地址，获取最近更新的一个地址
            $this->where("user_id = '".$userId."'")->order('update_time desc,add_time desc');
        }
        $info = $this->find();
        if(!empty($info)){
            $info['is_defaults'] = $defaultAddressId > 0 ? 1 : 0;  //标志当前地址是否为默认
        }
        return empty($info) ? array() : $info;
    }

    /**
     * 获取某用户的默认地址ID
     * @return mixed
     */
    public function getUserDefaultAddressId(){
        $userId = $this->getUser('user_id');
        if(is_null($this->defaultAddressId) && $userId > 0){
            $this->defaultAddressId = D('Common/Home/UserInfo')->getDefaultAddressId();
        }
        return !empty($this->defaultAddressId) ? $this->defaultAddressId : 0;
    }

    /**
     * 设置默认地址
     * @param int $addressId
     * @param int $userId
     * @return bool
     */
    public function setUserDefaultAddressId($addressId = 0, $userId = 0){
        $userId = $userId>0 ? $userId : $this->user_id;
        $result = $this->where(array('address_id'=>$addressId,'user_id'=>$userId))->getField('address_id');
        if($result === false){
            return false;
        }
        $UserInfo = D('Common/Home/UserInfo');

        $result = $UserInfo->where(array('user_id'=>$userId))->getField('user_id');
        $UserInfo->create(array(
            'user_id'=>$userId,
            'default_address_id'=>$addressId
        ));

        if($result === false){
            $result = $UserInfo->add();
        }else{
            $result = $UserInfo->where(array('user_id'=>$userId))->save();
        }
        return array(
            'address_id'=>$addressId,
            'affected'=>$result,
        );
    }

    /**
     * 获取某个地址的手机号码
     * @param int $addressId
     * @return int|null
     */
    public function getAddressMobile($addressId = 0){
        $mobile = $this->where(array('address_id'=>$addressId))->getField('mobile');
        return empty($mobile) ? '' : $mobile;
    }

    /**
     * 删除用户地址
     * @param int $addressId  地址ID
     * @param int $userId  用户ID
     * @return mixed
     */
    public function deleteAddress($addressId = 0, $userId = 0){
        $userId = $userId>0 ? $userId : $this->user_id;

        $result = $this->where(array('address_id'=>$addressId,'user_id'=>$userId))->delete();
        if($result !== false){
            $defaultAddressId = $this->getUserDefaultAddressId();  //获取这个用户的默认地址ID
            //如果用户删除的是默认地址，则设置info表的默认地址ID为0
            if($addressId == $defaultAddressId){
                D('Common/Home/UserInfo')->where(array('user_id'=>$userId))->save(array('default_address_id'=>0));
            }
        }
        return $result;
    }

    /**
     * 把地址里面的区域ID转换成名称
     * @param $info
     * @return mixed
     */
    public function getRegion($info){
        if(empty($info)){
            return $info;
        }
        $regionId = array();
        $regionName = array();

        $allowKey = array('province','city','district','town');

        foreach($allowKey as $key){
            if(isset($info[$key]) && $info[$key] > 0){
                $regionName[$info[$key]] = $key.'_name';
                $regionId[] = $info[$key];
            }
        }
        if(!empty($regionId)){
            $region = D('Common/Home/Region')->getList($regionId);
            if(!empty($region)){
                foreach($region as $v){
                    if(isset($regionName[$v['region_id']])){
                        $info[$regionName[$v['region_id']]] = $v['region_name'];
                    }
                }
            }
        }
        return $info;
    }

    /**
     * 保存地址信息到会员中心的收货地址列表
     * @param int $userId 用户ID
     * @param string $address 地址详情
     * @return int|mixed
     */
    public function saveAddress($userId, $address){
        $addressId = isset($address['address_id']) ? intval($address['address_id']) : 0;
        $data = array();
        $data['consignee'] = isset($address['consignee']) ? $address['consignee'] : '';
        $data['province'] = isset($address['province']) ? $address['province'] : 0;
        $data['city'] = isset($address['city']) ? $address['city'] : 0;
        $data['district'] = isset($address['district']) ? $address['district'] : 0;
        $data['town'] = isset($address['town']) ? $address['town'] : 0;
        $data['address'] = isset($address['address']) ? $address['address'] : '';
        $data['attribute'] = isset($address['attribute']) ? $address['attribute'] : '';
        $data['mobile'] = isset($address['mobile']) ? $address['mobile'] : '';
        $data['update_time'] = \Common\Extend\Time::gmTime();
        if ($addressId > 0) {
            if(isset($address['address_id'])){
                unset($address['address_id']);
            }
            $this->where("user_id = '$userId' AND address_id = '$addressId'")->save($data);  //更新
        }else {
            $data['user_id'] = $userId;
            $data['add_time'] = \Common\Extend\Time::gmTime();
            $addressId = $this->add($data);  //添加新的地址
        }
        return $addressId;
    }
}