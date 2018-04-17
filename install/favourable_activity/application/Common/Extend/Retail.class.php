<?php
/**
* ====================================
* 查询快递接口
* ====================================
* Author: 9009123 (Lemonice)
* Date: 2016-09-21 14:28
* ====================================
* File: Express.class.php
* ====================================
*/
namespace Common\Extend;
use Common\Extend\Curl;

class Retail{
    /**
     * 开启售货柜的格子 - 服务器地址
     * @var string
     */
	private $retail_send = 'http://14.23.109.186:8058/newretail/api/retail/send';
    /**
     * 开启售货柜的格子 - 头信息
     * @var string
     */
    private $retail_send_header = array(
        'Connection: Keep-Alive',
        'Content-Type: application/json',
    );
    /**
     * 开启售货柜的格子 - 数据包
     * @var string
     */
    private $retail_send_data = array(
        'user'=>'retail',
        'pass'=>'4d4738784d3277335a6a467962444d77',
    );

    public function __construct(){

    }

    /**
     * 开启售货柜的格子
     * @param string $grid
     * @return array
     */
	public function openRetail($grid = ''){
        if(empty($grid)){
            return array();
        }
        Curl::$headers = $this->retail_send_header;
        $data = $this->retail_send_data;
        $data['seqNo'] = $grid;
        $result = Curl::post($this->retail_send, $data, 'json');
        return $result ? json_decode($result,true) : array();
	}
}
