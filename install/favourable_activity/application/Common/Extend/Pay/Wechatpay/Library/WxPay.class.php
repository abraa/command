<?php
/**
 * ====================================
 * 接口访问类，包含所有微信支付API列表的封装，类中方法为static方法，
 * 每个接口有默认超时时间（除提交被扫支付为10s，上报超时时间为1s外，其他均为6s）
 * ====================================
 * Author: 9009123 (Lemonice)
 * Date: 2017-07-27 10:28
 * ====================================
 * File: WxPayApi.class.php
 * ====================================
 */
namespace Common\Extend\Pay\Wechatpay\Library;
use Common\Extend\Curl;

class WxPay extends WxPayData{
    protected $url = array(
        'unifiedorder' => 'https://api.mch.weixin.qq.com/pay/unifiedorder',      //统一下单
        'orderquery'   => 'https://api.mch.weixin.qq.com/pay/orderquery',        //查询订单
        'closeorder'   => 'https://api.mch.weixin.qq.com/pay/closeorder',        //关闭订单
        'refund'       => 'https://api.mch.weixin.qq.com/secapi/pay/refund',     //申请退款
        'refundquery'  => 'https://api.mch.weixin.qq.com/pay/refundquery',       //查询退款
        'downloadbill' => 'https://api.mch.weixin.qq.com/pay/downloadbill',      //下载对账单
        'micropay'     => 'https://api.mch.weixin.qq.com/pay/micropay',          //提交被扫码支付
        'reverse'      => 'https://api.mch.weixin.qq.com/secapi/pay/reverse',    //撤销订单API接口
        'shorturl'     => 'https://api.mch.weixin.qq.com/tools/shorturl',        //转换短链接
    );

    /**
     * 回调入口
     * @param bool $needSign  是否需要签名输出
     * @return bool
     */
    public function notify($needSign = false) {
        $msg = "OK";
        //当返回false的时候，表示notify中调用NotifyCallBack回调失败获取签名校验失败，此时直接回复失败
        $result = $this->notifyResult();
        $this->setDatas(array());  //重置XML字段
        if($result == false){
            $this->setData('return_code', "FAIL");
            $this->setData('return_msg', $msg);
            $this->replyNotify(false);
            return false;
        } else {
            //该分支在成功回调到NotifyCallBack方法，处理完成之后流程
            $this->setData('return_code', "SUCCESS");
            $this->setData('return_msg', $msg);
        }
        $this->replyNotify($needSign);
        return $result;
    }

    /**
     * 微信接口通讯 - 统一通讯方法
     * @param string $api_name
     * @param bool $checkSign
     * @return bool|mixed
     */
    public function request($api_name = '', $checkSign = true) {
        if(!isset($this->url[$api_name])){
            $this->setError('不支持的API接口!');
            return false;
        }

        $url = $this->url[$api_name];
        $this->setData('nonce_str', $this->getNonceStr());  //随机字符串
        $this->setSign();//签名
        $xml = $this->toXml();

        $response = Curl::postXml($xml, $url);

        return $this->result($response, $checkSign);
    }

    /**
     * 回复通知
     * @param bool $needSign 是否需要签名输出
     */
    private function replyNotify($needSign = false) {
        $return_code = $this->getData('return_code');
        //如果需要签名
        if($needSign == true && $return_code == "SUCCESS") {
            $this->setSign();
        }
        echo $this->toXml();
    }

    /**
     * 校验请求结果
     * @param $xml
     * @param bool $checkSign
     * @return bool|mixed
     */
    public function result($xml, $checkSign = true) {
        libxml_disable_entity_loader(true);
        $data = json_decode(json_encode(simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA)), true);
        if(!is_array($data) || empty($data)){
            $this->setError('返回内容解析错误！');
            return false;
        }
        if($checkSign === true){
            $this->setDatas($data);
            $result = $this->checkSign((isset($data['sign']) ? $data['sign'] : ''));
            if($result === false){
                return $result;
            }
        }
        return $data;
    }

    /**
     * 支付校验结果
     * @return bool|mixed
     */
    public function notifyResult() {
        //获取通知的数据
        $xml = $GLOBALS['HTTP_RAW_POST_DATA'];
        //如果返回成功则验证签名
        $result = $this->result($xml);
        return $result;
    }
}

