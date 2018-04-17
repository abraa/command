<?php
/**
 * ====================================
 * 会员中心文章模型
 * ====================================
 * Author: lirunqing
 * Date: 2017-07-03
 * ====================================
 * Project: new.m.chinaskin.cn
 * File: ArticleManagementModel.class.php
 * ====================================
 */
namespace Cpanel\Model;
use Common\Model\CpanelUserCenterModel;
use Common\Extend\Base\Common;

class ArticleInfoListModel extends CpanelUserCenterModel{

	protected $tableName = 'articleinfo';

	public function filter(&$params){
		$params['sort'] = !empty($params['sort']) ? $params['sort'] : 'article_id';
		$params['order'] = !empty($params['order']) ? $params['order'] : 'desc';

		if(!empty($params['keyword'])){
			$keyword = trim($params['keyword']);
			$where['a.title_name|a.article_id|c.art_name']  = array('like', "%$keyword%");
		}

		$field = "a.*,c.art_name";
		return $this->alias('a')->field($field)->where($where)->join("left join __ARTICLE__ as c on a.art_id=c.art_id");
	}

	public function format($data){
		
		if(!empty($data['rows'])){
			foreach($data['rows'] as &$val){
				$val['id'] = $val['article_id'];
				$val['is_top_str'] = ($val['is_top'] == 1) ? '置顶' : '普通';
				$val['is_view_str'] = ($val['is_view'] == 1) ? '显示' : '不显示';
			}
		}
		return $data;
	}
}