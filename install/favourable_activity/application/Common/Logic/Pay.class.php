<?php
/**
 * ====================================
 * 支付 - 第三方平台支付接口入口 - 业务层
 * ====================================
 * Author: 9009123 (Lemonice)
 * Date: 2017-10-09 17:59
 * ====================================
 * File: Pay.class.php
 * ====================================
 */
namespace Common\Logic;

class Pay extends LogicData{
    /**
     * 目前支持的支付方式 - 库名称
     * @var null
     */
    private $payCodeList = array(
        'WECHATPAY' => 'Wechatpay',  //微信支付
        'ALIPAY'    => 'Alipay',     //支付宝支付
        'KUAIQIAN'  => 'KuaiQian',   //快钱支付
        'TENPAY'    => 'Tenpay',     //财付通支付
    );

    /**
     * 调用支付库
     */
    public function handle(){
        $Object = $this->loadLibary();
        if(is_null($Object)){
            return false;
        }
        $paytype = $this->getData('paytype');
        $params = $this->getDatas();
        $Object->setDatas($params);
        if(!method_exists($Object, $paytype)){
            $this->setError('此支付方式不支持！');
            return false;
        }

        $result = $Object->$paytype();;
        if($result !== false){  //成功
            return $result;
        }else{  //失败
            $error = $Object->getError();
            $this->setError($error);
            return false;
        }
    }

    /**
     * 加载对应支付方式的库对象
     * @return Object
     */
    private function loadLibary(){
        $libary_name = $this->getLibaryName();
        if(is_null($libary_name)){
            return $libary_name;
        }
        $libary_name = "\\Common\\Extend\\Pay\\$libary_name";
        $Object = new $libary_name();
        $paytype = $this->getData('pay_type');
        $paycode = $this->getData('paycode');
        $paytype = !empty($paytype) ? $paytype : $this->getData('paytype');
        $Object->setNotifyUrl($paycode, $paytype);
        $Object->setNotifyF2fUrl($paycode, $paytype);
        $Object->setRespondUrl($paycode, $paytype);
        return $Object;
    }

    /**
     * 获取支付方式对应的库名称
     * @return array|bool|string
     */
    private function getLibaryName(){
        $paycode = $this->getData('paycode');
        if(is_null($paycode)){
            $this->setError('请选择支付方式！');
            return NULL;
        }
        $paycode = strtoupper($paycode);
        if(!isset($this->payCodeList[$paycode])){
            $this->setError('选择的支付方式不存在！');
            return NULL;
        }
        return $this->payCodeList[$paycode];
    }
}