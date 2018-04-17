<?php
/**
 * ====================================
 * 优惠券类型 模型
 * ====================================
 * Author: 9009123 (Lemonice)
 * Date: 2017-09-18 17:51
 * ====================================
 * File: BonusTypeModel.class.php
 * ====================================
 */
namespace Common\Model\Home;
use Common\Extend\Time;
use Common\Model\CustomizeModel;

class BonusTypeModel extends CustomizeModel{
	protected $_config = 'USER_CENTER';
    protected $_table = 'BonusType';

    /**
     * 根据优惠券类型ID获取当前站点的优惠券类型信息
     * @param int $bonusType 优惠券类型
     * @param int $siteId 站点ID，默认当前站点
     * @return array|mixed
     */
	public function getInfo($bonusType = 0, $siteId = 0){
        if($bonusType <= 0){
            return array();
        }
        $siteId = $siteId > 0 ? $siteId : C('SITE_ID');
        $bonus = $this->where("type_id = '$bonusType' and (FIND_IN_SET('".$siteId."',use_site) or use_site = 0)")->find();
        return !empty($bonus) ? $bonus : array();
    }

    /**
     * 获取线下发放的红包    --  符合条件所有人可用红包
     * @param int $reuse
     * @param int $siteId
     * @return array|mixed
     */
    public function getOfflineBonusList( $reuse = 1,$siteId = 0){
        $time = Time::gmtime();
        $siteId = $siteId > 0 ? $siteId : C('SITE_ID');
        $fields = $this->getDbFields();
        $result = $this->where(array(
            'send_type'=>3,                         //线下发放的红包
            'reuse'=>$reuse,                         //重复使用：0为不可以重复使用，1可以重复使用
            'use_start_date'=>array('EGT',$time),   //使用开始时间
            'use_end_date'=>array("ELT",$time),      //使用结束时间
            '_string'=>"FIND_IN_SET('$siteId',use_site) or use_site = 0",   //'使用站点：0为所有站点可用，数字字符串如：3,4 表示3,4站点可用',
        )
            //  "send_type = 3 and reuse = 1 and use_start_date <= '$time' and use_end_date >= '$time' and (FIND_IN_SET('$siteId',use_site) or use_site = 0)"
          )->getField(implode(",",$fields),true);
        return $result;
    }


    /**
     * 获取指定type_id 列表                    -- 用来和UserBonus联合获取用户优惠券类型列表
     * @param array $type_id
     * @return mixed
     */
    function getUserBonusType($type_id){
        if(empty($type_id)){
            return false;
        }
        $fields = $this->getDbFields();
        $list = $this->where(array("type_id"=>array("in",$type_id)))->getField(implode(",",$fields),true);
        return $list;
    }
}