<?php
/**
 * ====================================
 * 用户红包模型
 * ====================================
 * Author: 9009123 (Lemonice)
 * Date: 2017-09-18 18:17
 * ====================================
 * File: UserBonusModel.class.php
 * ====================================
 */
namespace Common\Model\Home;
use Common\Model\CustomizeModel;
use Common\Extend\Time;

class UserBonusModel extends CustomizeModel{
	protected $_config = 'USER_CENTER';
    protected $_table = 'UserBonus';

    /**
     * 获取优惠劵信息
     * @param int $bonusId 优惠劵id
     * @param string $bonusSn 优惠劵号
     * @return bool|mixed
     */
    public function getInfo($bonusId = 0, $bonusSn = ''){
        if($bonusId <= 0 && empty($bonusSn)){
            return false;
        }
		$where = array();
		if($bonusId > 0){
			$where['bonus_id'] = $bonusId;
        }else if(!empty($bonusSn)){
			$where['bonus_sn'] = $bonusSn;
        }
		$data = $this->field('*')->where($where)->find();
        return $data;
    }

    /**
     * 获取某个用户名下未使用的单张优惠券ID
     * @param int $bonusType  优惠券类型
     * @param int $userId  用户ID，默认为当前登录用户
     * @return int
     */
    public function getUserBonusId($bonusType = 0, $userId = 0){
        if($bonusType <= 0){
            return 0;
        }
        $userId = $userId > 0 ? $userId : $this->user_id;
        if($userId <= 0){
            return 0;
        }
        $day = Time::localGetdate();
        $today  = Time::localMktime(23, 59, 59, $day['mon'], $day['mday'], $day['year']);
        $where = "b.bonus_type_id = '$bonusType' AND t.use_end_date >= '$today' AND b.user_id = '".$userId."' AND b.order_id = 0";
        $this->alias(' AS b')->join("__BONUS_TYPE__ AS t ON t.type_id = b.bonus_type_id", 'left');
        $bonusId = $this->where($where)->order('RAND()')->getField('b.bonus_id');
        return $bonusId>0 ? $bonusId : 0;
    }


    /**
     *    获取用户可以使用的优惠劵         需要和BonusType配合验证
     * @Author abraa
     * @param int $userId 用户id
     * @param int $page
     * @param int $pageSize
     * @return array
     */
    public function  getUserBonus($userId = 0 ,$page = 1 , $pageSize = 0){
        $userId = $userId > 0 ? $userId : $this->user_id;
        if(empty($userId)){
            return array();
        }
        $day = Time::localGetdate();
        $today  = Time::localMktime(23, 59, 59, $day['mon'], $day['mday'], $day['year']);
        $this->where(array("use_end_date"=>array("EGT",$today),"user_id"=>$userId,'order_id'=>0))
            ->group('bonus_type_id')->order('start_time asc');
        // ===分页 ====
        if($pageSize>0){
            $subQuery  = $this->buildSql();
            $start = ($page -1) * $pageSize;
            $this->table($subQuery.' a')->limit($start,$pageSize);
        }
        $result = $this->getField("bonus_type_id ,bonus_id,user_id, bonus_sn, start_time, end_time, site_id, order_id,count(bonus_type_id) as count",true);
        return $result;
    }
}