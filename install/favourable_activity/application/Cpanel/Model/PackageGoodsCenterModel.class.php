<?php
/**
 * ====================================
 * 会员中心套装商品模型
 * ====================================
 * Author: 9006758
 * Date: 2017-04-19
 * ====================================
 * Project: new.m.chinaskin.cn
 * File: PackageGoodsCenterModel.class.php
 * ====================================
 */
namespace Cpanel\Model;
use Common\Model\CpanelUserCenterModel;
use Common\Extend\Time;

class PackageGoodsCenterModel extends CpanelUserCenterModel
{
    protected $tableName = 'goods_activity';
    protected $_validate = array(
        array('act_name', 'require', '套装名称不能为空'),
    );
    protected $img_base_path = '/upload/ucenter/goods/';//新图片基础路径
    protected $img_old_path = 'http://useradmin.chinaskin.cn/public/upload/goods/';//旧图片基础路径

	public function filter(&$params){
		$params['sort'] = !empty($params['sort']) ? $params['sort'] : 'act_id';
		$params['order'] = !empty($params['order']) ? $params['order'] : 'desc';
        $this->field('act_id,act_name,goods_name,start_time,end_time,unique_id');
        return $this;
	}

	public function format($data){
		if(!empty($data['rows'])){
			foreach($data['rows'] as &$val){
                $val['start_time'] = Time::localDate('Y-m-d H:i:s', strtotime($val['start_time']));
                $val['end_time'] = Time::localDate('Y-m-d H:i:s', strtotime($val['end_time']));
                $val['id'] = $val['act_id'];
			}
		}
		return $data;
	}

    /**
     * 获取套装信息
     * @param $act_id
     * @return mixed
     */
    public function getPackageInfo($act_id){
        $where['act_id'] = $act_id;
        $info = $this->where($where)->find();
        $info['act_desc'] = unserialize($info['act_desc']);
        $info['ext_info'] = unserialize($info['ext_info']);
        $info['package_price'] = !empty($info['ext_info']['package_price']) ? $info['ext_info']['package_price'] : '0.00';
        $info['start_time'] = Time::localDate('Y-m-d H:i:s', $info['start_time']);
        $info['end_time'] = Time::localDate('Y-m-d H:i:s', $info['end_time']);
		if(!empty($info['act_desc'])){
			$desc_img = array();
			foreach($info['act_desc'] as $key=>$val){
                if(file_exists(APP_ROOT.$this->img_base_path.$val)){
                    $desc_img[$key]['original'] = $this->img_base_path.$val;
                    $desc_img[$key]['thumb'] = $this->img_base_path.'thumb/'.$val;
                }else{
//                    $desc_img[$key]['original'] = $this->img_old_path.$val;
//                    $desc_img[$key]['thumb'] = $this->img_old_path.'thumb/'.$val;

                    $desc_img[$key]['original'] = C('SOURCE_UCENTER_PATH').'goods/'.$val;
                    $desc_img[$key]['thumb'] = C('SOURCE_UCENTER_PATH').'goods/'.'thumb/'.$val;
                }
			}
			$info['act_desc'] = $desc_img;
		}
        return $info;
    }

	// 套装更新与添加
    public function dataSave($params){
		$act_id = intval($params['act_id']);
		
		$data['act_name'] = trim($params['act_name']);
		$data['unique_id'] = trim($params['unique_id']);
		$data['goods_name'] = trim($params['goods_name']);
		$package_price = floatval(trim($params['package_price']));
		$data['ext_info'] = serialize(array('package_price'=>$package_price));
		$data['start_time'] = Time::localStrtotime(trim($params['start_time']));
		$data['end_time'] = Time::localStrtotime(trim($params['end_time']));
		
		if($act_id){
			/* 更新 */
			$this->where(array('act_id'=>$act_id))->data($data)->save();
		}else{
			/* 添加 */
			$data['act_type'] = 4;
			
			//录入商品表
			$goods_data['goods_type'] = 2;
			$goods_data['goods_name'] = $data['goods_name'];
			$goods_data['market_price'] = $package_price;
			$goods_data['shop_price'] = $package_price;
			$goods_data['add_time'] = Time::gmTime();
			$goods_id = D('GoodsCenter')->data($goods_data)->add();
			$data['goods_id'] = $goods_id;
			
			//录入套装
			$act_id = $this->data($data)->add();
		}
		
		// 录入商品套装关联表
		$goods_id_str = trim($params['goods_id']); 
		if($goods_id_str && $act_id){
			$this->dealGoodsId($goods_id_str, $act_id);
		}
		
		return $act_id;
    }
	
	/**
	 * 录入商品套装关联表
	 * @params string $goods_id_str //商品id字符串组合，[goods_id] => 655|2,656|1,657|2
	 * @params int    $act_id   套装id
	 */
	private function dealGoodsId($goods_id_str, $act_id){
		$relationModel = M('package_goods', null, 'USER_CENTER');
		
		//删除旧记录
		$relationModel->where(array('package_id'=>$act_id))->delete();
		
		$package_goods = explode(',', $goods_id_str);
		$admin_id = login('user_id');
		foreach($package_goods as $val){
			list($goods_id, $goods_num) = explode('|', $val);
			$data[] = array(
				'goods_id' => $goods_id,
				'goods_number' => intval($goods_num)<=0 ? 1 : $goods_num,
				'package_id' => $act_id,
				'admin_id' => $admin_id,
			);
		}
		if(!empty($data)){
			$relationModel->addAll($data);
		}
	}

}
