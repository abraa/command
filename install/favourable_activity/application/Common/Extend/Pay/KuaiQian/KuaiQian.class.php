<?php
/**
 * ====================================
 * 接口访问类，包含所有支付API列表的封装
 * ====================================
 * Author: 9009123 (Lemonice)
 * Date: 2017-10-11 14:53
 * ====================================
 * File: KuaiQian.class.php
 * ====================================
 */
namespace Common\Extend\Pay\KuaiQian;
use Common\Extend\Time;

class KuaiQian extends KuaiQianData{
    /**
     * 异步回调
     * @param string $respondUrl 同步地址，需要返回给快钱
     * @return bool
     */
    public function notify($respondUrl = '') {
        $result = $this->respond();
        $this->replyNotify($result['result'], $respondUrl);  //回复
        return $result;
    }

    /**
     * 同步回调
     * @return bool
     */
    public function respond() {
        $data = I('request.');

        $return = array(
            'result'=>0,  //0=支付失败，1=支付成功
            'order_sn'=>isset($data['orderId']) ? $data['orderId'] : '',
            'order_amount'=>isset($data['orderAmount']) ? round($data['orderAmount'] / 100, 2) : 0.00,
            'create_time'=>isset($data['orderTime']) ? Time::gmstr2time($data['orderTime']) : 0,
            'pay_time'=>isset($data['dealTime']) ? Time::gmstr2time($data['dealTime']) : 0,
            'data'=>$data
        );

        //校验签名
        $field = array(
            'merchantAcctId', 'version', 'language',
            'signType', 'payType', 'bankId',
            'orderId', 'orderTime', 'orderAmount',
            'bindCard', 'bindMobile', 'dealId',
            'bankDealId', 'dealTime', 'payAmount',
            'fee', 'ext1', 'ext2',
            'payResult', 'errCode',
        );
        $sign = $this->checkoSign($data['signMsg'], $field);
        if($sign === false){
            $this->setError('校验签名错误');
            return false;
        }

        $return['result'] = $sign!==false ? 1 : 0;
        return $return;
    }

    /**
     * 回复通知
     * @param int $result
     * @param string $respondUrl
     */
    private function replyNotify($result = 0, $respondUrl = '') {
        echo "<result>".$result."</result><redirecturl>".$respondUrl."</redirecturl>";
    }

    /**
     * 获取签名相关的参数与值
     * @return array
     */
    public function getSignData(){
        $field = !empty($field) ? $field : array(
            'inputCharset', 'pageUrl', 'bgUrl',
            'version', 'language', 'signType',
            'merchantAcctId', 'payerName', 'payerContactType',
            'payerContact', 'orderId', 'orderAmount',
            'orderTime', 'productName', 'productNum',
            'productId', 'productDesc', 'ext1',
            'ext2', 'productNum', 'payType',
            'bankId', 'redoFlag', 'pid',
        );
        /* 生成加密签名串 请务必按照如下顺序和规则组成加密串！*/
        $data = array();
        foreach($field as $f){
            $value = $this->getData($f);
            if(!is_null($value) && $value != ''){
                $data[$f] = $value;
            }
        }
        return $data;
    }

    /**
     * 校验签名
     * @param string $sign
     * @param array $field
     * @return bool|string
     */
    public function checkoSign($sign = '', $field = array()){
        if(empty($sign)){
            return false;
        }
        $data = $this->getSignData($field);
        $signmsgval = $this->buildString($data);
        $trans_body = rtrim($signmsgval,"&");
        $MAC        = base64_decode($sign);
        $cert       = file_get_contents(APP_PATH . $this->publicKey);
        $pubkeyid   = openssl_get_publickey($cert);
        $result = openssl_verify($trans_body,$MAC,$pubkeyid);
        return $result ? $sign : false;
    }

    /**
     * 获取签名
     * @param array $data
     * @param array $field
     * @return string
     */
    public function makeSign($data = array(), $field = array()){
        if(empty($data)){
            $data = $this->getSignData($field);
        }
        $signmsgval = $this->buildString($data);
        $signmsgval = rtrim($signmsgval,"&");
        $privKey   = file_get_contents(APP_PATH . $this->privateKeyPem);
        $pkeyid     = openssl_get_privatekey($privKey);

        // compute signature
        openssl_sign($signmsgval,$signMsg,$pkeyid);
        // free the key from memory
        openssl_free_key($pkeyid);

        $sign = base64_encode($signMsg);
        return !empty($sign) ? $sign : '';
    }

    /**
     * 将变量值不为空的参数组成字符串
     * @param array $data
     * @return string
     */
    public function buildString($data = array()){
        $string = '';
        if(!empty($data)){
            foreach($data as $key=>$value){
                if($key != '' && $value != ''){
                    $string .= '&'.$key.'='.$value;
                }
            }
            $string = !empty($string) ? substr($string,1) : $string;
        }
        return $string;
    }

    /**
     * 根据银行编号，获取支付编号
     * @param string $bankId
     * @return string
     */
    public function getPayType($bankId = ''){
//       $kq_payType		= "00";	//*   支付方式 固定值: 00, 10, 11, 12, 13, 14, 15, 16, 17  (2)
//        00: 其他支付
//        10: 银行卡支付
//        11: 电话支付
//        12: 快钱账户支付
//        13: 线下支付
//        14: 企业网银在线支付
//        15: 信用卡在线支付
//        17: 预付卡支付
//        *B2B 支付需要单独申请，默认不开通
        if (!empty($bankId)) {
            if ($bankId == 'PSBC') {
                $payType = "21-2";
            } else {
                $payType = "21-1";
            }
        } else {
            $payType = "00";  //如果是00值，则bank_id必须重置成空
        }
        return $payType;
    }
}

