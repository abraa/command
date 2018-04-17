<?php
/**
 * ====================================
 * 数据对象基础类，该类中定义数据类最基本的行为，包括：
 * 计算/设置/获取签名、输出xml格式的参数、从xml读取数据对象等
 * ====================================
 * Author: 9009123 (Lemonice)
 * Date: 2017-07-27 10:28
 * ====================================
 * File: WxPayData.class.php
 * ====================================
 */
namespace Common\Extend\Pay\Wechatpay\Library;

class WxPayData extends WxPayConfig{
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
     * 产生随机字符串，不长于32位
     * @param int $length
     * @return string
     */
    public function getNonceStr($length = 32) {
        return getRandom($length);
    }

    /**
     * 校验签名
     * @param string $check_sign
     * @return bool
     */
    public function checkSign($check_sign = '') {
        //fix异常
        if(empty($check_sign)){
            $this->setError('签名错误!');
            return false;
        }
        $sign = $this->makeSign();
        if($check_sign == $sign){
            return true;
        }
        $this->setError('签名错误!');
        return false;
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
     * 构造XML
     * @return bool|string
     */
    public function toXml() {
        $params = $this->getDatas();
        ksort($params);
        if(!is_array($params)
            || count($params) <= 0)
        {
            $this->setError('数组数据异常!');
            return false;
        }
        $xml = $this->createXml($params);
        return $xml;
    }

    /**
     * 把数组转换成xml
     * @param $params
     * @return string
     */
    public function createXml($params){
        $xml = "<xml>";
        foreach ($params as $key=>$val) {
            if (is_numeric($val)){
                $xml.="<".$key.">".$val."</".$key.">";
            }else{
                $xml.="<".$key."><![CDATA[".$val."]]></".$key.">";
            }
        }
        $xml.="</xml>";
        return $xml;
    }

    /**
     * 格式化参数格式化成url参数
     * @param array $params
     * @return string
     */
    public function toUrlParams($params = array()) {
        $params = empty($params) ? $this->getDatas() : $params;
        ksort($params);
        $buff = "";
        foreach ($params as $k => $v) {
            if($k != "sign" && $v != "" && !is_array($v)){
                $buff .= $k . "=" . $v . "&";
            }
        }
        $buff = trim($buff, "&");
        return $buff;
    }

    /**
     * 生成签名
     * @return 签名，本函数不覆盖sign成员变量，如要设置签名需要调用SetSign方法赋值
     */
    public function makeSign() {
        //签名步骤一：按字典序排序参数
        $string = $this->ToUrlParams();
        //签名步骤二：在string后加入KEY
        $string = $string . "&key=".$this->getKey();
        //签名步骤三：MD5加密
        $string = md5($string);
        //签名步骤四：所有字符转为大写
        $result = strtoupper($string);
        return $result;
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
     * 设置错误信息
     * @return array
     */
    protected function setError($msg = ''){
        if(!empty($msg)){
            $this->error[] = $msg;
        }
        return true;
    }
}





