<?php
/**
 * ====================================
 * 数据对象基础类，该类中定义数据类最基本的行为，包括：
 * 计算/设置/获取签名、输出xml格式的参数、从xml读取数据对象等
 * ====================================
 * Author: 9009123 (Lemonice)
 * Date: 2017-07-27 10:28
 * ====================================
 * File: AlipayData.class.php
 * ====================================
 */
namespace Common\Extend\Pay\Alipay;

class AlipayData extends AlipayConfig{
    /**
     * 支付宝统一接口网关
     * @var string
     */
    protected $gateway = 'https://openapi.alipay.com/gateway.do';  //支付宝API网关
    /**
     * HTTPS形式消息验证地址
     * @var string
     */
    protected $httpsVerifyUrl = 'https://mapi.alipay.com/gateway.do?service=notify_verify&';
    /**
     * HTTP形式消息验证地址
     * @var string
     */
    protected $httpVerifyUrl = 'http://notify.alipay.com/trade/notify_query.do?';
    /**
     * 支付宝接口名称
     * @var array
     */
    protected $method = array(  //接口名称
        'trade_wap_pay' => 'alipay.trade.wap.pay',      //手机网站支付
        'alipay_trade_precreate'=>'alipay.trade.precreate',  //统一收单线下交易预创建，面对面支付
        'alipay_trade_refund' => 'alipay.trade.refund',      //统一收单交易退款接口
        'alipay_trade_fastpay_refund_query'=>'alipay.trade.fastpay.refund.query',  //统一收单交易退款查询
    );
    /**
     * 错误信息
     * @var array
     */
    private $error = array();
    /**
     * 参数储存
     * @var array
     */
    private $datas = array();

    /**
     * 校验签名
     * @param $params
     * @param string $check_sign
     * @return bool|string
     */
    public function checkSign($params, $check_sign = '') {
        if(empty($check_sign)){
            $check_sign = $params['sign'];
            if(isset($params['sign_type'])) unset($params['sign_type']);
            if(isset($params['sign'])) unset($params['sign']);
            if(isset($params['paycode'])) unset($params['paycode']);
            if(isset($params['paytype'])) unset($params['paytype']);
            $data = $this->toUrlParams($params);
        }else{
            $data = $params;
        }

        //fix异常
        if(empty($check_sign)){
            $this->setError('签名错误!');
            return false;
        }
        //读取公钥文件
        $key_pem_path = $this->getKeyPemPath(1);
        $pubKey = file_get_contents($key_pem_path['public_path']);
        //转换为openssl格式密钥
        $res = openssl_get_publickey($pubKey);

        if(!$res){
            $this->setError('支付宝RSA公钥错误。请检查公钥文件格式是否正确');
            return false;
        }
        //调用openssl内置方法验签，返回bool值
        $result = (bool)openssl_verify($data, base64_decode($check_sign), $res);

        //释放资源
        openssl_free_key($res);

        return $result ? $check_sign : false;
    }

    /**
     * 设置签名，详见签名生成算法
     * @return string
     */
    public function setSign() {
        $sign = $this->makeSign();
        $this->setData('sign', $sign);
        return $sign;
    }

    /**
     * 获取签名，详见签名生成算法的值
     * @return string
     **/
    public function getSign() {
        return $this->getData('sign');
    }

    /**
     * 将参数转换成JSON数据
     * @return string
     */
    public function toJson(){
        $data = $this->getDatas();
        return json_encode($data);
    }

    /**
     * 格式化参数格式化成url参数
     * @param array $params
     * @return string
     */
    public function toUrlParams($params = array()) {
        $params = empty($params) ? $this->getDatas() : $params;
        ksort($params);
        $stringToBeSigned = "";
        $i = 0;
        foreach ($params as $k => $v) {
            if (false === $this->checkEmpty($v) && "@" != substr($v, 0, 1)) {
                // 转换成目标字符集
                $v = $this->characet($v, $this->getData('charset'));

                if ($i == 0) {
                    $stringToBeSigned .= "$k" . "=" . "$v";
                } else {
                    $stringToBeSigned .= "&" . "$k" . "=" . "$v";
                }
                $i++;
            }
        }

        unset ($k, $v);
        return $stringToBeSigned;
    }

    /**
     * 生成签名
     * @return string 签名，本函数不覆盖sign成员变量，如要设置签名需要调用SetSign方法赋值
     */
    public function makeSign() {
        $data = $this->toUrlParams();
        $key_pem_path = $this->getKeyPemPath(1);
        $priKey = file_get_contents($key_pem_path['private_path']);
        $res = openssl_get_privatekey($priKey);
        if(!$res){
            $this->setError('您使用的私钥格式错误，请检查RSA私钥配置');
            return false;
        }
        openssl_sign($data, $sign, $res);  //只支持RSA，不支持RSA2
        openssl_free_key($res);

        $sign = base64_encode($sign);
        return $sign;
    }

    /**
     * 组装业务请求参数的集合
     * @param array $privateParamsField
     * @param string $paramName
     * @return bool|string
     */
    public function setBizContent($privateParamsField = array(), $paramName = 'biz_content'){
        $params = $this->getDatas();
        $data = array();
        if(!empty($privateParamsField)){
            foreach($privateParamsField as $field){
                if(isset($params[$field])){
                    $data[$field] = $params[$field];
                }
            }
            $biz_content = json_encode($data);
            $this->setData($paramName, $biz_content);
            return $biz_content;
        }
        return false;
    }

    /**
     * 设置支付接口名称
     * @param string $method
     */
    public function setMethod($method = ''){
        $method = !empty($method) ? $method : $this->getData('method');
        if(!empty($method) && isset($this->method[$method])){
            $this->setData('method', $this->method[$method]);
        }
    }

    /**
     * 设置参数
     * @param string $name  字段名称
     * @param bool|string|array $value  字段值
     * @return bool
     */
    public function setData($name = '', $value = ''){
        if(empty($name)){
            return false;
        }
        $this->datas[$name] = $value;
        return true;
    }

    /**
     * 获取设置的参数
     * @param string $name  字段名称
     * @return bool|string|array| ... anything
     */
    public function getData($name = ''){
        if(isset($this->datas[$name])){
            return $this->datas[$name];
        }
        return NULL;
    }

    /**
     * 获取设置的所有参数
     * @return array
     */
    public function getDatas(){
        $params = $this->datas;
        $params['app_id'] = $this->getAppId();
        if(isset($params['paycode'])){
            unset($params['paycode']);
        }
        return $params;
    }

    /**
     * 重置所有参数
     * @param array $params
     * @return array
     */
    public function setDatas($params = array()){
        $this->datas = $params;
        return $this->datas;
    }

    /**
     * 获取错误信息
     * @return array
     */
    public function getError(){
        if(!empty($this->error)){
            return implode(' & ', $this->error);
        }
        return '';
    }

    /**
     * 获取网关地址
     * @return string
     */
    public function getGateWay(){
        return $this->gateway;
    }

    /**
     * 设置错误信息
     * @return array
     */
    protected function setError($msg = ''){
        if(!empty($msg)){
            $this->error[] = $msg;
        }
        return true;
    }

    /**
     * 校验$value是否非空
     *  if not set ,return true;
     *    if is null , return true;
     **/
    protected function checkEmpty($value) {
        if (!isset($value))
            return true;
        if ($value === null)
            return true;
        if (trim($value) === "")
            return true;

        return false;
    }

    /**
     * 转换字符集编码
     * @param $data
     * @param $targetCharset
     * @return string
     */
    protected function characet($data, $targetCharset) {
        if (!empty($data)) {
            $fileType = 'utf-8';
            if (strcasecmp($fileType, $targetCharset) != 0) {
                $data = mb_convert_encoding($data, $targetCharset, $fileType);
            }
        }
        return $data;
    }
}





