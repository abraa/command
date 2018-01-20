<?php
/**
 * ====================================
 * 微信关注用户模型
 * ====================================
 * Author: 1002571
 * Date: 2017/11/9 10:56
 * ====================================
 * Project: ggzy
 * File: WechatUser.php
 * ====================================
 */

namespace app\admin\model;

use app\admin\BaseModel;

class WechatUser extends BaseModel {



    protected $autoWriteTimestamp = true;

    /**
     *  初始化模型
     * @access protected
     * @return void
     */
    protected function initialize()
    {
        parent::initialize();
    }




    /**
     *  修改微信用户数据
     * @param array $params 微信用户信息
     * @param array $where
     * @return false|int
     */
    function updateUser($params = [],$where=[]){
        if(isset($params['subscribe'])&& 0 == $params['subscribe']){           //取消关注 -- 修改取消关注时间
            $this->cancel_time = time()  ;
        }
        return $this->isUpdate(true)->allowField(true)->save($params,$where);
    }


    /**
     * 修改器 tagid_list 传入数组会转成字符串
     * @param $value
     * @return int
     */
    public function setTagidListAttr($value){
        if(is_array($value)){
            $value = implode(",",$value);
        }
        return $value;
    }

    /**
     * 修改器 wechat_account_id 传入空字符串或NULL自动转为0
     * @param $value
     * @return int
     */
    public function setWechatAccountIdAttr($value){
        if(empty($value)){
            $value = 0;
        }
        return $value;
    }

    /**
     * 列表查询字段
     * @param array $data
     */
    public function filter($data = array()){
        $where = array();
        if(isset($data['subscribe']) && $data['subscribe'] != '-1'){
            $where['subscribe'] = $data['subscribe'];
        }
        if(isset($data['keyword']) && $data['keyword'] != ''){
            $where['openid|unionid|nickname|city|province|country|remark'] = array('LIKE', '%'.trim($data['keyword']).'%');
        }
        empty($where) || $this->where($where);
    }
}