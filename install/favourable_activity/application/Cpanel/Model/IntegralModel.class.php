<?php
/**
 * ====================================
 * 积分模型
 * ====================================
 * Author: 9004396
 * Date: 2017-02-24 10:07
 * ====================================
 * Project: new.m.chinaskin.cn
 * File: IntegralModel.class.php
 * ====================================
 */
namespace Cpanel\Model;

use Common\Extend\PhxCrypt;
use Common\Model\CpanelUserCenterModel;

class IntegralModel extends CpanelUserCenterModel
{


    public function filter($params)
    {
        $where = array();
        if (!empty($params['type'])) {
            $map = array();
            $keyword = $params['keyword'];
            switch ($params['type']) {
                case 1:
                    $map['user_id'] = intval($keyword);
                    break;
                case 2:
                    $map['custom_no'] = trim($keyword);
                    break;
                case 3:
                    $map['email'] = array('LIKE', "%{$keyword}%");
                    break;
                case 4:
                    $map['mobile'] = PhxCrypt::phxEncrypt($keyword);
                    break;
            }
            if (!empty($map)) {
                $custom_id = D('UsersCenter')->where($where)->getField('custom_id');
                if(!empty($custom_id)){
                    $where['customer_id'] = $custom_id;
                }
            }
        }


        if (!empty($params['rank'])) {
            if ($params['rank'] == '1') {
                $where['rank'] = array(array('eq', 1), array('eq', 0), 'OR');
            } else {
                $where['rank'] = $params['rank'];
            }
        }
        $min_integral = empty($params['min_integral']) ? 0 : $params['min_integral'];
        $max_integral = empty($params['max_integral']) ? 0 : $params['max_integral'];
        if ($max_integral > 0 && $min_integral > 0 && $min_integral <= $max_integral) {
            $where['points_left'] = array(array('EGT', $min_integral), array('ELT', $max_integral));
        } elseif ($min_integral > 0 && $max_integral == 0) {
            $where['points_left'] = array('EGT', $min_integral);
        } elseif ($max_integral > 0 && $min_integral == 0) {
            $where['points_left'] = array('ELT', $max_integral);
        }
        $this->order('id DESC');
        return $this->where($where);
    }


    public function format($data)
    {
        $rank = D('UserRank')->getRankList();
        $userModel = D('UsersCenter');
        if (!empty($data)) {
            foreach ($data['rows'] as &$item) {
                $item['custom_on'] = $userModel->where(array('custom_id' => $item['customer_id']))->getField('custom_no');
                $item['rank_name'] = empty($item['rank']) ? current($rank) : $rank[$item['rank']];
            }
        }
        return $data;
    }


}