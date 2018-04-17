<?php
/**
 * ====================================
 * 商品模型
 * ====================================
 * Author: 9009123
 * Date: 2017-09-04 18:21
 * ====================================
 * File: GoodsModel.class.php
 * ====================================
 */
namespace Common\Model\Home;
use Common\Model\CommonModel;

class GoodsModel extends CommonModel {
    /**
     * 获取单品商品详情
     * @param int $goodsId
     * @param string $field
     * @return array|mixed
     */
    public function getGoodsInfo($goodsId = 0, $field = ''){
        $field = empty($field) ? 'goods_id,cat_id,goods_name,goods_number,goods_sn,is_on_sale,is_alone_sale,market_price,shop_price,skip_link' : $field;
        $goodsInfo = $this->field($field)->where(array('goods_id'=>$goodsId))->find();
        $goodsInfo = empty($goodsInfo) ? array() : $goodsInfo;
        return $goodsInfo;
    }

    /**
     * 检查是否药品商品、是否可卖
     * @param int $goodsId  goods表的商品ID
     * @return bool 【false=不可卖，true=可卖】
     */
    public function checkDrugSale($goodsId = 0){
        if($goodsId <= 0){
            return false;
        }
        //检查药品商品的链接
        $goods = $this->field('skip_link,cat_id')->where(array('goods_id'=>$goodsId))->find();
        if(!empty($goods['skip_link'])){
            return false;  //药品商品，不可售卖
        }
        //检查药品商品的分类和扩展分类
        $filterGoods = C('place_order_filter.' . C('SITE_ID'));
        if (!empty($filterGoods['goods']) || !empty($filterGoods['cat'])) {
            if (!empty($filterGoods['goods']) && in_array($goodsId, $filterGoods['goods'])) {
                return false;  //药品商品，不可售卖
            }
            if (in_array($goods['cat_id'], $filterGoods['cat'])) {
                return false;  //药品商品，不可售卖
            }
            $extCat = $this->goodsExtCat($goodsId); //识别分类和扩展分类
            if (!empty($extCat)) {
                foreach ($extCat as $extCatId) {
                    if (in_array($extCatId, $filterGoods['cat'])) {
                        return false;  //药品商品，不可售卖
                    }
                }
            }
        }
        return true;
    }

    /**
     * 获取商品分类以及扩展分类
     * @param int $goodsId
     * @return array
     */
    private function goodsExtCat($goodsId = 0) {
        $catList = array();
        $catId = $this->where(array('goods_id' => $goodsId))->getField('cat_id');
        if ($catId > 0) {
            $catList[] = $catId;
        }
        $goodsCat = M('goods_cat')->where(array('goods_id' => $goodsId))->select();
        if(!empty($goodsCat)){
            foreach ($goodsCat as $cat){
                $catList[] = $cat['cat_id'];
            }
        }
        return $catList;
    }

    /**
     * 获取商品缩略图
     * @param int $goodsId 商品id
     * @return array
     */
    public function getThumb($goodsId = 0) {
        $thumb = $this->where(array('goods_id'=>$goodsId))->getField('goods_thumb');
        return empty($thumb) ? '' : $thumb;
    }
    /**
     * 批量获取商品缩略图                     -- abraa
     * @param array $goodsId 商品id
     * @return array
     */
    public function getThumbList($goodsId = array()) {
        if(empty($goodsId)){
            return array();
        }
        $thumb = $this->where(array('goods_id'=>array("in",$goodsId)))->getField('goods_id,goods_thumb',true);
        return empty($thumb) ? array() : $thumb;
    }
    /**
     * 获取商品图
     * @param int $goodsId 商品id
     * @return array
     */
    public function getImage($goodsId = 0) {
        $info = $this->field('goods_thumb,goods_img,original_img')->where(array('goods_id'=>$goodsId))->find();
        return empty($info) ? array() : $info;
    }

    /**
     * 批量获取商品分类以及扩展分类           -- abraa
     * @param array|int $goodsId
     * @return array                array(goods_id=>array(cat_id1,...))
     */
    public function goodsExtCatList($goodsId = array()) {
        if(empty($goodsId)) return null;
        $catList = array();
        $goodsList = $this->where(array('goods_id' => array("in",$goodsId)))->field('goods_id,cat_id')->select();     //商品分类
        if(false !== $goodsList){
            foreach($goodsList as $val){
                $catList[$val['goods_id']] = array($val['cat_id']);
            }
        }
        $goodsCat = M('goods_cat')->where(array('goods_id' => array("in",$goodsId)))->select();                             //扩展分类
        if(false !== $goodsList){
            foreach($goodsCat as $cat){
                $catList[$cat['goods_id']][] = $cat['cat_id'];
            }
        }
        return $catList;
    }

    /**
     * 批量获取单品商品详情                   -- abraa
     * @param array|int $goodsId
     * @param string $field
     * @return array|mixed
     */
    public function getGoodsList($goodsId = array(), $field = ''){
        if(empty($goodsId)) return null ;
        $field = empty($field) ? 'goods_id,cat_id,goods_name,goods_number,goods_sn,is_on_sale,is_alone_sale,market_price,shop_price,skip_link' : $field;
        if(is_array($field)){
            $field[] = 'goods_id';
        }else{
            $field .= ",goods_id";              //不管字段有没有goods_id都加上
        }
        $goodsInfo = array();
        $list = $this->field($field)->where(array('goods_id'=>array("in",$goodsId)))->select();
        if(false !== $list){
            foreach($list as $v){
                $goodsInfo[$v['goods_id']] = $v;
            }
        }
        return $goodsInfo;
    }

    /**
     * 批量获取商品库存
     * @param int|array $goodsId   商品id，套装id
     * @return array
     */
    function getGoodsStock($goodsId){
        if(empty($goodsId)){
            return false;
        }
        $result = $this->where(array("goods_id"=>array("in",$goodsId)))->getField("goods_id,goods_number",true);
        return $result;
    }
}
