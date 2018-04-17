<?php
/**
 * ====================================
 * 测试模版
 * ====================================
 * Author: 9004396
 * Date: 2016-07-06 17:29
 * ====================================
 * File:IndexModel.class.php
 * ====================================
 */
namespace Home\Model;

use Common\Model\CommonModel;

class QrcodeModel extends CommonModel{
    protected $tableName = 'customer_qrcode';

    /**
     * 根据等级获取队列数据
     * @param int $qrcode_type
     * @param string $field
     * @return mixed
     */
    public function getQrcodeByLevel($qrcode_type = 0, $field = ''){

        $where = array(
            'is_show' => 0,
            'locked' => 0,
            'kefu_level' => array('gt', 0),
            '_string' => "FIND_IN_SET('{$qrcode_type}',qrcode_type)",
        );
        $field = $field ? $field : 'id,weixin,kefu_qrcode';
        $res = $this->where($where)->field($field)->order('kefu_level desc')->select();
        return $res;
    }

    /**
     * 获取二维码
     * @param $id
     * @return mixed
     */
    public function getQrcodeOne($id){
        $where = array(
            'is_show' => 0,
            'locked' => 0,
            'id' => $id,
        );
        return $this->where($where)->field('id,weixin,kefu_qrcode')->find();
    }
}