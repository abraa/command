<?php
/**
 * ====================================
 * 配置账号信息
 * ====================================
 * Author: 9009123 (Lemonice)
 * Date: 2017-07-27 10:28
 * ====================================
 * File: AlipayConfig.class.php
 * ====================================
 */
namespace Common\Extend\Pay\Alipay;

class AlipayConfig
{
    protected $appId;            //应用ID
    protected $account;          //帐号
    protected $key;              //密钥
    protected $partnerId;        //合作ID

    /**
     * 密钥文件路径 - 分两种
     * @var string
     */
    protected $publicKeyPem      = 'Common/Extend/Pay/Alipay/cert/alipay_public_key.pem';
    protected $privateKeyPem     = 'Common/Extend/Pay/Alipay/cert/rsa_private_key.pem';
    protected $publicKeyF2fPem   = 'Common/Extend/Pay/Alipay/cert/rsa_public_key_f2f.pem';
    protected $privateKeyF2fPem  = 'Common/Extend/Pay/Alipay/cert/rsa_private_key_f2f.pem';
    /**
     * CURL证书
     * @var string
     */
    protected $cacertPem = 'Common/Extend/Pay/Alipay/cert/cacert.pem';

    /**
     * 设置应用ID
     * @param string $appid
     */
    public function setAppId($appid = ''){
        $this->appId = $appid;
    }

    /**
     * 获取应用ID
     * @return mixed
     */
    public function getAppId(){
        return $this->appId;
    }

    /**
     * 设置帐号
     * @param string $account
     */
    public function setAccount($account = ''){
        $this->account = $account;
    }

    /**
     * 获取帐号
     * @return mixed
     */
    public function getAccount(){
        return $this->account;
    }

    /**
     * 设置密钥
     * @param string $key
     */
    public function setKey($key = ''){
        $this->key = $key;
    }

    /**
     * 获取密钥
     * @return mixed
     */
    public function getKey(){
        return $this->key;
    }

    /**
     * 设置合作ID
     * @param string $partnerId
     */
    public function setPartnerId($partnerId = ''){
        $this->partnerId = $partnerId;
    }

    /**
     * 获取合作ID
     * @return mixed
     */
    public function getPartnerId(){
        return $this->partnerId;
    }

    /**
     * 获取密钥文件的路径
     * @param int $mode  mode: 0=普通密钥文件，1=面对面付款的密钥文件, 2=CURL证书路径，单个路径（字符串）
     * @return array|String
     */
    public function getKeyPemPath($mode = 0){
        if($mode == 2){
            return APP_PATH . $this->cacertPem;
        }
        $public_path = $this->publicKeyPem;
        $private_path = $this->privateKeyPem;
        if($mode == 1){
            $public_path = $this->publicKeyF2fPem;
            $private_path = $this->privateKeyF2fPem;
        }
        return array(
            'public_path'=>APP_PATH . $public_path,
            'private_path'=>APP_PATH . $private_path
        );
    }
}
