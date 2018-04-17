<?php
/**
 * ====================================
 * 接口访问类，包含所有微信支付API列表的封装，类中方法为static方法，
 * 每个接口有默认超时时间（除提交被扫支付为10s，上报超时时间为1s外，其他均为6s）
 * ====================================
 * Author: 9009123 (Lemonice)
 * Date: 2017-07-27 10:28
 * ====================================
 * File: AlipayApi.class.php
 * ====================================
 */
namespace Common\Extend\Pay\Alipay;
use Common\Extend\Curl;
use Common\Extend\Time;

class Alipay extends AlipayData{

    /**
     * 接口通讯 - 统一通讯方法
     * @param bool $checkSign  返回值是否校验sign签名
     * @return bool|mixed
     */
    public function request($checkSign = true) {
        $method = $this->getData('method');
        if(!in_array($method, $this->method)){
            $this->setError('不支持的API接口!');
            return false;
        }

        $url = $this->getGateWay();
        $params = $this->getDatas();
        $response = Curl::get($url . '?' . http_build_query($params));
        return $this->result($response, $checkSign);
    }

    /**
     * 异步回调
     * @return bool
     */
    public function notify() {
        $data = I('post.');
        $result = $this->checkSign($data);
        $return = array(
            'result'=>0,  //0=支付失败，1=支付成功
            'order_sn'=>isset($data['out_trade_no']) ? $data['out_trade_no'] : '',
            'order_amount'=>isset($data['total_amount']) ? $data['total_amount'] : 0.00,
            'create_time'=>isset($data['gmt_create']) ? strtotime($data['gmt_create']) : 0,
            'pay_time'=>isset($data['gmt_payment']) ? strtotime($data['gmt_payment']) : 0,
            'data'=>$data
        );
        if($result !== false && ($data['trade_status'] == 'TRADE_FINISHED' || $data['trade_status'] == 'TRADE_SUCCESS')){
            $return['result'] = 1;
        }
        $this->replyNotify($return['result']);  //回复支付宝
        return $return;
    }

    /**
     * 同步回调
     * @return bool
     */
    public function respond() {
        $data = I('get.');
        $result = $this->checkSign($data);
        $return = array(
            'result'=>0,  //0=支付失败，1=支付成功
            'order_sn'=>isset($data['out_trade_no']) ? $data['out_trade_no'] : '',
            'order_amount'=>isset($data['total_amount']) ? $data['total_amount'] : 0.00,
            'create_time'=>isset($data['gmt_create']) ? Time::gmstr2time($data['gmt_create']) : 0,
            'pay_time'=>isset($data['gmt_payment']) ? Time::gmstr2time($data['gmt_payment']) : 0,
            'data'=>$data
        );
        if($result !== false){
            $return['result'] = 1;
        }
        return $return;
    }

    /**
     * 面对面付款异步回调
     * @return bool
     */
    public function notifyF2f() {
        $data = I('post.');
        if(!isset($data['total_amount']) && isset($data['fund_bill_list']) && !empty($data['fund_bill_list'])){
            $fundBill_List = @json_decode($data['fund_bill_list'], true);
            if(!empty($fundBill_List) && isset($fundBill_List[0]['amount'])){
                $data['total_amount'] = $fundBill_List[0]['amount'];
            }
        }
        $result = $this->checkSign($data);
        //签名校验通过，还要校验ID
        if($result === true){
            $responseTxt = 'false';
            if (isset($data['notify_id']) && !empty($data['notify_id'])) {
                $responseTxt = $this->getResponse($data['notify_id']);
            }
            //验证
            //$responsetTxt的结果不是true，与服务器设置问题、合作身份者ID、notify_id一分钟失效有关
            //isSign的结果不是true，与安全校验码、请求时的参数格式（如：带自定义参数等）、编码格式有关
            $result = 0;
            if (preg_match("/true$/i",$responseTxt)) {
                $result = 1;
            }
        }

        $return = array(
            'result'=>0,  //0=支付失败，1=支付成功
            'order_sn'=>isset($data['out_trade_no']) ? $data['out_trade_no'] : '',
            'order_amount'=>isset($data['total_amount']) ? $data['total_amount'] : 0.00,
            'create_time'=>isset($data['gmt_create']) ? strtotime($data['gmt_create']) : 0,
            'pay_time'=>isset($data['gmt_payment']) ? strtotime($data['gmt_payment']) : 0,
            'data'=>$data
        );
        if($result !== false && ($data['trade_status'] == 'TRADE_FINISHED' || $data['trade_status'] == 'TRADE_SUCCESS')){
            $return['result'] = 1;
        }
        $this->replyNotify($return['result']);  //回复支付宝
        return $return;
    }

    /**
     * 获取远程服务器ATN结果,验证返回URL
     * @param string $notify_id 通知校验ID
     * @return string 服务器ATN结果
     * 验证结果集：
     * invalid命令参数不对 出现这个错误，请检测返回处理中partner和key是否为空
     * true 返回正确信息
     * false 请检查防火墙或者是服务器阻止端口问题以及验证时间是否超过一分钟
     */
    private function getResponse($notify_id) {
        $veryfyUrl = $this->httpsVerifyUrl . "partner=" . $this->getPartnerId() . "&notify_id=" . $notify_id;
        $cacertPemPath = $this->getKeyPemPath(2);
        return Curl::get($veryfyUrl, $cacertPemPath);
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
     * 校验请求结果
     * @param $json
     * @param bool $checkSign
     * @return bool|mixed
     */
    private function result($json, $checkSign = true) {
        $data = json_decode($json, true);

        //截取出来签名校验的数据
        $method = str_replace('.','_',$this->getData('method')).'_response';
        if(!is_array($data) || empty($data) || !isset($data[$method]) || empty($data[$method])){
            $this->setError('返回内容解析错误！');
            return false;
        }
        $sign = isset($data['sign']) ? $data['sign'] : '';
        $data = $data[$method];
        if($data['code'] != '10000'){
            $this->setError('['.$data['code'].']'.$data['sub_msg']);
            return false;
        }
        $checkParam = substr($json, strpos($json, $method)+strlen($method)+2, strpos($json, 'sign')-strlen($method)-6);
        if($checkSign === true){
            $result = $this->checkSign($checkParam, $sign);
            if($result === false){
                return $result;
            }
        }
        if(isset($data[$method])){
            $data = $data[$method];
        }
        return $data;
    }
}

