<?php
/**
 * ====================================
 * 积分日志
 * ====================================
 * Author: 9004396
 * Date: 2017-08-10 09:26
 * ====================================
 * Project: new.m.chinaskin.cn
 * File: IntegralLogModel.class.php
 * ====================================
 */
namespace Cpanel\Model;

use Common\Model\CpanelUserCenterModel;
use Common\Extend\PhxCrypt;

class IntegralLogModel extends CpanelUserCenterModel
{
    public function filter($params)
    {
        $where = array();
        if (!empty($params['id'])) {
            $where['log.customer_id'] = $params['id'];
        }

        if (!empty($params['state'])) {
            $where['log.state'] = $params['state'];
        }

        if(!empty($params['rank'])){
            if($params['rank'] == '1'){
                $where['i.rank'] = array(array('eq',1),array('eq',0), 'or');
            }else{
                $where['i.rank'] = $params['rank'];
            }
        }

        if(!empty($params['point_type'])){
            $printType = $params['point_type']-1;
            $where['log.point_type'] = $printType;
        }


        if (!empty($params['type'])) {
            $map = array();
            $keyword = $params['keyword'];
            switch ($params['type']) {
                case 1:
                    $where['user_id'] = intval($keyword);
                    break;
                case 2:
                    $map['custom_no'] = trim($keyword);
                    break;
                case 3:
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


        if(!empty($params['use_start_date']) && !empty($params['use_end_date'])){
            $time_start=strtotime($params['use_start_date'])-28800;
            $time_ent=strtotime($params['use_end_date'])+57600;
            $where['log.add_time'] = array(array('EGT', $time_start), array('ELT', $time_ent));
        }



        $this->alias('log')
            ->join('__INTEGRAL__ AS i ON i.id=log.integral_id','left')
            ->field('log.*,i.rank');
        $this->order('log.log_id DESC');


        return $this->where($where);
    }


    public function format($data)
    {
        if (!empty($data)) {
            $rank = D('UserRank')->getRankList();
            $userModel = D('UsersCenter');
            foreach ($data['rows'] as &$item) {
                $item['rank_name'] = empty($item['rank']) ? current($rank) : $rank[$item['rank']];
                $item['custom_on'] = $userModel->where(array('custom_id' => $item['customer_id']))->getField('custom_no');
                switch ($item['state']) {
                    case 2:
                        $item['state_text'] = '<font color="green">' . L('INTEGRAL_GOODS_REMOVE') . '</font>';
                        break;
                    case 0:
                        $item['state_text'] = '<font color="green">' . L('INTEGRAL_NORMAL') . '</font>';
                        break;
                    case -1:
                        $item['state_text'] = '<font color="red">' . L('INTEGRAL_REMOVE') . '</font>';
                        break;
                    case -2:
                        $item['state_text'] = '<font color="green">' . L('INTEGRAL_EXPIRED') . '</font>';
                        break;
                    case -3:
                        $item['state_text'] = '<font color="red">' . L('INTEGRAL_SELF_CONSUME') . '</font>';
                        break;
                    case -4:
                        $item['state_text'] = '<font color="red">' . L('INTEGRAL_CUSTOMER_CONSUME') . '</font>';
                        break;
                }
                switch ($item['point_type']) {
                    case 0:
                        $item['type_text'] = L('ORDER_INTEGRAL');
                        break;
                    case 1:
                        $item['type_text'] = L('CHECK_IN_INTEGRAL');
                        break;
                    case 2:
                        $item['type_text'] = L('COMMENT_INTEGRAL');
                        break;
                    case 3:
                        $item['type_text'] = L('CONSUME_INTEGRAL');
                        break;
                }

            }
        }
        return $data;
    }

}