<?php
/**
 * ====================================
 * 数据对象基础类，该类中定义数据类最基本的行为
 * ====================================
 * Author: 9009123 (Lemonice)
 * Date: 2017-10-14 10:51
 * ====================================
 * File: TenpayData.class.php
 * ====================================
 */
namespace Common\Extend\Pay\Tenpay;

class TenpayData{
    protected $gateway = 'https://gw.tenpay.com/gateway/pay.htm';  //API网关
    /**
     * 错误信息
     * @var array
     */
    private $error = array();
    /**
     * 参数储存
     * @var array
     */
    protected $datas = array();
    /**
     * 快钱帐号
     * @var
     */
    protected $account;
    /**
     * 快钱密钥
     * @var
     */
    protected $secret;

    /**
     * 密钥文件路径
     * @var string
     */
    protected $privateKeyPem     = 'Common/Extend/Pay/KuaiQian/99bill-rsa.pem';
    /**
     * 证书
     * @var string
     */
    protected $publicKey = 'Common/Extend/Pay/KuaiQian/99bill.cert.rsa.20340630.cer';

    /**
     * 设置帐号
     * @param string $account
     */
    public function setAccount($account = ''){
        $this->account = $account;
    }

    /**
     * 获取帐号
     */
    public function getAccount(){
        return $this->account;
    }

    /**
     * 设置密钥
     * @param string $secret
     */
    public function setSecret($secret = ''){
        $this->secret = $secret;
    }

    /**
     * 获取密钥
     */
    public function getSecret(){
        return $this->secret;
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
        $params['merchantAcctId'] = $this->getAccount();
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
}





