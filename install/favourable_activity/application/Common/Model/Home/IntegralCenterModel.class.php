<?php
/**
 * ====================================
 * 会员中心 里面的积分模型
 * ====================================
 * Author: 9009123 (Lemonice)
 * Date: 2017-02-08 13:49
 * ====================================
 * File: IntegralCenterModel.class.php
 * ====================================
 */
namespace Common\Model\Home;
use Common\Model\UserCenterModel;

class IntegralCenterModel extends UserCenterModel{
	protected $_config = 'USER_CENTER';
    protected $tableName = 'integral';

    /**
     * 获取用户的可用积分 - 会计算被冻结的积分在内
     * @param int $user_id  用户ID
     * @param int $custom_id  客户ID
     * @return array
     */
    public function getPointsLeft($user_id = 0, $custom_id = 0) {
        $integral = array(
            'user_points'=>0,
            'points_left'=>0,
            'freeze'=>0,
        );
        if($user_id <= 0 && $custom_id <= 0){
            return $integral;
        }
        if($custom_id <= 0){
            $custom_id = D('Common/Home/Users')->where("user_id = '$user_id'")->getField('custom_id');  //查询用户客户ID
            if($custom_id <= 0){
                return $integral;
            }
        }
        $user_points = $this->field('is_invalid,points_left')->where("customer_id = '$custom_id' AND is_invalid = 0")->getField('points_left');  //查询用户可用积分
        if(!$user_points){
            return $integral;
        }

        //获取被冻结的积分  -  2017-06-28废除冻结机制
        //$freeze = D('UserPointFreeze')->getUserFreezeSum($user_id);
        $freeze = 0;

        $integral = array(
            'user_points'=>($user_points + $freeze > 0 ? $user_points + $freeze : 0),
            'points_left'=>$user_points,
            'freeze'=>$freeze,
        );
        $integral['user_points'] = $integral['user_points'] > 0 ? $integral['user_points'] : 0;
        return $integral;
    }

    /**
     * 获取用户的等级
     * @param int $user_id 用户ID
     * @param int $custom_id 客户ID
     * @return int|mixed
     */
    public function getUserRank($user_id = 0, $custom_id = 0) {
        if($user_id <= 0 && $custom_id <= 0){
            return 1;
        }
        if($custom_id <= 0){
            $custom_id = D('Common/Home/Users')->where("user_id = '$user_id'")->getField('custom_id');  //查询用户客户ID
            if($custom_id <= 0){
                return 1;
            }
        }
        $rank = $this->where("customer_id = '$custom_id' AND is_invalid = 0")->getField('rank');
        if(!$rank){
            return 1;
        }
        return $rank;
    }
}
