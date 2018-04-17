<?php
/**
 * ====================================
 * 支付方式信息模型
 * ====================================
 * Author: 9009123 (Lemonice)
 * Date: 2016-07-05 15:46
 * ====================================
 * File: PaymentModel.class.php
 * ====================================
 */
namespace Common\Model\Home;
use Common\Model\CommonModel;

class PaymentModel extends CommonModel{
    /**
     * 取得某支付方式信息
     * @param string $code  支付ID 或 支付方式代码
     * @return mixed
     */
    public function getData($code){
        $field = is_numeric($code) ? 'pay_id' : 'pay_code';
        $data = $this->where(array($field=>$code,'enabled'=>1))->find();
        if (!empty($data)){
            $configList = unserialize($data['pay_config']);
            foreach ($configList as $config){
                $data[$config['name']] = $config['value'];
            }
        }
        return $data;
    }

    /**
     * 处理序列化的支付、配送的配置参数,  返回一个以name为索引的数组
     * @param string $payConfig 配置信息
     * @return array|bool
     */
    public function unserializeConfig($payConfig = ''){
        if (is_string($payConfig) && ($arr = unserialize($payConfig)) !== false){
            $config = array();
            foreach ($arr as $key => $val){
                $config[$val['name']] = $val['value'];
            }
            return $config;
        }else{
            return false;
        }
    }
}