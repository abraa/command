<?php
/**
 * ====================================
 * 微信账号模型
 * ====================================
 * Author: 1002571
 * Date: 2017/11/9 10:56
 * ====================================
 * Project: ggzy
 * File: WechatAccount.php
 * ====================================
 */


namespace app\admin\model;

use app\admin\BaseModel;

class WechatAccount extends BaseModel {

    /**
     * 获取配置信息
     * @param int $index
     * @return array
     * @throws \think\Exception
     */
    function getWechatConfig($index = 0){
        if(empty($index)){
            return self::column("*",$this->getPk());
        }else{
            return self::get($index)->toArray();
        }
    }

    /**
     * 列表查询字段
     * @param array $data
     */
    public function filter($data = array()){
        $where = array();
        if(isset($data['keyword']) && $data['keyword'] != ''){
            $where = array(
                'text|token|app_id|app_secret|machine_id|pay_key|crypted|encoding_aes_key'=>array('LIKE', '%'.trim($data['keyword']).'%')
            );
        }
        empty($where) || $this->where($where);
    }
}