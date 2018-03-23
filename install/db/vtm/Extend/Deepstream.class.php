<?php
/**
 * ====================================
 * 请求Deepstream
 * ====================================
 * Author: 9009123 (Lemonice)
 * Date: 2017-08-08 15:48
 * ====================================
 * File: Deepstream.class.php
 * ====================================
 */
namespace Common\Extend;
use WebSocket\Client;

class Deepstream{
    /**
     * 请求的地址、目标
     * @var null
     */
    private $address = NULL;
    /**
     * 请求参数
     * @var array
     */
    private $params = NULL;
    /**
     * 返回数据
     * @var null
     */
    private $response = NULL;
    /**
     * 请求包中的字符
     * @var null
     */
    protected $chr31 = NULL;
    protected $chr30 = NULL;
    /**
     * 句柄
     * @var null
     */
    protected $client = NULL;
    /**
     * 是否链接成功
     * @var bool
     */
    private $is_connect = false;
    /**
     * 配置储存
     * @var array
     */
    private $config = array();

    private $log_path = '';

    public function __construct(){
        $this->log_path = LOG_PATH . 'deepstream/'.MODULE_NAME.'_'.date('Y-m-d').'.log';
        if(!file_exists($this->log_path)){
            makeDir(LOG_PATH . 'deepstream');
            @file_put_contents($this->log_path, 'Create File!'."\n\n");
            @chmod($this->log_path, 0777);
            @exec("chmod 777 ".$this->log_path);
        }

        $this->writeLog('From: '.MODULE_NAME.'/' . CONTROLLER_NAME . '/' . ACTION_NAME . ', Link: ' . $_SERVER['REQUEST_URI'] . "\n");  //记录来源

        $this->chr31 = chr(31);
        $this->chr30 = chr(30);
        //获取配置
        $this->config = C('DEEPSTREAM');
        //连接websocket并且认证登录
        $this->auth();
    }

    /**
     * RPC请求
     * @return string
     */
    protected function rpc(){
        if(!$this->is_connect){
            //$this->auth();
            //if(!$this->is_connect){
                return false;
            //}
        }
        $address = $this->getAddress();
        $params = $this->getParams();
        if(is_null($address) || is_null($params)){
            return false;
        }
        $key = substr(md5(rand(10000,99999) . session_id() . time()),0,16);
        $send_msg = 'P'.$this->chr31.'REQ'.$this->chr31.$address.$this->chr31.$key.$this->chr31.'O'.json_encode($params).$this->chr30;

        $this->client->send($send_msg);
        $this->writeLog('Send: '. $send_msg . "\n");  //记录日记
        unset($send_msg);
        //避免重复调用，清空
        $this->setResponse(NULL);
        $flag_start = 'P'.$this->chr31.'RES'.$this->chr31.$address.$this->chr31.$key.$this->chr31.'O';
        while(1){
            $response = $this->client->receive();
            if($response === false || is_null($response) || strstr($response, $flag_start) !== false){
                break;
            }
        }
        $this->writeLog('Response: '. $response . "\n");  //记录日记

        //处理数据
        $response = substr(str_replace($flag_start,'',$response),0,-1);
        if(!empty($response) && substr($response,0,1) == '{'){
            $this->setResponse(!empty($response) ? $response : NULL);
            return true;
        }
        return false;
    }

    /**
     * 关闭socket
     */
    public function close(){
        $this->client->close();
        $this->writeLog("Close Connected! \n");  //记录日记
        $this->writeLog("Finish Time: ".date('Y-m-d H:i:s')."\n\n");  //记录日记
    }

    /**
     * 连接websocket并且认证登录
     */
    private function auth(){
        $this->writeLog("Start Time: ".date('Y-m-d H:i:s')."\n");  //记录日记
        $this->writeLog('Link: ws://'.$this->config['HOST'].':'.$this->config['PORT'].'/deepstream' . "\n");  //记录日记

        $this->client = new Client('ws://'.$this->config['HOST'].':'.$this->config['PORT'].'/deepstream');
        $times = 3;
        for($i=0;$i<$times;$i++){
            $this->client->start();
            $this->is_connect = $this->client->isConnected();
            $this->writeLog("Do Connected: ".date('Y-m-d H:i:s')."\n");  //记录日记
            if($this->is_connect){
                break;
            }
        }
        if($this->is_connect){
            //开始握手
            $this->handShake();
            //登录
            $this->login();
        }else{
            $this->writeLog("Not Connected!\n\n");  //记录日记
        }
    }

    /**
     * 握手
     */
    private function handShake(){
        $send_msg = 'C'.$this->chr31.'CHR'.$this->chr31.'ws://'.$this->config['HOST'].':'.$this->config['PORT'].'/deepstream'.$this->chr30;
        //建立握手，最多重试3次
        for($i = 0;$i <= 2;$i++){
            $this->client->send($send_msg);
            $this->writeLog('HandShake Send: '. $send_msg . "\n");  //记录日记
            $response = $this->client->receive();
            $this->writeLog('HandShake Response: '. $response . "\n");  //记录日记
            if($response == 'C'.$this->chr31.'CH'.$this->chr30){
                $this->writeLog("HandShake Success!\n");  //记录日记
                break;  //握手成功
            }
        }
    }

    /**
     * 认证
     */
    private function login(){
        //登录认证，最多重试3次
        for($i = 0;$i <= 2;$i++){
            $send_msg = 'A'.$this->chr31.'REQ'.$this->chr31.json_encode(array(
                    'username'=>$this->config['USERNAME'],
                    'password'=>$this->config['PASSWORD'],
                    'group'=>$this->config['GROUP'],
                )).$this->chr30;
            $this->client->send($send_msg);
            $this->writeLog('Login Send: '. $send_msg . "\n");  //记录日记
            //由于有其他数据包，认证最多收4个数据包做认证判断是否成功
            for($n = 0;$n <= 3;$n++){
                $response = $this->client->receive();
                $this->writeLog('Login Response: '. $response . "\n");  //记录日记
                if(strstr($response, 'A'.$this->chr31.'A'.$this->chr31)){
                    $this->writeLog("Login Success!\n");  //记录日记
                    $i = $i + 3;  //最外层不用再循环
                    break;
                }
            }
        }
    }

    /**
     * 根据不同数据前缀解析数据
     * @param string $k
     * @param string $data
     * @return int|mixed|string
     */
    protected function decodeData($k = '', $data = ''){
        switch($k){
            case 'S':  //字符串
                break;
            case 'N':  //数字
                $data = intval($data);
                break;
            case 'O':  //JSON对象
                $data = json_decode($data, true);
                break;
        }
        return $data;
    }

    /**
     * 根据不同数据加前缀
     * @param $data
     * @return int|mixed|string
     */
    protected function encodeData($data){
        if(is_array($data)){
            $data = 'O'.json_encode($data);
        }else if(is_numeric($data)){
            $data = 'N'.$data;
        }else{
            $data = 'S'.$data;
        }
        return $data;
    }

    /**
     * 设置属性
     * @param string $field
     * @param string $value
     * @return bool
     */
    protected function setParam($field = '', $value = ''){
        if(empty($field) || (!is_null($this->params) && !is_array($this->params))){
            return false;
        }
        if(is_null($this->params)){
            $this->params = array();  //初始化数组
        }
        $this->params[$field] = $value;
        return true;
    }

    /**
     * 设置属性
     * @param string $field
     * @return null
     */
    protected function getParam($field = ''){
        if(!isset($this->params[$field])){
            return NULL;
        }
        return $this->params[$field];
    }

    /**
     * 设置所有属性
     * @param string|array $params
     * @return bool
     */
    protected function setParams($params){
        $this->params = $params;
        return true;
    }

    /**
     * 获取所有属性
     * @return string|array
     */
    protected function getParams(){
        return $this->params;
    }

    /**
     * 设置访问目标地址
     * @param string $address
     * @return bool
     */
    protected function setAddress($address = ''){
        $this->address = $address;
        return true;
    }

    /**
     * 获取访问目标地址
     * @return string
     */
    protected function getAddress(){
        return $this->address;
    }

    /**
     * 设置返回的数据
     * @param array|string $response
     * @return string
     */
    private function setResponse($response){
        $this->response = @json_decode(trim($response));
        return true;
    }

    /**
     * 获取返回的数据
     * @return string
     */
    protected function getResponse(){
        return $this->response;
    }

    /**
     * 记录日记
     * @param string $log_msg
     */
    public function writeLog($log_msg = ''){
        @file_put_contents($this->log_path, $log_msg, FILE_APPEND);
    }
}