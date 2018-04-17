<?php
/**
 * ====================================
 * 会员等级 模型
 * ====================================
 * Author: 9009123 (Lemonice)
 * Date: 2018-01-22 17:42
 * ====================================
 * File: UserRankModel.class.php
 * ====================================
 */
namespace Common\Model\Home;
use Common\Model\UserCenterModel;

class UserRankModel extends UserCenterModel {
    /**
     * 获取客户的积分详情
     * @param int $custom_id
     * @return array
     */
    public function getUserRank($custom_id = 0){
        $row = array();
        //获取当前总积分和等级
        $Integral = D('Common/Home/IntegralCenter')->field('total_points,rank')->where("customer_id = '$custom_id' AND is_invalid = 0")->find();
        $rank_id = isset($Integral['rank']) ? $Integral['rank'] : 0;
        $row['total_points'] = isset($Integral['total_points']) ? $Integral['total_points'] : 0;
        if($rank_id > 0){
            $row['rank_name'] = $this->where(array('rank_id'=>$rank_id))->getField('rank_name');
            if(is_null($row['rank_name'])){
                $UserRankInfo = $this->field('rank_id,rank_name')->order('rank_name asc,rank_id asc')->find();
                $row['rank_name'] = $UserRankInfo['rank_name'];
                $rank_id = $UserRankInfo['rank_id'];
            }
        }else{
            $UserRankInfo = $this->field('rank_id,rank_name')->order('rank_name asc,rank_id asc')->find();
            $row['rank_name'] = $UserRankInfo['rank_name'];
            $rank_id = $UserRankInfo['rank_id'];
        }
        $row['rank_id'] = $rank_id;
        return $row;
    }
}