<?php
/**
 * ====================================
 * 订单详情信息模型
 * ====================================
 * Author: 9009123 (Lemonice)
 * Date: 2017-09-30 10:01
 * ====================================
 * File: OrderInfoModel.class.php
 * ====================================
 */
namespace Common\Model\Home;
use Common\Model\CommonModel;
use Common\Extend\Time;

class OrderInfoModel extends CommonModel{
    /**
     * 检查是否频繁下单
     * @param int $seconed  秒数，这么多秒内超过多少次不可再下单
     * @param int $frequency  次数，这段时长内超过这么多次即不可再下单
     * @return bool
     */
    public function checkIpFrequently($seconed = 600, $frequency = 3){
        $realIp = get_client_ip();
        $limitTime  = Time::gmtime() - $seconed;
        $where = array(
            'add_time'=>array('GT',$limitTime),
            'ip_address'=>$realIp,
        );
        $count = $this->where($where)->count();
        if($count > $frequency){
            return false;
        }
        return true;
    }
}