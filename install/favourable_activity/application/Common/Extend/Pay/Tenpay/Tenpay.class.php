<?php
/**
 * ====================================
 * 接口访问类，包含所有支付API列表的封装
 * ====================================
 * Author: 9009123 (Lemonice)
 * Date: 2017-10-14 10:10
 * ====================================
 * File: Tenpay.class.php
 * ====================================
 */
namespace Common\Extend\Pay\Tenpay;

use Common\Extend\Time;

class Tenpay extends TenpayData{
    /**
     * 过滤不加入签名的参数名称 - 目的是为了校验签名
     * @var array
     */
    protected $filterParams = array('sign', 'paycode', 'paytype');

    /**
     * 异步回调
     * @return bool
     */
    public function notify() {
        $result = $this->respond();
        $this->replyNotify($result['result']);  //回复给财付通
        return $result;
    }

    /**
     * 同步回调
     * @return bool
     */
    public function respond() {
        $data = $this->getParams();
        $return = array(
            'result'=>0,  //0=支付失败，1=支付成功
            'order_sn'=>isset($data['out_trade_no']) ? $data['out_trade_no'] : '',
            'order_amount'=>isset($data['total_fee']) ? round($data['total_fee'] / 100, 2) : 0.00,
            'create_time'=>isset($data['time_start']) ? Time::gmstr2time($data['time_start']) : 0,
            'pay_time'=>isset($data['time_end']) ? Time::gmstr2time($data['time_end']) : 0,
            'data'=>$data
        );

        $sign = $this->checkoSign($data['sign']);
        if($sign === false){
            $this->setError('校验签名错误');
            return false;
        }
        if(($data["trade_mode"] == '1' || $data["trade_mode"] == '2') && $data["trade_state"] == '0'){
            $return['result'] = 1;  //支付成功
        }
        return $return;
    }

    /**
 * 回复通知
 */
    private function replyNotify($result = 0) {
        if($result == 1){
            echo "success";
        }else{
            echo "fail";
        }
    }

    /**
     * 获取签名相关的参数与值
     * @param array $params
     * @return array
     */
    public function getSignData($params = array()){
        $params = empty($params) ? $this->getDatas() : $params;
        ksort($params);
        return $params;
    }

    /**
     * 将变量值不为空的参数组成字符串
     * @param array $data
     * @return string
     */
    public function buildString($data = array()){
        $string = array();
        if(!empty($data)){
            foreach($data as $key=>$value){
                if($key != '' && $value != '' && !in_array($key, $this->filterParams)){
                    $string[] = $key.'='.$value;
                }
            }
            $string = !empty($string) ? implode('&',$string) : '';
        }
        return $string;
    }

    /**
     * 校验签名
     * @param string $checkSign
     * @return bool|string
     */
    public function checkoSign($checkSign = ''){
        $data = $this->getSignData($this->getParams());
        $data['key'] = $this->getSecret();
        $signmsgval = $this->buildString($data);

        $sign = strtolower(md5($signmsgval));
        return $sign == strtolower($checkSign) ? $sign : false;
    }

    /**
     * 获取签名
     * @param array $data
     * @return string
     */
    public function makeSign($data = array()){
        if(empty($data)){
            $data = $this->getSignData();
        }
        $data['key'] = $this->getSecret();
        $signmsgval = $this->buildString($data);
        $sign = strtolower(md5($signmsgval));
        return $sign;
    }

    /**
     * 获取GET与POST参数
     * @return array
     */
    private function getParams(){
        $getData = I('get.');
        $postData = I('post.');
        return array_merge($getData, $postData);
    }
}

