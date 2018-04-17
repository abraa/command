<?php

/**
 * ====================================
 * 购物车模型
 * ====================================
 * Author: 9006765
 * Date: 2016-06-28 14:30
 * ====================================
 * File: CartModel.class.php
 * ====================================
 */

namespace Common\Model\Home;
use Common\Model\CommonModel;
use Common\Extend\Time;

class CartModel extends CommonModel {

    private $cartGoodsExtCat = NULL;                       //购物车商品分类列表

    /**
     * 保存选中商品的session字段名称
     * @var string
     */
    private $sessionKeyName = 'cart_select';
    /**
     * 用于临时缓存选择的购物车ID
     * @var array
     */
    private $CartSelect = array();

    /**
     * 获取购物车总数
     * @return int
     */
    public function getCartCount(){
        $where = $this->getUserWhere();
        $where['parent_id'] = 0;
        $count = $this->where($where)->sum('goods_number');
        return $count;
    }

    /**
     * 获取商品的详情数据 - 支持套装和单品
     * @param int $goodsId  商品ID
     * @param int $isPackage 是否为套装：1=是，0=否，会根据此字段选择是否查询套装的绑定商品ID
     * @param int $actId  套装ID
     * @return array
     */
    public function getProductInfo($goodsId = 0, $isPackage = 0, $actId = 0){
        //获取商品数据
        if ($isPackage == 0) {  //单品，直接获取goods数据
            $goodsInfo = D('Common/Home/Goods')->getGoodsInfo($goodsId);
        }else{  //查询套装，goods_id是套装绑定的商品ID查询
            $GoodsActivityModel = D('Common/Home/GoodsActivity');
            $goodsInfo = $GoodsActivityModel->getPackageInfo(($actId>0 ? 0 : $goodsId), $actId);
            if(!empty($goodsInfo)){
                //检查是否可售卖
                $goodsInfo['is_on_sale'] = $GoodsActivityModel->checkPackageSale($goodsInfo);
            }
        }
        return empty($goodsInfo) ? array() : $goodsInfo;
    }

    /**
     * 检查某个商品是否在购物车中
     * @param int $goodsId 商品ID，或者套装ID
     * @param int $isPackage 是否套装，-1=不校验，1=是，0=否
     * @param int $isGift 是否为活动商品，-1=不校验，0=非活动，其他=活动ID
     * @param int|float $goodsPrice   是否校验商品价格，-1=不校验
     * @param int $activityId 活动ID
     * @return int
     */
    public function isInCart($goodsId = 0, $isPackage = -1, $isGift = -1,$goodsPrice = -1, $activityId = -1){
        if($goodsId <= 0){
            return 0;
        }
        $where = $this->getUserWhere();
        $where['goods_id'] = $goodsId;
        if($isGift >= 0){
            $where['is_gift'] = $isGift;
        }
        if($isPackage >= 0){
            $where['extension_code'] = $isPackage == 1 ? 'package_buy' : '';  //package_buy=套装，空=单品
        }
        if($goodsPrice >=0){
            $where['goods_price'] = $goodsPrice;
        }
        if($activityId >= 0){
            $where['is_gift'] = $activityId;
        }
        $rec_id = $this->where($where)->getField('rec_id');
        return $rec_id;
    }

    /**
     * 根据是否登录来区分查询购物车的条件
     * @return array
     */
    public function getUserWhere(){
        $userId = $this->getUser('user_id');
        $where = array();
        if($userId > 0){
            $where['_complex'] = array(
                'user_id' => $userId,
                '_logic' => 'or',
                'session_id' => session_id()
            );
        }else{
            $where['session_id'] = session_id();
        }
        return $where;
    }

    /**
     * 删除购物车商品 - 如果是套装则会包括子商品都删除
     * @param int|array $recId 购物车ID
     * @return bool
     */
    public function deleteGoods($recId = 0){
        //先取消选中
        if(is_array($recId)){
            foreach($recId as $id){
                $this->selectGoods($id, false);
            }
        }else{
            $this->selectGoods($recId, false);
        }
        $where = $this->getUserWhere();
        $where['_complex'] = array(
            'rec_id' => (is_array($recId) ? array('IN', $recId) : $recId),
            '_logic' => 'or',
            'parent_id' => (is_array($recId) ? array('IN', $recId) : $recId),
        );
        $result = $this->where($where)->delete();  //删除当前购物车商品和下属子商品
        return ($result !== false ? true : false);
    }

    /**
     * 选中和反选购物车商品
     * @param int $recId 购物车ID
     * @param bool $type 类型：true=选中，false=反选
     * @return bool
     */
    public function selectGoods($recId = 0, $type = true){
        $cartSelect = $this->getSelectRecId();
        $result = in_array($recId, $cartSelect);
        if($type === true && !$result){  //选中
            $cartSelect[] = $recId;
        }else if($type === false && $result){  //反选
            unset($cartSelect[array_search($recId, $cartSelect)]);
        }
        session($this->sessionKeyName, $cartSelect);
        $this->CartSelect = $cartSelect;
        return true;
    }

    /**
     * 获取购物车选中的购物车ID
     * @param bool $is_delete  是否取完删除选中的ID
     * @return array|mixed
     */
    public function getSelectRecId($is_delete = false){
        $cartSelect = !empty($this->CartSelect) ? $this->CartSelect : session($this->sessionKeyName);
        if($is_delete === true){
            $this->CartSelect = array();
            session($this->sessionKeyName, NULL);
        }
        return empty($cartSelect) ? array() : $cartSelect;
    }

    /**
     * 累加、累减购物车数量
     * @param int $recId  购物车ID
     * @param int $goodsNumber 购物车商品数量，正数为加，负数为减
     * @return bool
     */
    public function setGoodsNumber($recId = 0, $goodsNumber = 1){
        $where = $this->getUserWhere();
        $where['rec_id'] = $recId;
        $method = 'setInc';
        if($goodsNumber < 0){  //减少数量
            $method = 'setDec';
            $number = $this->where($where)->getField('goods_number');
            if($number + $goodsNumber < 1){  //商品数量不能减到小于1
                return NULL;  //数量不能小于1的返回值是NULL
            }
        }
        $result = $this->where($where)->$method('goods_number', abs($goodsNumber));  //累加、累减
        return $result===false ? false : true;
    }

    /**
     * 获取购物车数量
     * @param int $recId  购物车ID
     * @param int $goodsId 商品ID
     * @return int
     */
    public function getGoodsNumber($recId = 0, $goodsId = 0){
        $where = $this->getUserWhere();
        if($recId > 0){
            $where['rec_id'] = $recId;
        }else{
            $where['goods_id'] = $goodsId;
        }
        $number = $this->where($where)->getField('goods_number');
        return $number>0 ? $number : 0;
    }

    /**
     * 全选、全反选购物车商品
     * @param bool $select  是否选择：true=全选，false=全反选
     * @return bool|null
     */
    public function selectAllGoods($select = true){
        $cartList = $this->getCartList('rec_id');  //获取购物车所有商品的购物车ID
        if(empty($cartList)){
            return false;  //购物车没商品
        }
        foreach($cartList as $value){
            $this->selectGoods($value['rec_id'], $select);  //选中 或者 反选某个购物车商品，忽略结果
        }
        return true;
    }

    /**
     * 检查购物车所有商品，如果有不可用的、下架的、过期的，全部处理掉
     */
    public function checkCartGoods(){
        $list = $this->getCartList('*', false, true);  //获取购物车商品列表
        $expireGoods = array();  //记录哪些已过期、已下架、不可用的商品购物车ID
        if(!empty($list)){
            $GoodsActivityModel = D('Common/Home/GoodsActivity');
            $GoodsModel = D('Common/Home/Goods');
            foreach($list as $key=>$value){
                if ($value['extension_code'] == 'package_buy') {  //套装检测
                    $packageInfo = $GoodsActivityModel->getPackageInfo(0, $value['goods_id']);
                    if(!empty($packageInfo)){
                        $packageInfo['is_on_sale'] = $GoodsActivityModel->checkPackageSale($packageInfo, ($value['is_gift'] <= 0 ? false : true));  //校验套装的是否可售卖
                        if($packageInfo['is_on_sale'] == 0){
                            $expireGoods[] = $value['rec_id'];  //此套装不可用
                        }
                    }else{
                        $expireGoods[] = $value['rec_id'];  //此套装不存在
                    }
                }else if ($value['extension_code'] == '' && $value['is_gift'] <= 0) {  //单品检测，非活动的
                    $goodsInfo = $GoodsModel->getGoodsInfo($value['goods_id']);
                    if (empty($goodsInfo) || $goodsInfo['is_on_sale'] == 0 || (CHECK_STOCK && $goodsInfo['goods_number'] <= 0)) {
                        $expireGoods[] = $value['rec_id'];  //此商品不可卖
                    }
                }
            }
            //如果有不可卖的商品，需要处理掉
            if (!empty($expireGoods)) {
                foreach($expireGoods as $recId){
                    $this->deleteGoods($recId);
                }
            }
        }
        return true;
    }

    /**
     * 查询购物车商品列表
     * @param string $field 查询的字段
     * @param bool $mustBeSelect 是否只查询选中的商品
     * @param bool $getPackageGoods 是否包含套装的子商品
     * @param int $getIsGift 是否包含活动商品，-1=所有， 0=仅非活动，其他=仅某个活动ID
     * @param int $page  分页：当前页数
     * @param int $pageSize  分页：每页查询多少条，0=不分页
     * @return array|mixed
     */
    public function getCartList($field = '*', $mustBeSelect = false, $getPackageGoods = false, $getIsGift = -1, $page = 1, $pageSize = 0){
        $cartList = array();
        $selectRecId = $this->getSelectRecId();  //获取选中的购物车ID
        //如果是查询必选并且用户没选中任何商品，则立刻返回空
        if($mustBeSelect === true && empty($selectRecId)){
            return $cartList;
        }

        $where = $this->getUserWhere();
        if($mustBeSelect === true){
            if($getPackageGoods === false){
                $where['rec_id'] = array('IN', $selectRecId);  //必须是选中的
            }else{
                $where['_complex'] = array(
                    'rec_id'=>array('IN', $selectRecId),
                    '_logic' => 'or',
                    'extension_code'=>array('EQ','package_goods'),
                );
            }
        }
        //判断是否查询套装的子商品
        if($getPackageGoods === false){
            $where['extension_code'] = array('NEQ','package_goods');
        }
        //判断是否查询赠品
        if($getIsGift >= 0){
            $where['is_gift'] = $getIsGift;
        }
        //分页
        if($pageSize > 0 && $page > 0){
            $this->page($page, $pageSize);
        }
        $list = $this->field($field)->where($where)->order('addtime ASC,rec_id ASC')->select();
        //如果有套装子商品，循环做归属
        if($getPackageGoods === true && !empty($list)){
            foreach($list as $key=>$value){
                if($value['parent_id'] > 0){  //有归属ID
                    $cartList[$value['parent_id']]['package_goods'][] = $value;
                }else{
                    if(isset($cartList[$value['rec_id']]['package_goods'])){
                        $value['package_goods'] = $cartList[$value['rec_id']]['package_goods'];
                    }
                    $cartList[$value['rec_id']] = $value;
                }
            }
            //处理没有归属套装信息的子商品
            foreach($cartList as $key=>$value){
                if(isset($value['package_goods'])){
                    unset($value['package_goods']);
                }
                if(empty($value)){
                    unset($cartList[$key]);
                }
            }
        }else{
            $cartList = $list;
        }
        $cartList = !empty($cartList) ? array_values($cartList) : array();

        //有分页的，返回值不一样
        if($pageSize > 0 && $page > 0){
            $count = $this->where($where)->count();  //统计数量
            $amount = $this->getAmount();  //统计选中的商品总金额
            return array(
                'list'=>$cartList,
                'amount'=>($amount>0 ? $amount : 0),
                'count'=>$count,
                'page'=>$page,
                'page_total'=>ceil($count/$pageSize),
            );
        }
        return $cartList;
    }

    /**
     * 获取当前购物车选中的商品价格
     * @return int
     */
    public function getAmount(){
        $amount = 0;
        $recIds = $this->getSelectRecId();
        if(!empty($recIds)){
            $where = $this->getUserWhere();
            $where['rec_id'] = array('IN',$recIds);
            $amount = $this->where($where)->sum('goods_price*goods_number');  //统计选中的商品总金额
        }
        return ($amount > 0 ? $amount : 0);
    }

    /**
     * 添加商品进入购物车
     * @param int $goodsNumber  购买数量
     * @param array $goodsInfo  商品详情
     * @param array $packageInfo  套装详情
     * @param int $isGift  活动ID，0=非活动
     * @param int $isReal  是否实体商品，1=是，0=否
     * @param bool $startTrans 是否开启事务，true or false
     * @return bool
     */
    public function addToCart($goodsNumber = 1, $goodsInfo = array(), $packageInfo = array(), $isGift = 0, $isReal = 1, $startTrans = true){
        if(empty($goodsInfo) && empty($packageInfo)){
            return false;
        }
        $goodsData = array();
        if(!empty($packageInfo)){
            $goodsData['goods_id'] = $packageInfo['act_id'];
            $goodsData['goods_sn'] = (!empty($packageInfo['unique_id']) ? $packageInfo['unique_id'] : '');
            $goodsData['goods_name'] = isset($packageInfo['goods_name'])&&!empty($packageInfo['goods_name']) ? $packageInfo['goods_name'] : $packageInfo['act_name'];
            $goodsData['market_price'] = isset($packageInfo['market_price']) ? $packageInfo['market_price'] : 0;
            $goodsData['goods_price'] = $packageInfo['package_price'];
            $goodsData['is_real'] = $isReal;
            $goodsData['extension_code'] = 'package_buy';
            $goodsData['parent_id'] = 0;
        }else if(!empty($goodsInfo)){
            $goodsData['goods_id'] = $goodsInfo['goods_id'];
            $goodsData['goods_sn'] = $goodsInfo['goods_sn'];
            $goodsData['goods_name'] = $goodsInfo['goods_name'];
            $goodsData['market_price'] = $goodsInfo['market_price'];
            $goodsData['goods_price'] = $goodsInfo['shop_price'];
            $goodsData['is_real'] = isset($goodsInfo['is_real']) ? intval($goodsInfo['is_real']) : 0;
            $goodsData['is_real'] = $isReal;
            $goodsData['extension_code'] = '';
            $goodsData['parent_id'] = 0;
        }
        $goodsData['user_id'] = $this->getUser('user_id');
        $goodsData['session_id'] = session_id();
        $goodsData['is_gift'] = $isGift;
        $goodsData['goods_number'] = $goodsNumber;
        $goodsData['addtime'] = Time::gmTime();

        if($startTrans === true){
            $this->startTrans(); //开启事务
        }
        $recId = $this->add($goodsData);
        if($recId > 0){  //添加主商品成功
            if($goodsData['extension_code'] == 'package_buy'){
                $result = $this->addPackageGoodsToCart($recId, $packageInfo, $isGift, $isReal);
                if($result === false){
                    if($startTrans === true){
                        $this->rollback();
                    }
                    return false;
                }
            }
        }
        $this->selectGoods($recId);  //选中
        if($startTrans === true){
            $this->commit();
        }
        return true;
    }

    /**
     * 把套装的子商品插入购物车并且归属到套装
     * @param int $parent_id  上级rec_id
     * @param array $packageInfo 套装详情
     * @param int $isGift 活动ID，0=非活动
     * @param int $isReal 是否实体商品，1=是，0=否
     * @return array|bool
     */
    private function addPackageGoodsToCart($parent_id = 0, $packageInfo = array(), $isGift = 0, $isReal = 1){
        if(empty($packageInfo) || empty($packageInfo['package_goods'])){
            return false;
        }
        $userId = $this->getUser('user_id');
        $sessionId = session_id();
        $time = Time::gmTime();
        foreach ($packageInfo['package_goods'] as $k => $v) {
            $goodsData = array();
            $goodsData['goods_id'] = $v['goods_id'];
            $goodsData['goods_sn'] = $v['goods_sn'];
            $goodsData['goods_name'] = $v['goods_name'];
            $goodsData['market_price'] = $v['market_price'];
            $goodsData['goods_price'] = $v['shop_price'];
            $goodsData['is_real'] = $v['is_real'];
            $goodsData['extension_code'] = 'package_goods';
            $goodsData['parent_id'] = $parent_id;
            $goodsData['user_id'] = $userId;
            $goodsData['session_id'] = $sessionId;
            $goodsData['is_real'] = (isset($v['is_real']) ? intval($v['is_real']) : $isReal);
            $goodsData['is_gift'] = $isGift;  //活动ID
            $goodsData['goods_number'] = $v['goods_number'];
            $goodsData['goods_price'] = $v['shop_price'];
            $goodsData['addtime'] = $time;
            $result = $this->add($goodsData);
            if($result === false){
                return false;
            }
        }
        return true;
    }

    /**
     * 检查购物车是否有包邮商品
     * @return bool
     */
    public function haveShippingGoods(){
        $where = $this->getUserWhere();
        $where['is_shipping'] = 1;
        $rec_id = $this->where($where)->getField('rec_id');
        return $rec_id>0 ? true :false;
    }

    /**
     * 获取当前用户所有购物车ID
     * @return bool
     */
    public function getAllRecId(){
        $where = $this->getUserWhere();
        $result = $this->field('rec_id')->where($where)->select();
        $recId = array();
        if(!empty($result)){
            foreach($result as $value){
                $recId[] = $value['rec_id'];
            }
        }
        return $recId;
    }

    /**
     * 获取购物车列表所有商品所属分类
     * @Author abraa(1002571)
     * @param $cartList
     * @return array
     */
    protected function getCartExtCat($cartList= array()){
        if(empty($cartList)){
            $field = 'rec_id,goods_id,is_gift,parent_id,extension_code';
            $cartList = $this->getCartList($field,true);
        }
        //将所有商品进行分类 -- 普通商品 | 套装
        $goodsIdList = array();
        $packageIdList = array();
        foreach($cartList as $goods){
            if(0 == strcmp("package_buy",$goods['extension_code'])){        //是套装
                $packageIdList[$goods['rec_id']] = $goods['goods_id'];
            }else{                                                          //是商品
                $goodsIdList[$goods['rec_id']] = $goods['goods_id'];
            }
        }
        //===== 套装====
        //获取套装绑定商品id
        $result = D('Common/Home/GoodsActivity')->getGoodsIdList($packageIdList);
        if(!empty($result)){
            foreach($packageIdList as &$v){
                $v = $result[$v];
            }
        }
        //将套装和商品id合并
        $goodsIdList = array_merge($goodsIdList,$packageIdList);
        //=== 商品=====
        //获取商品分类
        $extCatList  = D('Common/Home/Goods')->goodsExtCatList($goodsIdList);
        //将对应商品分类数组绑定到对应购物车rec_id
        foreach($goodsIdList as $key =>$goods_id){
            $goodsIdList[$key] = $extCatList[$goods_id];
        }
        return $goodsIdList;

    }

    /**
     * 获取购物车列表中指定rec_id的商品所属分类列表
     * @Author abraa(1002571)
     * @param $rec_id
     * @param $cart_list
     * @return mixed|array  商品分类数组
     */
    public function getCartGoodsExtCat($rec_id,$cart_list){
        if(!isset($this->cartGoodsExtCat)||!isset($this->cartGoodsExtCat[$rec_id])){
            $this->cartGoodsExtCat = $this->getCartExtCat($cart_list);
        }
        return $this->cartGoodsExtCat[$rec_id];
    }

    /**
     * 统计购物车信息
     * @Author abraa(1002571)
     * @param array $cart_list
     * @param bool $extCat              是否统计分类信息
     * @return array
     */
    public function totalCart($cart_list = array(),$extCat = false){
        if(empty($cart_list)){
            $cart_list = $this->getCartList("rec_id,goods_id,goods_number,goods_name,goods_price,market_price,is_gift,extension_code",true);
        }
        $result = array(
            'have_gift'=>0,                                     //是否有活动商品
            'have_package'=>0,                                  //是否有套装
            'market_price'=>0,                                  //市场价格
            'goods_price'=>0,                                   //售卖价格
            'goods_number'=>0,                                  //商品数量
            'goods_ids'=>array(),                               //单品id列表
            'gift_ids'=>array(),                                //活动id列表
            'package_ids'=>array(),                             //套装id列表
            'cat_ids'=>array(),                                 //分类id列表

        );
        foreach($cart_list as $value){
            if(isset($value['is_gift']) && $value['is_gift'] > 0){                                   //是活动
                $result['have_gift'] = 1;
                $result['gift_ids'][] = $value['is_gift'];                                          //添加到活动id
            }
            if(isset($value['extension_code']) && $value['extension_code'] == 'package_buy'){       //是套装
                $result['have_package'] = 1;
                $result['package_ids'][] = $value['goods_id'];                                          //添加到套装id
            }else{                                                                                   //是单品
                $result['goods_ids'][] = $value['goods_id'];                                          //添加到套装id
            }
            if($extCat){                                                                                //需要获取分类       --用于检查是否存在指定分类
                $cats = $this->getCartGoodsExtCat($value['rec_id'],$cart_list);
                if(is_array($cats)){
                $result['cat_ids'] = array_merge($result['cat_ids'],array_diff($cats,$result['cat_ids']));   //分类合并并去重
                }
            }
            $result['goods_price'] += $value['goods_price'] * $value['goods_number'];
            $result['market_price'] += $value['market_price'] * $value['goods_number'];
            $result['goods_number'] += $value['goods_number'];
        }
        return $result;
    }
}
