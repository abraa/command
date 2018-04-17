<?php
/**
 * ====================================
 * 会员中心商品属性模型
 * ====================================
 * Author: 9006758
 * Date: 2017-04-08
 * ====================================
 * Project: new.m.chinaskin.cn
 * File: GoodsCenterModel.class.php
 * ====================================
 */
namespace Cpanel\Model;
use Common\Model\CpanelUserCenterModel;


class GoodsCenterModel extends CpanelUserCenterModel
{
    protected $tableName = 'goods';

    protected $_validate = array(
        array('goods_name', 'require', '商品名称不能为空'),
        array('cat_id', '/^\d+$/', '请选择商品分类'),
        array('goods_sn', 'require', '请填写货号'),
    );

	public function filter(&$params){
		$params['sort'] = !empty($params['sort']) ? $params['sort'] : 'goods_id';
		$params['order'] = !empty($params['order']) ? $params['order'] : 'desc';

		if(!empty($params['keyword'])){
			$keyword = trim($params['keyword']);
			$search['g.goods_name']  = array('like', "%$keyword%");
            $search['g.goods_sn']  = $keyword;
            $search['_logic'] = 'or';
		}
        if(!empty($params['cat_id'])){
            if(!empty($search)){
                $cat_id['_complex'] = $search;
            }
            $cat_id['g.cat_id'] = intval($params['cat_id']);
        }
        if(!empty($search) && $cat_id){
            $where = $cat_id;
        }elseif(!empty($search) && empty($cat_id)){
            $where = $search;
        }else if(empty($search) && !empty($cat_id)){
            $where = $cat_id;
        }
        return $this->alias('g')
                ->field("g.goods_id,g.cat_id,g.goods_name,g.goods_sn,g.market_price,g.shop_price,g.is_on_sale,c.cat_name")
                ->join("left join __CATEGORY__ AS c on g.cat_id=c.cat_id")
                ->where($where);
	}

	public function format($data){
		if(!empty($data['rows'])){
			foreach($data['rows'] as &$val){
				if($val['is_on_sale'] == 1){
					$val['on_sale_text'] = '<span style="color:green;" class="fa fa-check"></span>';
				}else{
					$val['on_sale_text'] = '<span style="color:red;" class="fa fa-close"></span>';
				}
				$val['id'] = $val['goods_id'];
			}
		}
		return $data;
	}

}
