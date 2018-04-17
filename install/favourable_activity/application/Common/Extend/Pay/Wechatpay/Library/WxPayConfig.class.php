<?php
/**
 * ====================================
 * 配置账号信息
 * ====================================
 * Author: 9009123 (Lemonice)
 * Date: 2017-07-27 10:28
 * ====================================
 * File: WxPayConfig.class.php
 * ====================================
 */
namespace Common\Extend\Pay\Wechatpay\Library;

class WxPayConfig
{
    protected $appid;          //应用ID
    protected $app_secret;     //应用密钥
    protected $machine_id;     //商户号
    protected $key;            //支付密钥

    /**
     * 设置应用ID
     * @param string $appid
     */
    public function setAppId($appid = ''){
        $this->appid = $appid;
    }

    /**
     * 获取应用ID
     * @return mixed
     */
    public function getAppId(){
        return $this->appid;
    }

    /**
     * 设置应用密钥
     * @param string $app_secret
     */
    public function setAppSecret($app_secret = ''){
        $this->app_secret = $app_secret;
    }

    /**
     * 获取应用密钥
     * @return mixed
     */
    public function getAppSecret(){
        return $this->app_secret;
    }

    /**
     * 设置商户ID
     * @param string $machine_id
     */
    public function setMachineId($machine_id = ''){
        $this->machine_id = $machine_id;
    }

    /**
     * 获取商户ID
     * @return mixed
     */
    public function getMachineId(){
        return $this->machine_id;
    }

    /**
     * 设置支付密钥
     * @param string $key
     */
    public function setKey($key = ''){
        $this->key = $key;
    }

    /**
     * 获取支付密钥
     * @return mixed
     */
    public function getKey(){
        return $this->key;
    }
}
