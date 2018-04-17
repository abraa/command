<?php
/**
 * Created by PhpStorm.
 * User: 1002571    -- abraa
 * Date: 2017/9/5
 * Time: 15:53
 */

namespace Common\Model\Home;
use Common\Model\CommonModel;
use Common\Extend\Time;

class FavourableActivityModel extends CommonModel{

    /**
     * 获取当前活动信息
     * @param int $act_id       活动id
     * @return mixed
     */
    function getActivity($act_id){
        $activity = $this
            ->where(array("act_id"=>$act_id))
            ->getField('`act_id`,`act_name`,`start_time` ,`end_time`,`user_rank` ,`act_range` ,`act_range_ext`,`shipping_free`,
                                `min_amount` ,`max_amount`,`act_type` ,`act_type_ext`,`gift`,`gift_package`,`stock_limited`,
                                `is_join_amount`,`gift_range`,`gift_range_price`,`level_type`,`conflict_act`,`is_after_discount`');
        return $activity[$act_id];

    }

    /**
     * 获取是否勾选 ，此优惠活动的商品总价格也将用于优惠的价格限制计算
     * @param int $act_id    活动id
     * @return mixed    1参与
     */
    function getIsJoinAmount($act_id){
        $result = $this->where(array("act_id"=>$act_id))->getField("is_join_amount");
        return $result;
    }

    /**
     * 获取优惠品选购限制范围          act_type_ext
     * @param $act_id
     * @param array $actInfo           没有传则通过act_id获取
     * @return array|string          ksort()
     */
    function getActTypeExt($act_id , $actInfo = array()){
        if(empty($actInfo)){
            $actInfo = $this->getActivity($act_id);
        }
        if(false === strpos($actInfo['act_type_ext'],",") && false === strpos($actInfo['act_type_ext'],"|")){           // 1|2,3|4
            return $actInfo['act_type_ext'];                                                //如果不是多个或者为空直接返回
        }
        $act_type_ext = array();
        $act_array = explode(",",$actInfo['act_type_ext']);
        foreach($act_array as $ext){
            $ext_array = explode('|',$ext);
            $key = $ext_array[0]?$ext_array[0]: 0;                  //前一个框  -- 条件
            $value = $ext_array[1]?$ext_array[1]: 0;                //后一个框  -- 结果             //看不懂看后台这个地方的填写方法
            $act_type_ext[$key] = $value;
        }
        ksort($act_type_ext);
        return $act_type_ext;
    }

    /**
     * 获取有效期内的所有活动
     * @param string $now
     * @return mixed
     */
    public function getAllActivity($now = "")
    {
        if(empty($now)){
            $now = Time::gmTime();
        }
        $activity = $this
            ->where('start_time<=' . $now . ' and end_time>= ' . $now)
            ->getField('`act_id`,`act_name`,`start_time` ,`end_time`,`user_rank` ,`act_range` ,`act_range_ext`,`shipping_free`,
                                `min_amount` ,`max_amount`,`act_type` ,`act_type_ext`,`gift`,`gift_package`,`stock_limited`,
                                `is_join_amount`,`gift_range`,`gift_range_price`,`level_type`,`conflict_act`,`is_after_discount`',true);
        return $activity;
    }

    /**
     * 检查活动是否已经开始           -- 最新活动是否可用
     * @param $now
     * @return bool
     */
    function getActivityStarted($now = ""){
        if(empty($now)){
            $now = Time::gmTime();
        }
        $result = $this->field("start_time,end_time")->order("start_time desc")->find();
        if($now>=$result['start_time'] && $now<=$result['end_time']){
            return true;
        }else{
            return false;
        }
    }
}