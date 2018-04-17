<?php
/**
 * ====================================
 * 后台配置信息模型
 * ====================================
 * Author: 9009123 (Lemonice)
 * Date: 2017-10-09 16:07
 * ====================================
 * File: ShopConfigModel.class.php
 * ====================================
 */
namespace Common\Model\Home;
use Common\Model\CommonModel;

class ShopConfigModel extends CommonModel{
    /**
     * 类型默认值
     * @var array
     */
	private $typeDefault = array('intval'=>0,'floatval'=>0,'trim'=>'');
    /**
     * 初始化配置的值
     * @var array
     */
	private $configType = array(
		'intval'=>array(
			'watermark_alpha',
			'cache_time',
			'thumb_width',
			'thumb_height',
			'image_width',
			'image_height',
			'bought_goods',
			'goods_name_length',
			'top10_time',
			'show_order_type',  // 显示方式默认为列表方式
		),
		'floatval'=>array(
			'market_price_rate',
			'integral_scale',
			//'integral_percent',
			'min_goods_amount'=>0,
		),
		'trim'=>array(
			'qq',
			'ww',
		),
		
	);
    /**
     * 初始化配置的值, 如果没有值则设置默认值
     * @var array
     */
	private $configDefault = array(
		'intval'=>array(
			'best_number'=>3,
			'new_number'=>3,
			'hot_number'=>3,
			'promote_number'=>3,
			'top_number'=>10,
			'history_number'=>5,
			'comments_number'=>5,
			'article_number'=>5,
			'page_size'=>10,
			'goods_gallery_number'=>5,
			'help_open'=>1,  // 显示方式默认为列表方式
			'default_storage'=>1,
		),
	);

    /**
     * 加载、获取配置信息
     * @param string $field 查询的配置字段（多个逗号隔开，或者数组）
     * @return array|mixed
     */
	public function config($field = ''){
		if($field == ''){
			return $this->loadConfig();
		}
		$data = C('SHOP_CONFIG');
		//强制加载所有配置
		if (!$data){
			$data = $this->loadConfig();
		}
		
		//检查是否已经加载过所有配置
		if (isset($data[$field])){
			return $data[$field];
		}
		
		$value = $this->where("code = '$field'")->getField('value');
		return $value;
	}

    /**
     * 加载、获取配置信息
     * @param string $field 查询的配置字段（多个逗号隔开，或者数组）
     * @return array
     */
	public function loadConfig($field = ''){
		$array = array();
	
		$data = C('SHOP_CONFIG');
		if (is_null($data)){
			$where = 'parent_id > 0';
			if(!is_array($field) && $field != ''){
				$fieldArray = explode(',',$field);
				$where .= " and code IN('".implode("','",$fieldArray)."')";
			}elseif(is_array($field) && !empty($field)){
				$where .= " and code IN('".implode("','",$field)."')";
			}
			
			$result = $this->field('code, value')->where($where)->select();
			if(!empty($result)){
				foreach ($result as $value){
					$array[$value['code']] = $value['value'];
				}
			}
			
			//初始化配置值
			$array = $this->setType($array);
			//初始化配置的值, 如果没有值则设置默认值
			$array = $this->setDefaultValue($array);
			
			/* 对字符串型设置处理 */
			$array['no_picture']           = !empty($arr['no_picture']) ? str_replace('../', './', $arr['no_picture']) : 'images/no_picture.gif'; // 修改默认商品图片的路径
			$array['one_step_buy']         = empty($arr['one_step_buy']) ? 0 : 1;
			$array['invoice_type']         = empty($arr['invoice_type']) ? array('type' => array(), 'rate' => array()) : unserialize($arr['invoice_type']);

			C('SHOP_CONFIG', $array);
		}else{
			$array = $data;
		}
		return $array;
	}

    /**
     * 对config配置具体的值进行初始化处理
     * @param array $config 配置内容
     * @return mixed
     */
	private function setType($config){
		if(isset($this->configType) && !empty($this->configType)){
			foreach($this->configType as $function=>$typeField){
				if(!empty($typeField)){
					foreach($typeField as $field){
						$config[$field] = isset($config[$field]) ? $function($config[$field]) : (isset($this->typeDefault[$function]) ? $this->typeDefault[$function] : '');  //初始化配置值
					}
				}
			}
		}
		return $config;
	}

    /**
     * 对config配置具体的值按设置进行默认值设置
     * @param array $config 配置内容
     * @return mixed
     */
	private function setDefaultValue($config){
		if(isset($this->configDefault) && !empty($this->configDefault)){
			foreach($this->configDefault as $function=>$typeField){
				if(!empty($typeField)){
					foreach($typeField as $field=>$default_value){
						$config[$field] = isset($config[$field]) ? $function($config[$field]) : $default_value;  //设置默认值
					}
				}
			}
		}
		return $config;
	}
}