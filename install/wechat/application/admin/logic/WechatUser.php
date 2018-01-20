<?php
/**
 * ====================================
 * 商品管理 - 逻辑层
 * ====================================
 * Author: 9009123
 * Date: 2017-11-23 14:46
 * ====================================
 * File: Order.php
 * ====================================
 */

namespace app\admin\logic;

use app\admin\BaseLogic;


class WechatUser extends BaseLogic{
    public function _before_save(){
        $id = intval($this->data['id']);
        if($id <= 0){
            $this->err = '不允许添加微信用户';
            return false;
        }
        $remark = $this->data['remark'];  //备注
        $this->data = array(
            'id'=>$id,
            'remark'=>$remark,
        );
    }

    /**
     * 格式化列表数据
     * @param $data
     * @return mixed
     */
    public function format($data){
        if(isset($data['rows']) && !empty($data['rows'])){
            $wechat_account_id = array_column($data['rows'],'wechat_account_id');
            $res = model('WechatAccount')->whereIn('id',$wechat_account_id)->column('id,text');
            foreach($data['rows'] as $key=>$value){
                //微信公众号名称
                $value['wechat_account'] = $res[$value['wechat_account_id']];
                $data['rows'][$key] = $value;
            }
        }
        return $data;
    }


}