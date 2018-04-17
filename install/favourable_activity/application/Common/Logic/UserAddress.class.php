<?php
/**
 * ====================================
 * 用户地址相关业务处理
 * ====================================
 * Author: 9009123 (Lemonice)
 * Date: 2017-09-07 17:32
 * ====================================
 * File: UserAddress.class.php
 * ====================================
 */
namespace Common\Logic;
use Common\Extend\Time;

class UserAddress extends LogicData{
    /**
     * 地址模型实例化对象
     * @var \Model|null|\Think\Model
     */
    private $UserAddressModel = NULL;
    /**
     * 会员ID，没登录则为0
     * @var int
     */
    private $userId = 0;
    /**
     * 用来保存地址属性的分隔符，请不要修改
     * @var string
     */
    private $attributeExt = '|+_+|';

    public function __construct() {
        parent::__construct();

        $this->UserAddressModel = D('Common/Home/UserAddress');
        $this->userId = $this->UserAddressModel->getUser('user_id');
    }

    /**
     * 获取某个用户的地址列表
     */
    public function getPage(){
        $page = $this->getData('page');  //当前页数
        $pageSize = $this->getData('pageSize');  //每页显示多少条，0为不分页

        $data = $this->UserAddressModel->getPage($page,$pageSize);
        if(!empty($data['list'])){
            $defaultAddressId = $this->UserAddressModel->getUserDefaultAddressId();  //获取这个用户的默认地址ID
            foreach($data['list'] as $key=>$value){
                $value['is_defaults'] = 0;
                if($value['address_id'] == $defaultAddressId){
                    $value['is_defaults'] = 1;
                }
                $data['list'][$key] = $this->phxDecode($value);
            }
        }
        return $data;
    }

    /**
     * 添加地址、保存地址
     * @return array
     */
    public function save(){
        $addressId = intval($this->getData('address_id'));  //地址ID
        $data['consignee'] = trim($this->getData('consignee'));  //姓名
        $data['mobile'] = trim($this->getData('mobile'));  //手机号码
        $data['province'] = intval($this->getData('province'));  //省份
        $data['city'] = intval($this->getData('city'));  //城市
        $data['district'] = intval($this->getData('district'));  //区域
        $data['town'] = intval($this->getData('town'));  //街道
        $data['address'] = trim($this->getData('address'));  //详细地址
        $data['attribute'] = trim($this->getData('attribute'));  //属性

        if($data['address_id'] > 0){  //编辑
            if(!empty($data['mobile'])){
                $result = $this->checkMobile($data['address_id'], $data['mobile']);  //检查手机号码是否有修改，如果有修改则校验格式
                if($result === false){
                    return false;
                }else if(is_null($result)){  //不修改
                    unset($data['mobile']);  //等于空表示不修改手机号码
                }
            }else if(empty($data['mobile'])){
                unset($data['mobile']);  //等于空表示不修改手机号码
            }
        }else {  //添加
            if (empty($data['mobile'])) {
                $this->setError('请输入手机号码');
                return false;
            }
            if (!isMobile($data['mobile'])) {
                $this->setError('请输入正确的手机号码');
                return false;
            }
        }

        $data = $this->phxEncode($data);

        if($this->userId > 0){  //有登录的，插入到数据库
            $data['user_id'] = $this->userId;
            if($addressId > 0){  //编辑
                $data['update_time'] = Time::gmTime();
                $result = $this->UserAddressModel->where(array('user_id'=>$this->userId,'address_id'=>$addressId))->save($data);
            }else{  //添加
                $data['add_time'] = Time::gmTime();
                $result = $this->UserAddressModel->add($data);
                if($result > 0){
                    $addressId = $result;
                    $result = 1;
                }
            }
            if($result !== false){
                D('Common/Home/UserInfo')->checkDefaultAddress($addressId);  //如果没有设置默认地址，则设置为默认地址
            }
        }else{  //没登录的，保存到session
            $data['address_id'] = 0;
            session('new_consignee', $data);
            $addressId = 0;
            $result = 1;
        }

        return array(
            'address_id'=>$addressId,
            'affected'=>$result,
        );
    }

    /**
     * 获取某个地址详情
     * @return bool|mixed
     */
    public function info(){
        $addressId = intval($this->getData('address_id'));  //地址ID
        //如果没登录，则获取session的返回
        if($this->userId <= 0){
            $info = $this->getUserAddress();  //获取session
            return $info;
        }
        if($addressId <= 0){
            $this->setError('地址不存在');
            return false;
        }
        $info = $this->UserAddressModel->getAddress($addressId);
        if(!empty($info)){
            $info = $this->infoFormat($info);
        }else{
            $this->setError('地址不存在');
            return false;
        }
        return $info;
    }

    /**
     * 设置默认地址
     */
    public function setDefaultAddress(){
        $addressId = intval($this->getData('address_id'));  //地址ID
        if($addressId <= 0){
            $this->setError('地址不存在');
            return false;
        }
        return $this->UserAddressModel->setUserDefaultAddressId($addressId);
    }

    /**
     * 删除地址
     */
    public function delete(){
        $addressId = intval($this->getData('address_id'));  //地址ID
        if($addressId <= 0){
            $this->setError('地址不存在');
            return false;
        }
        return $this->UserAddressModel->deleteAddress($addressId);
    }

    /**
     * 获取当前登录帐号的默认地址 或 未登录的临时地址
     */
    public function getDefaultAddress(){
        //如果没登录，则获取临时保存的地址
        if($this->userId <= 0){
            $info = $this->getUserAddress();  //获取session
        }else{
            $info = $this->UserAddressModel->getUserDefaultAddress();
        }
        $info = $this->infoFormat($info);
        return $info;
    }

    /**
     * 获取用户的地址详情
     * @param int $addressId
     * @return mixed
     */
    public function getUserAddress($addressId = 0){
        $newConsignee = session('new_consignee');
        if($addressId == 0 && !empty($newConsignee)){ //如果有新增地址，则取新增的地址
            return $newConsignee;
        }else{
            $addressId = $addressId > 0 ? $addressId : intval(session('default_address_id'));  //用户下单后，会把新地址ID存放在session('default_address_id')
            $info = $this->UserAddressModel->getAddress($addressId);
            if(isset($info['mobile'])){
                $info['encode_mobile'] = $info['mobile'];
            }
            $info = $this->infoFormat($info);
            return $info;
        }
    }

    /**
     * 格式化地址详情
     * @param $info
     * @return mixed
     */
    public function infoFormat($info){
        if(empty($info)){
            return $info;
        }
        //解析出地址属性
        $info = $this->phxDecode($info);
        //获取省份、城市、村镇、街道
        $info = $this->UserAddressModel->getRegion($info);
        //格式化时间
        $info['add_time'] = Time::localDate('Y-m-d H:i:s', $info['add_time']);
        $info['update_time'] = Time::localDate('Y-m-d H:i:s', $info['update_time']);
        return $info;
    }

    /**
     * 地址中加密字段的解密
     * @param array $info 地址详情
     * @return mixed
     */
    private function phxDecode($info = array()){
        if(empty($info)){
            return $info;
        }
        //解析出地址属性
        if(!empty($info['address'])){
            $info['attribute'] = ($info['attribute']!='' ? $info['attribute'] : '');
            if(strstr($info['address'],$this->attributeExt)){
                $address = explode($this->attributeExt,$info['address']);
                $info['attribute'] = isset($address[0]) ? $address[0] : '';
                $info['address'] = isset($address[1]) ? $address[1] : '';
            }
        }
        //解密手机号码
        if(isset($info['mobile']) && !empty($info['mobile'])){
            $info['mobile'] = \Common\Extend\PhxCrypt::phxDecrypt($info['mobile']);
        }
        return $info;
    }

    /**
     * 地址中加密字段的加密
     * @param array $info 地址详情
     * @return mixed
     */
    private function phxEncode($info = array()){
        if(empty($info)){
            return $info;
        }
        //手机号码加密
        if(isset($info['mobile'])){
            $info['mobile_source'] = $info['mobile'];
            $info['mobile'] = $info['mobile']!='' ? \Common\Extend\PhxCrypt::phxEncrypt($info['mobile']) : '';
        }
        //地址属性要做处理
        if(isset($info['address'])){
            $info['attribute'] = isset($info['attribute']) ? $info['attribute'] : '';
            $info['address'] = $info['attribute'] . $this->attributeExt . $info['address'];  //拼接属性
        }
        return $info;
    }

    /**
     * 校验提交的手机号码格式是否正确
     * 如果提交的值跟原来地址的手机号码一致则为不修改处理
     * @param int $addressId
     * @param string $mobile
     * @return bool|null
     */
    public function checkMobile($addressId = 0, $mobile = ''){
        if($addressId <= 0 && isMobile($mobile)){
            return true;
        }
        if(empty($mobile)){
            return false;
        }
        if(!empty($mobile)){
            $mobileData = $this->UserAddressModel->getAddressMobile($addressId);  //获取这个地址的手机号码，做对比
            if(!empty($mobileData)){
                $mobileData = \Common\Extend\PhxCrypt::phxDecrypt($mobileData);  //解密出来，和提交的手机号码对比
                if($mobileData != $mobile){  //不相等，说明有重新修改手机号码
                    if(!isMobile($mobile)){  //校验格式
                        $this->setError('请输入正确的手机号码');
                        return false;
                    }
                    return true;  //有修改
                }
                return NULL;  //不修改
            }else if(!isMobile($mobile)){  //这个地址之前没有保存手机号码，校验格式
                $this->setError('请输入正确的手机号码');
                return false;
            }
            return true;  //允许修改
        }
        return NULL;  //不修改
    }

    /**
     * 根据省份计算运费
     * @return int|mixed
     */
    public function calculateFee(){
        $province = intval($this->getData('province'));
        $addressId = intval($this->getData('address_id'));
        $shippingFee = $this->getData('shipping_fee');  //基础邮费
        $shippingFee = $shippingFee >=0 ? $shippingFee : C('shipping_fee');  //默认基础邮费
        $remoteAddress = C('remote_address');  //偏远地址的省份ID

        $consignee = $this->getUserAddress($addressId);  //获取填写的收货地址
        if((!isset($consignee['province']) || $consignee['province'] <= 0) && $province > 0){
            $consignee['province'] = $province;  //这里是为了兼容推广的软文下单
        }
        if(isset($remoteAddress[$consignee['province']])){
            $shippingFee += $remoteAddress[$consignee['province']];  //偏远地址，基础邮费+偏远地址的费用
        }
        return $shippingFee;
    }

    /**
     * 检查地址的完整性
     * @return bool
     */
    public function checkConsigneeInfo(){
        $addressId = intval($this->getData('address_id'));
        $consignee = $this->getUserAddress($addressId);  //获取填写的收货地址
        if(empty($consignee)){
            return false;
        }
        $res =(!empty($consignee['consignee']) &&
                !empty($consignee['province']) &&
                !empty($consignee['address']) &&
                !empty($consignee['mobile'])) || (!empty($consignee['consignee']) &&
                !empty($consignee['province']) &&
                !empty($consignee['address']));

        if ($res){
            $RegionModel = D('Common/Home/Region');
            if (empty($consignee['province'])){
                /* 没有设置省份，检查当前国家下面有没有省份 */
                $pro = $RegionModel->where("parent_id = '0'")->getField('region_id');
                if(empty($pro)){
                    return false;
                }
            }elseif (empty($consignee['city'])){
                /* 没有设置城市，检查当前省下面有没有城市 */
                $city = $RegionModel->where("parent_id = '$consignee[province]'")->getField('region_id');
                if(empty($city)){
                    return false;
                }
            }elseif (empty($consignee['district'])){
                $dist = $RegionModel->where("parent_id = '$consignee[city]'")->getField('region_id');
                if(empty($dist)){
                    return false;
                }
            }
            return $consignee;
        }
        return false;
    }
}