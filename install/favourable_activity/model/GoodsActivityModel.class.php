<?php
/**
 * ====================================
 * 套装模型
 * ====================================
 * Author: 9009123
 * Date: 2017-09-04 18:21
 * ====================================
 * File: GoodsActivityModel.class.php
 * ====================================
 */
namespace Common\Model\Home;
use Common\Model\CommonModel;
use Common\Extend\Time;

class GoodsActivityModel extends CommonModel {
    /**
     * 根据商品ID查询出对应的套装ID
     * @param int $goodsId
     * @param int $actId
     * @return int
     */
    public function getActId($goodsId = 0, $actId = 0){
        $where = (empty($goodsId) && $actId > 0) ? array('act_id'=>$actId) : array("goods_id" => $goodsId);
        $actId = $this->where($where)->getField('act_id');
        if($actId > 0){
            return $actId;
        }
        return 0;
    }
    /**
     * 获取套装详情
     * @param int $goodsId  商品ID，套装绑定的商品ID
     * @param int $actId  套装ID
     * @param string $field  查询的字段
     * @return bool
     */
    public function getInfo($goodsId = 0, $actId = 0, $field = '*'){
        if ($goodsId <= 0 && $actId <= 0) {
            return false;
        }
        $where = (empty($goodsId) && $actId > 0) ? array('act_id'=>$actId) : array("goods_id" => $goodsId);
        $info = $this->field($field)->where($where)->order('start_time desc')->find();
        if(empty($info) && $actId > 0){
            $where = array('act_id'=>$actId);
            $info = $this->field($field)->where($where)->order('start_time desc')->find();
        }
        return $info;
    }

    /**
     * 批量获取套装详情                                 -- abraa
     * @param array $goodsId  商品ID，套装绑定的商品ID
     * @param array $actId  套装ID
     * @param string $field  查询的字段
     * @return bool
     */
    public function getList($goodsId = array() ,$actId = array(),$field = "*"){
        if ( empty($goodsId) && empty($actId)) {
            return false;
        }
        $where = empty($goodsId) ? array('act_id'=>array("in",$actId)) : array("goods_id" => array("in",$goodsId));
        $info = $this->field($field)->where($where)->order('start_time desc')->select();
        return $info;
    }

    /**
     * 获取套装商品详情
     * @param int $goodsId  套装绑定的商品ID，如果同时多个套装绑定同一个商品ID，则取最新发布的
     * @param int $actId  套装ID，如果传0则用商品ID找绑定关系
     * @return bool|mixed
     */
    public function getPackageInfo($goodsId = 0, $actId = 0, $getPackageGoods = true) {
        $packageInfo = $this->getInfo($goodsId, $actId);
        if (empty($packageInfo)) {
            return $packageInfo;
        }
        //处理套装价格等字段
        if(!empty($packageInfo['ext_info'])){
            $extInfo = unserialize($packageInfo['ext_info']);
            unset($packageInfo['ext_info']);
            $packageInfo = !empty($extInfo) ? array_merge($packageInfo, $extInfo) : $packageInfo;
        }
        //获取套装子商品及子商品属性
        if($getPackageGoods === true){
            $packageGoods = D('Common/Home/PackageGoods')->getPackageGoods($packageInfo['act_id']);
            $packageInfo['package_goods'] = !empty($packageGoods) ? $packageGoods : array();
        }
        return $packageInfo;
    }

    /**
     * 校验套装是否可售卖
     * @param array $packageInfo  套装详情，必须含有package_goods字段【子产品列表】
     * @param bool $isActivity 是否为活动商品
     * @return int
     */
    public function checkPackageSale($packageInfo = array(), $isActivity = false){
        $time = Time::gmTime();
        if($packageInfo['start_time'] > $time || $packageInfo['end_time'] <= $time){  //已开始并且未过期的
            return 0;  //套装未开始或者已过期，不可售卖
        }
        if(!isset($packageInfo['package_goods']) || empty($packageInfo['package_goods']) || !is_array($packageInfo['package_goods'])){
            return 0;  //没有子商品，不可售卖
        }
        //检查非活动商品的子商品
        if($isActivity === false){
            foreach($packageInfo['package_goods'] as $value){
                if($value['is_on_sale'] == 0){
                    return 0;  //子商品未上架，不可售卖
                }
                if (CHECK_STOCK && empty($value['kc_goods_number'])) {
                    return 0;  //子商品库存不足，不可售卖
                }
            }
        }
        return 1;
    }

    /**
     * 获取套装缩略图
     * @param int $actId 套装id
     * @return array
     */
    public function getThumb($actId = 0) {
        $thumb = $this->alias('ga')->join('LEFT JOIN __GOODS__ AS g ON ga.goods_id=g.goods_id')->where(array('act_id'=>$actId))->getField('goods_thumb');
        return empty($thumb) ? '' : $thumb;
    }

    /**
     * 批量获取套装缩略图                                    -- abraa
     * @param array $actId 套装id
     * @return array (actId => goods_thumb)
     */
    public function getThumbList($actId = array()) {
        if(empty($actId)){
            return array();
        }
        $thumb = $this->alias('ga')->join('LEFT JOIN __GOODS__ AS g ON ga.goods_id=g.goods_id')->where(array('act_id'=>array("in",$actId)))->getField('act_id,goods_thumb',true);
        return empty($thumb) ? array() : $thumb;
    }
    /**
     * 获取套装图
     * @param int $actId 套装id
     * @return array
     */
    public function getImage($actId = 0) {
        $info = $this->alias('ga')->join('LEFT JOIN __GOODS__ AS g ON ga.goods_id=g.goods_id')->field('g.goods_thumb,g.goods_img,g.original_img')->where(array('act_id'=>$actId))->find();
        return empty($info) ? array() : $info;
    }

    /**
     * 获取套装的商品ID
     * @param int $actId 套装id
     * @return array
     */
    public function getGoodsId($actId = 0) {
        $goodsId = $this->where(array('act_id'=>$actId))->getField('goods_id');
        return $goodsId>0 ? $goodsId : 0;
    }
    /**
     * 根据套装id批量获取绑定商品id                  -- abraa
     * @param array $actId 套装id
     * @return array
     */
    public function getGoodsIdList($actId = array()) {
        if(empty($actId)){return array();}
        $goodsId = $this->where(array('act_id'=>array("in",$actId)))->getField('act_id,goods_id',true);
        return $goodsId;
    }

    /**
     * 批量获取商品库存
     * @param int|array $actId   套装id
     * @return array
     */
    function getGoodsStock($actId){
        if(empty($actId)){
            return false;
        }
        $result = $this->alias('ga')->join('LEFT JOIN __GOODS__ AS g ON ga.goods_id=g.goods_id')->where(array("act_id"=>array("in",$actId)))->getField("act_id,goods_number",true);
        return $result;
    }
}
