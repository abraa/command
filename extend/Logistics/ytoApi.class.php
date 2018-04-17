<?php
/**
 * ====================================
 * 圆通物流Api 基于开放者开发平台.
 * ====================================
 * Author: 9009123
 * Date: 2018-01-06 09:51
 * ====================================
 * File: ytoApi.class.php
 * ====================================
 */
namespace Common\Extend\Logistics;

class ytoApi {
    private $config = array(
        //服务类接口：走件流程查询接口
        'yto_Marketing_WaybillTrace'=>array(
            'url'          => 'http://MarketingInterface.yto.net.cn',
            'method'       => 'yto.Marketing.WaybillTrace',
            'user_id'      => 'CJ2018',
            'app_key'      => 'BHRu4q',
            'secret_key'   => '1qra4G',
        ),
    );
     
    public function __construct() {
        date_default_timezone_set('Asia/Shanghai');
    }

    public function ytoRequest($method, $user_param) {
        $config = isset($this->config[$method]) ? $this->config[$method] : array();
        if(empty($config)){
            return false;
        }
        $data = array(
            'app_key'=>$config['app_key'],
            'method'=>$config['method'],
            'timestamp'=>date('Y-m-d H:i:s'),
            'user_id'=>$config['user_id'],
            'v'=>'1.01',
            'format'=>'XML',
        );
        $data['sign'] = $this->getSign($data, $config['secret_key']);  //签名
        $data['param'] = $this->getXml($user_param['invoice_no']);
        $response = $this->post($config['url'], $data, array());

        return !empty($response) ? $response : false;
    }

    /**
     * 获取查询的XML
     * @param $invoice_no
     * @return string
     */
    private function getXml($invoice_no){
        $xml = '<?xml  version="1.0"?>';
        $xml .= '<ufinterface>';
        $xml .= '<Result>';
        $xml .= '<WaybillCode>';
        $xml .= '<Number>'.$invoice_no.'</Number>';
        $xml .= '</WaybillCode>';
        $xml .= '</Result>';
        $xml .= '</ufinterface>';
        return $xml;
    }
     
    /**
     * 签名
     * @return string
     */
    private function getSign($params, $secret_key) {
        //所有请求参数按照字母先后顺序排序
        ksort($params);
        //定义字符串开始 结尾所包括的字符串
        $stringToBeSigned = $secret_key;
        //把所有参数名和参数值串在一起
        foreach ($params as $k => $v) {
            $stringToBeSigned .= "$k$v";
        }
        unset($k, $v);
        //使用MD5进行加密，再转化成大写
        return strtoupper(md5($stringToBeSigned));
    }
     
    private function post($url, array $post = array(), array $options = array()) {
        $defaults = array(
            CURLOPT_POST => 1,
            CURLOPT_HEADER => 0,
            CURLOPT_URL => $url,
            CURLOPT_FRESH_CONNECT => 1,
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_FORBID_REUSE => 1,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_POSTFIELDS => http_build_query($post)
        );
        //print_r(http_build_query($post));
        $ch = curl_init();
        curl_setopt_array($ch, ($options + $defaults));
        $result = array();
        if( !$result = curl_exec($ch)) {
            throw new Exception(curl_error($ch), 101);
        }
        curl_close($ch);
        return $result;
    }   
}