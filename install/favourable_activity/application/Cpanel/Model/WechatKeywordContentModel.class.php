<?php
/**
 * ====================================
 * 微信关键字内容模型
 * ====================================
 * Author: lirunqing
 * Date: 2017-07-05
 * ====================================
 * Project: new.m.chinaskin.cn
 * File: WechatKeywordContentModel.class.php
 * ====================================
 */
namespace Cpanel\Model;
use Common\Model\CpanelModel;

class WechatKeywordContentModel extends CpanelModel{

	public $typeArr = array(
	    		USER_ACT_REPLY => '文本回复',
	    		USER_ACT_MENU => '菜单点击',
	    		//USER_ACT_SUBSCRIBE => '关注',
	    		//USER_ACT_UNSUBSCRIBE =>'取消关注',
	    	);

	public function filter($parmas = array()){

		$params['sort'] = !empty($params['sort']) ? $params['sort'] : 'add_time';
		$params['order'] = !empty($params['order']) ? $params['order'] : 'desc';

        $where = array();
        if(isset($parmas['account_id'])){
            $where['wechat_account_id'] = $parmas['account_id'];
        }
       
		return  $this->field($field)->where($where)->order($params['sort'].' '.$params['order']);
    }

    public function format($data){

		if(!empty($data['rows'])){

			$wechatKeywordModel = D('WechatKeyword');
			$contentIdArr = array();

			foreach($data['rows'] as $key => $val){
				$contentIdArr[$val['id']] = $val['id'];
				$val['type_str'] = $this->typeArr[$val['type']];

				$data['rows'][$key] = $val;
			}

			$where['content_id'] = array('in' , $contentIdArr);
			$keywordArr = $wechatKeywordModel->where($where)->select();
			$keywordArrTemp = array();
			foreach ($keywordArr as $key => $value) {
				$keywordArrTemp[$value['content_id']][] = $value['keyword']; 
			}

			$keywordStr = '';
			foreach($data['rows'] as $key => $val){

				$keyword = $keywordArrTemp[$val['id']];

				if (!empty($keyword)) {
					$keywordStr = implode('，', $keyword);
					$val['keyword_str'] = $keywordStr;
				}

				$data['rows'][$key] = $val;
			}
		}
		return $data;
	}
}