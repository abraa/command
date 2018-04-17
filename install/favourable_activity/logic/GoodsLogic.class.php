<?php
/**
 * Created by PhpStorm.
 * User: 1002571
 * Date: 2017/9/1
 * Time: 14:18
 */
namespace Common\Logic;

class  GoodsLogic{

    protected $GoodsModel;                                  //商品模型

    /**
     * 获取商品模型
     * @return \GoodsModel|\Think\Model
     */
    protected function getGoodsModel(){
        if(!isset($this->GoodsModel)){
            $this->GoodsModel = D('Common/Home/Goods');
        }
        return $this->GoodsModel;
    }
    /**
     * 检查商品是否可以售卖
     * @param array|int $goods_id           商品id
     * @return array                 可以售卖商品id数组
     */
    public function checkGoodsSale($goods_id){
        if(empty($goods_id)) return array();
        if(!is_array($goods_id)){
            $goods_id = array($goods_id);
        }
        $filterGoods = C('place_order_filter.' . C('SITE_ID'));
        if (!empty($filterGoods['goods'])) {
            $goods_id = array_diff($goods_id,$filterGoods['goods']);    //药品商品，不可售卖   求差集   --去除配置id
        }
         $goodsList = $this->getGoodsModel()->getGoodsList($goods_id);          //所有商品集
         $goodsCatList = $this->getGoodsModel()->goodsExtCatList($goods_id);          //所有商品分类集

        //检查药品商品的链接
      foreach($goodsList as $key => $goods){
          if(!empty($goods['skip_link'])){
              unset($goodsList[$key]);                     //药品商品，不可售卖
              continue;
          }
          if (!empty($filterGoods['cat']) && isset($goodsCatList[$key]) && 0 < count(array_intersect($goodsCatList[$key],$filterGoods['cat']))) {
              unset($goodsList[$key]);                     //药品商品，不可售卖
              continue;
          }
      }
        return array_keys($goodsList);
    }

    /**
     * 根据商品id获取缩略图
     * @param array|int $goods_id
     * @return array
     */
    public function getGoodsThumb($goods_id){
        if(is_array($goods_id)){
            $thumbList = $this->getGoodsModel()->getThumbList($goods_id);
            foreach($thumbList as &$thumb){
                $thumb =  empty($thumb) ?  "" : C('domain_source.img_domain') .$thumb ;
            }
        }else{
            $thumb = $this->getGoodsModel()->getThumb($goods_id);
            $thumb = empty($thumb) ? "" : C('domain_source.img_domain') .$thumb ;
            $thumbList = array($goods_id => $thumb);
        }
        return $thumbList;
    }
}