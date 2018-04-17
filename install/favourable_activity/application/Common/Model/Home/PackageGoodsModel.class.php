<?php
/**
 * ====================================
 * 套装模型
 * ====================================
 * Author: 9009123
 * Date: 2017-09-04 18:21
 * ====================================
 * File: PackageGoodsModel.class.php
 * ====================================
 */
namespace Common\Model\Home;
use Common\Model\CommonModel;

class PackageGoodsModel extends CommonModel {
    /**
     * 获取套装子商品列表                            -- abraa + 支持id数组
     * @param int|array $actId  套装ID
     * @return bool|mixed
     */
    public function getPackageGoods($actId = 0) {
        if (empty($actId) || $actId<=0) {
            return false;
        }
        if(!is_array($actId)) {
            $actId = array($actId);
        }
        return $this
            ->field('pg.package_id, pg.goods_id, pg.goods_number, g.goods_sn, g.goods_name,g.shop_price, g.market_price, g.goods_thumb, g.is_real,g.goods_number as kc_goods_number,g.is_on_sale,g.skip_link')
            ->alias('pg')
            ->join("LEFT JOIN __GOODS__ AS g ON g.goods_id = pg.goods_id")
            ->where(array("pg.package_id"=>array("in",$actId)))
            ->select();
    }
}
