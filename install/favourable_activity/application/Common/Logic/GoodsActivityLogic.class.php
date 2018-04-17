<?php
/**
 * Created by PhpStorm.
 * User: 1002571
 * Date: 2017/9/1
 * Time: 14:19
 */
namespace Common\Logic;

class   GoodsActivityLogic{

    protected $GoodsActivityModel;                          //套装模型
    protected $GoodsModel;                                  //商品模型
    protected $PackageGoodsModel;                            //套装子商品模型

    /**
     * 获取套装模型
     * @return \GoodsActivityModel|\Think\Model
     */
    function getGoodsActivityModel(){
        if(!isset($this->GoodsActivityModel)){
            $this->GoodsActivityModel  = D('Common/Home/GoodsActivity');
        }
        return $this->GoodsActivityModel;
    }

    /**
     * 获取商品模型
     * @return \GoodsModel|\Think\Model
     */
    protected function getGoodsModel(){
        if(empty($this->GoodsModel)){
            $this->GoodsModel = D('Common/Home/Goods');
        }
        return $this->GoodsModel;
    }

    /**
     * 获取套装子商品模型
     * @return \PackageGoodsModel|\Think\Model
     */
    protected function getPackageGoodsModel(){
        if(empty($this->PackageGoodsModel)){
            $this->PackageGoodsModel = D('Common/Home/PackageGoodsModel');
        }
        return $this->PackageGoodsModel;
    }

    /**
     * 获取套装子商品列表
     * @param array|int $act_id
     * @return array
     */
    public function getPackageGoods($act_id){
        $result = array();
        $list = $this->getPackageGoodsModel()->getPackageGoods($act_id);
        foreach($list as $v){
            if(!isset($result[$v['package_id']])){
                $result[$v['package_id']] = array();
            }
            $result[$v['package_id']][] = $v;
        }
        return $result;           //array("套装id"=>array(子商品数组1 , ...))
    }

    /**
     * 检查活动商品是否可以售卖9+
     * @param array|int $act_id           套装id
     * @return bool
     */
    function checkGoodsActivitySale($act_id){
        if(empty($act_id)) return false;
        $packageInfo = $this->getGoodsActivityModel()->getPackageInfo(0,$act_id);       //获取套装信息
        if($this->getGoodsActivityModel()->checkPackageSale($packageInfo)){             //检查是否可以售卖
            return true;
        }else{
            return false;
        }
    }

    /**
     * 检查活动商品是否可以售卖   参数使用 in 处理 (无论是不是数组)
     * @param array|int $act_id_list
     * @return array     可以售卖的套装id
     */
    function checkGoodsActivitySaleList($act_id_list){
        if( empty($act_id_list)) return array();
        $resultId = array();
        $packageList = $this->getGoodsActivityModel()->getList(0,$act_id_list);         //套装列表

        $packageGoodsList  = $this->getPackageGoods($act_id_list);                      //所有套装子商品列表

        foreach($packageList as $packageInfo){                                          //遍历处理是否可售卖
            if(!empty($packageInfo['ext_info'])){
                $extInfo = unserialize($packageInfo['ext_info']);
                unset($packageInfo['ext_info']);
                $packageInfo = !empty($extInfo) ? array_merge($packageInfo, $extInfo) : $packageInfo;
            }
            //获取套装子商品及子商品属性
            $packageInfo['package_goods'] = !empty($packageGoodsList[$packageInfo['act_id']]) ? $packageGoodsList[$packageInfo['act_id']] : array();
            if($this->getGoodsActivityModel()->checkPackageSale($packageInfo)){         //当前套装可以售卖
                $resultId[] = $packageInfo['act_id'];
            }
        }
        return empty($resultId) ? array() : $resultId;
    }
    /**
     * 获取活动商品套装缩略图
     * @param array|string $act_id  活动套装id
     * @return array            act_id => 图片路径
     */
    function getGoodsActivityThumb($act_id){
        if(empty($act_id)){
            return array();
        }
        $thumbList = $this->getGoodsActivityModel()->getThumbList($act_id);
        foreach($thumbList as &$thumb){
            $thumb =  empty($thumb) ?  "" : C('domain_source.img_domain') .$thumb ;
        }
        return $thumbList;
    }
}