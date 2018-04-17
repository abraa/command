<?php
/**
 * ====================================
 * 业务逻辑层主父类 - 用于业务层传参
 * ====================================
 * Author: 9009123 (Lemonice)
 * Date: 2017-09-01 14:44
 * ====================================
 * File: LogicData.class.php
 * ====================================
 */
namespace Common\Logic;

class LogicData {
    /**
     * 数据
     * @var array
     */
    protected $data = array();
    /**
     * 错误信息
     * @var array
     */
    protected $errorMsg = array();
    public function __construct() {

    }

    /**
     * 设置数据 - 单个字段
     * @param string $field
     * @param $value
     */
    public function setData($field = '', $value){
        $this->data[$field] = $value;
    }

    /**
     * 获取数据 - 单个字段
     * @param string $field
     * @return null
     */
    public function getData($field = ''){
        return isset($this->data[$field]) ? $this->data[$field] : NULL;
    }

    /**
     * 设置数据
     * @param array $data
     */
    public function setDatas($data = array()){
        $this->data = $data;
    }

    /**
     * 获取数据
     * @return array
     */
    public function getDatas(){
        return $this->data;
    }

    /**
     * 设置错误信息 - 单个错误信息
     * @param string $errorMsg
     */
    protected function setError($errorMsg = ''){
        if(!empty($errorMsg)){
            $this->errorMsg[] = $errorMsg;
        }
    }

    /**
     * 获取第一个报错信息 - 单个信息
     * @return string
     */
    public function getError(){
        return isset($this->errorMsg[0]) ? $this->errorMsg[0] : '';
    }

    /**
     * 获取所有报错信息 - 多个信息
     * @return string
     */
    public function getErrors(){
        return !empty($this->errorMsg) ? $this->errorMsg : NULL;
    }

    /**
     * 设置错误信息 - 单个错误信息
     * @param array $errorMsg
     */
    protected function setErrors($errorMsg = array()){
        $this->errorMsg[] = $errorMsg;
    }
}