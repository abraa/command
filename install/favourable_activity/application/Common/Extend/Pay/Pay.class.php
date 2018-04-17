<?php
/**
 * ====================================
 * 支付模块的公共类
 * ====================================
 * Author: 9009123 (Lemonice)
 * Date: 2017-10-09 17:47
 * ====================================
 * File: Pay.class.php
 * ====================================
 */
namespace Common\Extend\Pay;

class Pay{
    /**
     * 第三方内核库实例化
     * @var null
     */
    protected $pay = NULL;
    /**
     * 参数
     * @var array
     */
    private $params = array();
    /**
     * 错误信息
     * @var array
     */
    private $error = array();
    /**
     * 异步回调地址
     * @var string
     */
    private $notify_url;
    /**
     * 面对面支付异步回调地址
     * @var string
     */
    private $notify_f2f_url;
    /**
     * 同步返回地址
     * @var
     */
    private $respond_url;

    public function __construct(){

    }

    /**
     * 生成支付二维码 - 默认方法
     * @return bool
     */
    public function qrCode(){
        return $this->noExists();
    }

    /**
     * 生成支付二维码 - 默认方法
     * @return bool
     */
    public function getCode(){
        return $this->noExists();
    }

    /**
     * HTML5支付 - 默认支付类型
     */
    public function pay(){
        return $this->noExists();
    }

    /**
     * 查询退款
     */
    public function queryRefund(){
        return $this->noExists();
    }

    /**
     * 申请退款
     */
    public function refund(){
        return $this->noExists();
    }

    /**
     * 方法未存在
     * @return bool
     */
    protected function noExists(){
        $this->setError('此支付方式不支持当前操作！');
        return false;
    }

    /**
     * 初始化异步地址
     */
    public function setNotifyUrl($paycode, $paytype = ''){
        $this->notify_url = siteUrl() . 'Home/Payment/Notify/paycode/'.$paycode.(!empty($paytype) ? '/paytype/'.$paytype : '').'.shtml';
    }

    /**
     * 获取异步地址
     */
    public function getNotifyUrl(){
        return $this->notify_url;
    }

    /**
     * 获取面对面支付异步地址
     */
    public function setNotifyF2fUrl($paycode, $paytype = ''){
        $this->notify_f2f_url = siteUrl() . 'Home/Payment/NotifyF2f/paycode/'.$paycode.(!empty($paytype) ? '/paytype/'.$paytype : '').'.shtml';
    }

    /**
     * 获取面对面支付异步地址
     */
    public function getNotifyF2fUrl(){
        return $this->notify_f2f_url;
    }

    /**
     * 初始化同步地址
     */
    public function setRespondUrl($paycode, $paytype = ''){
        $this->respond_url = siteUrl() . 'Home/Payment/Respond/paycode/'.$paycode.(!empty($paytype) ? '/paytype/'.$paytype : '').'.shtml';
    }

    /**
     * 获取同步地址
     */
    public function getRespondUrl(){
        return $this->respond_url;
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
        $this->params[$name] = $value;
        return true;
    }

    /**
     * 获取设置的参数
     * @param string $name  字段名称
     * @return bool|string|array| ... anything
     */
    public function getData($name = ''){
        if(isset($this->params[$name])){
            return $this->params[$name];
        }
        return NULL;
    }

    /**
     * 设置所有参数
     * @return array
     */
    public function setDatas($params = array()){
        $this->params = $params;
        return true;
    }

    /**
     * 获取设置的所有参数
     * @return array
     */
    public function getDatas(){
        return $this->params;
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
     * 获取支付类型
     * @return string
     */
    public function getPayType(){
        $paytype = $this->getData('pay_type');
        if(empty($paytype)){
            $paytype = $this->getData('paytype');
        }
        return $paytype;
    }

    /**
     * 设置错误信息
     * @param string $msg
     * @return bool
     */
    protected function setError($msg = ''){
        if(!empty($msg)){
            $this->error[] = $msg;
        }
        return true;
    }

    /**
     * 把当前设置的参数同步设置到微信内核库
     */
    protected function syncDatas(){
        $params = $this->getDatas();  //获取参数
        $this->pay->setDatas($params);
    }

    /**
     * 异步回调
     * @return bool
     */
    public function notify(){
        $result = $this->pay->notify();
        if($result === false){  //校验失败，数据有问题
            $error = $this->pay->getError();
            if(!empty($error)){
                $this->setError($error);
            }
            return false;
        }else{  //校验成功，有数据
            return $result;
        }
    }

    /**
     * 同步回调
     * @return bool
     */
    public function respond(){
        $result = $this->pay->respond();
        if($result === false){  //校验失败，数据有问题
            $error = $this->pay->getError();
            if(!empty($error)){
                $this->setError($error);
            }
            return false;
        }else{  //校验成功，有数据
            return $result;
        }
    }
}