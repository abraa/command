<?php
/**
 * ====================================
 * 积分类
 * ====================================
 * Author: 9004396
 * Date: 2017-02-06 15:00
 * ====================================
 * Project: new.m.chinaskin.cn
 * File: Integral.class.php
 * ====================================
 */

namespace Common\Extend;

use Think\Model;

class Integral
{

    private $user_id = 0;
    private $err;
    private $state = array(-4, -3, -2, -1, 0, 2, 3, 4);
    private $extendRule = array(
        1 => 'PRE_SALE',
        2 => 'AFTER_SALE',
        3 => 'ORDER',
        4 => 'SURVEY',
    );

    public function __construct()
    {
        if (is_null($this->user_id)) {
            $this->user_id = session('user_id');
        }
    }


    /**
     * 获取积分详情
     * @param int $user_id 会员ID
     * @return mixed
     */
    public function getUserIntegralInfo($user_id)
    {
        $accountModel = D('Home/UserAccount');
        $pointLogModel = D('Home/UserPointLog');
        $accountInfo = $accountModel->where(array('user_id' => $user_id))->find();
        $accountInfo['points_left'] = isset($accountInfo['points_left']) ? $accountInfo['points_left'] : 0;
        $accountInfo['total3'] = 0;

        $pointInfo = $pointLogModel->where(array('user_id' => $user_id))->select();
        if (empty($pointInfo)) {
            return $accountInfo;
        }

        $pointLeft = $accountInfo['points_left'];
        foreach ($pointInfo as $point) {
            $pointLeft -= $point['points'];
            if ($pointLeft == 0) {
                $logId = $point['log_id'];
                break;
            }
        }

        $logId = (isset($logId) && $logId) ? $logId : 0;
        $points = $pointLogModel->where(array('log_id' => array('EGT', $logId), 'user_id' => $user_id))->select();
        $sTime = Time::localStrtotime("-1 Year");
        $eTime = Time::localStrtotime("-9 month");
        $point3 = 0;
        $before = 0;
        $after = 0;
        foreach ($points as $point) {
            if ($point['add_time'] < $sTime) {
                $before += $point['points'];
            } elseif ($points['add_time'] > $eTime) {
                if ($point['state'] == -3 || $point['state'] == -2) {
                    $after += $point['points'];
                }
            } else {
                $point3 += $point['points'];
            }
        }

        if ($point3 == 0) {
            return $accountInfo;
        }
        $lastPoint = $point3 + $before + $after;
        $accountInfo['total3'] = $lastPoint < 0 ? 0 : $accountInfo['total3'];
        return $accountInfo;

    }

    /**
     * 积分变化（新）只处理积分
     * @param int $site_id 站点ID
     * @param int $point 增减的积分(增加不加符号，减少在积分前面加'-')
     * @param string $remark 积分说明
     * @param int $state 状态 -1：删除（订单退货），0：正常，-2（积分过期），-3自主消费，-4客服消费,2:积分商品删除,3:订单无效，4:取消订单
     * @param bool $isMultiple 生日是否获取双倍积分
     * @param array $extra 扩展字段：暂时支持order_sn,order_id,user_id,can_be_debts(是否已经扣减为负数积分),type(积分类型 0:订单积分，1:签到积分，2:评论积分, 3:积分消费，4:转移积分),extend_type(2.评论积分[1.售前，2.售后，3.订单，4.品牌])
     * @return bool
     */
    public function newVariety($site_id = 0, $point = 0, $remark = '', $state = 0, $isMultiple = false, $extra = array())
    {
        $site_id = (is_numeric($site_id) && $site_id > 0) ? $site_id : 0;
        $this->user_id = (isset($extra['user_id']) && $extra['user_id'] > 0) ? $extra['user_id'] : $this->user_id;

        if (empty($remark)) {
            $this->setError(6);
            return false;
        }

        if (!in_array($state, $this->state)) {
            $this->setError(9);
            return false;
        }

        $extRet = $this->extend($extra, 'IntegralLogCenter');
        if ($extRet == false) {//扩展处理
            $this->setError(1);
            return false;
        }

        if (empty($point)) {
            $point = is_int($extRet) ? $extRet : 0;
        }
        if (empty($point)) {  //积分为0
            $this->setError(2);
            return false;
        }
        if (!isset($extra['custom_id']) && empty($this->user_id)) {  //会员不存在
            $this->setError(3);
            return false;
        }
        $rank  = isset($extra['rank']) ? abs(intval($extra['rank'])) : 0;

        $model = isset($extra['model']) ? $extra['model'] : new Model('', null, 'USER_CENTER');  //初始化模型，绑定会员中心库
        if($this->user_id > 0){
            //通过会员ID,寻找对应的客户编号
            $userInfo = $model->table('users')
                ->alias('u')
                ->join('__USER_INFO__ AS ui ON u.user_id=ui.user_id', 'LEFT')
                ->field('ui.birthday,u.custom_id')
                ->where(array('u.user_id' => $this->user_id))
                ->find();
            if (empty($userInfo)) {
                $this->setError(3);
                return false;   //会员信息不存在
            }

            if (empty($userInfo['custom_id'])) {  //客户编号未绑定，无法获取积分
                $this->setError(4);
                return false;
            }
            $multiple = 1;
            if ($isMultiple && !empty($userInfo['birthday'])) { //生日获取双倍积分
                $birthday = date('m-d', $userInfo['birthday']);
                $addTime = Time::gmTime();
                if ($birthday == date('m-d', $addTime)) {
                    $multiple *= 2;
                }
            }
            $point = $point * $multiple;
        }else{
            $userInfo = array(
                'custom_id'=>$extra['custom_id'],
            );
        }

        isset($extra['not_db_trans']) or $model->startTrans(); //开启事务
        $flag = false;

        //检测积分记录是否存在
        $integral = $model->table('integral')->where(array('customer_id' => $userInfo['custom_id']))->find();

        //检查是否无效的积分
        if(isset($integral['is_invalid']) && $integral['is_invalid'] == 1){
            $this->setError(10);  //积分无效
            return false;
        }
        if (empty($integral)) { //积分账户不存在
            if ($point < 0) {
                $this->setError(5);
                return false;
            }
            $integralData = array(
                'customer_id' => $userInfo['custom_id'],
                'rank' => $rank,
                'total_points' => $point,
                'points_left' => $point,
            );
            $integral['id'] = $integralRet = $model->table('integral')->add($integralData);
        } else {
            if ($point < 0 && (!isset($extra['can_be_debts'])||$extra['can_be_debts']==false)) {
                if (abs($point) > $integral['points_left']) {
                    $this->setError(7);
                    return false;
                }
                $integralData = array(
                    'points_left' => $integral['points_left'] + $point,
                    'pay_points' => $integral['pay_points'] - $point,
                );
            } else {
                $integralData = array(
                    'total_points' => $integral['total_points'] + $point,
                    'points_left' => $integral['points_left'] + $point,
                );
            }
            if($rank > 0){
                $integralData['rank'] = $rank;
            }

            $integralRet = $model->table('integral')->where(array('customer_id' => $userInfo['custom_id']))->save($integralData);
        }

        if ($integralRet) {
            $pointLog = array(
                'user_id' => $this->user_id,
                'customer_id' => $userInfo['custom_id'],
                'site_id' => $site_id,
                'order_sn' => isset($extra['order_sn']) ? $extra['order_sn'] : '',
                'order_id' => isset($extra['order_id']) ? $extra['order_id'] : 0,
                'state' => $state,
                'point_type' => isset($extra['type']) ? $extra['type'] : 0,
                'extend_type' => isset($extra['extend_type']) ? $extra['extend_type'] : 0,
                'points' => $point,
                'remark' => $remark,
                'add_time' => Time::gmTime(),
                'integral_id' => $integral['id'],
            );
            $logRet = $model->table('integral_log')->add($pointLog);
            if ($logRet) {
                isset($extra['not_db_trans']) or $model->commit();
                $flag = true;
            }
        }

        if ($flag == false) {
            isset($extra['not_db_trans']) or $model->rollback();
            $this->setError(8);
            return false;
        }
        return true;
    }


    /**
     * 积分变化
     * @param int $site_id 站点ID
     * @param int $point 增减的积分(增加不加符号，减少在积分前面加'-')
     * @param string $remark 积分说明
     * @param int $state 状态 -1：删除（订单退货），0：正常，-2（积分过期），-3自主消费，-4客服消费,2:积分商品删除
     * @param bool $multiple 生日是否获取双倍积分
     * @param array $extra 扩展字段：暂时支持order_sn,order_id,user_id,type(积分类型 0:订单积分，1:签到积分，2:评论积分, 3:积分消费，4:转移积分),extend_type(2.评论积分[1.售前，2.售后，3.订单，4.品牌])
     * @return bool
     */
    public function variety($site_id, $point = 0, $remark = '', $state = 0, $multiple = false, $extra = array())
    {
        $extra['user_id'] = isset($extra['user_id']) ? $extra['user_id'] : 0;
        $this->user_id = empty($this->user_id) ? $extra['user_id'] : $this->user_id;
        $extRet = $this->extend($extra);
        if ($extRet == false) {//扩展处理
            return false;
        }
        if (empty($point)) {
            $point = $extRet;
        }
        if (empty($this->user_id)) {
            return false;
        }
        $printLog['user_id'] = $this->user_id;
        $printLog['site_id'] = $site_id;
        $printLog['order_sn'] = isset($extra['order_sn']) ? $extra['order_sn'] : '';
        $printLog['order_id'] = isset($extra['order_id']) ? $extra['order_id'] : 0;
        $printLog['state'] = $state;
        $printLog['point_type'] = isset($extra['type']) ? $extra['type'] : 0;
        $printLog['extend_type'] = isset($extra['extend_type']) ? $extra['extend_type'] : 0;
        $printLog['points'] = $point;
        if ($multiple) {
            $printLog['points'] = $point * $this->multiple();
        }
        $printLog['remark'] = $remark;
        $printLog['add_time'] = Time::gmTime();
        $pointLogModel = D('Home/UserPointLog');
        if ($logId = $pointLogModel->add($printLog)) {
            if ($userAccountId = $this->userPoint($point)) {
                $pointLogModel->where(array('log_id' => $logId))->setField('integralrecord_id', $userAccountId);
                return true;
            } else {
                $pointLogModel->where(array('log_id' => $logId))->delete();
                return false;
            }
        } else {
            return false;
        }
    }



    /**
     * 主/子帐号合并，子帐号归属到主帐号 - 子帐号积分将无效
     * @param int $primary_custom_id
     * @param array $child_mobile
     * @return bool
     */
    public function userMarge($primary_custom_id = 0, $child_mobile = array()) {
        $model = new Model('', null, 'USER_CENTER');  //初始化模型，绑定会员中心库
        if($primary_custom_id <= 0 || empty($child_mobile)){
            $this->error('20001','',true);  //参数异常
        }
        //检查主帐号是否存在，不存在则插入
        $primary_is_invalid = $model->table('integral')->where(array('customer_id'=>$primary_custom_id))->getField('is_invalid');  //注意：这个表的客户ID和客户编号是反过来的
        if(is_null($primary_is_invalid)){
            $rank_id = $model->table('user_rank')->order('min_points asc')->getField('rank_id');  //查询最低级的积分等级
            $integral_data = array(
                'customer_id'=>$primary_custom_id,
                'site_id'=>0,'is_invalid'=>0,'total_points'=>0,'pay_points'=>0,'expire_points'=>0,'points_left'=>0,
                'rank'=>$rank_id,
            );
            //如果没有存在积分记录，则插入一条0分的
            $primary_custom_integral_id = $model->table('integral')->add($integral_data);
            if($primary_custom_integral_id === false){
                $primary_custom_integral_id = $model->table('integral')->add($integral_data);  //失败重试
                if($primary_custom_integral_id === false){  //重试失败则报错
                    return -2;  //主帐号的积分无效或者不存在
                }
            }
        }else if($primary_is_invalid > 0){
            return -2;  //主帐号的积分无效或者不存在
        }

        $child_account_list = $model->table('users')->field('user_id,custom_id,custom_no,mobile')->where(array('mobile'=>array('IN',$child_mobile)))->select();
        if(empty($child_account_list)){
            return -7;  //子帐号的所有手机号码都未注册
        }
        //获取客户编号，如果没有就0
        $primary_custom_no = $model->table('user_customer')->where(array('custom_no'=>$primary_custom_id))->getField('custom_id');  //此表存的客户ID和编号是反的
        $primary_account = array(
            'custom_id'=>$primary_custom_id,
            'custom_no'=>($primary_custom_no > 0 ? $primary_custom_no : 0),
        );
        $model->startTrans(); //开启事务
        foreach($child_account_list as $key=>$child_account){
            $result = $this->userMargeOne($primary_account, $child_account, $model);
            if($result != 1){  //其中有一个操作失败了，就全部中断不保存
                $model->rollback();
                return $result;
            }
        }
        $model->commit();
        return 1;
    }

    /**
     * 处理单个手机号码被合并到某个客户ID
     * @param $primary_account
     * @param $child_account
     * @param $model
     * @return int
     */
    private function userMargeOne($primary_account, $child_account, $model){
        if($primary_account['custom_id'] ==  $child_account['custom_id']){
            return 1;  //帐号已经被合并，不可再次合并
        }
        //如果子帐号被合并过，获取真实的客户ID
        $child_integral_log = $model->table('integral_merge_log')
            ->where(array('user_id'=>$child_account['user_id']))
            ->order('add_time desc')->find();
        if(!empty($child_integral_log)){
            $child_account['custom_id'] = $child_integral_log['custom_id'];
            $child_account['custom_no'] = $child_integral_log['custom_no'];
        }
        //获取子帐号积分
        $child_integral = $model->table('integral')->field('is_invalid,points_left')
            ->where(array('customer_id'=>$child_account['custom_id']))
            ->find();
        if(empty($child_integral)){  //如果不存在积分记录，当作0积分处理
            $child_integral = array('is_invalid'=>0, 'points_left'=>0);  //初始化积分
        }

        //获取旧主帐号的合并记录，退还积分
        $return_integral = false;  //标记是否有从旧主帐号返还
        if($child_integral['is_invalid'] > 0){
            if(empty($child_integral_log)){
                return -4;  //旧主帐号积分出现异常
            }
            //检查旧主帐号合并后是否有消费过
            if($child_integral_log['points_left'] > 0){  //上次合并有合并过积分
                $log_id = $model->table('integral_log')
                    ->where("customer_id = '".$child_integral_log['to_custom_id']."' AND add_time >= '".$child_integral_log['add_time']."' AND (state = -2 OR state = -3 OR state = -4)")
                    ->getField('log_id');
                if($log_id > 0){  //有消费记录，子帐号积分清零
                    $child_integral['points_left'] = 0;
                }else{  //没消费过，积分退还
                    $remark = '[客户ID：'.$child_integral_log['custom_id'].',用户ID：'.$child_integral_log['user_id'].']子用户迁移，积分返还';
                    $extra = array('custom_id' => $child_integral_log['to_custom_id'],'type' => '4','model'=>$model,'not_db_trans'=>true,'can_be_debts'=>true);  //转移积分
                    $result = $this->newVariety(0, intval('-'.$child_integral_log['points_left']), $remark, 0, false, $extra);
                    if($result === false){
                        return -5;  //旧主帐号积分积分返还出错
                    }
                    $return_integral = true;  //积分已经返还给了子帐号
                    $child_integral['points_left'] = $child_integral_log['points_left'];
                }
            }
        }
        $result = true;  //转移积分的结果
        if($child_integral['points_left'] != 0){  //子帐号有积分，做转移处理
            $remark = '[客户ID：'.$child_account['custom_id'].',用户ID：'.$child_account['user_id'].']转移积分到[客户ID：'.$primary_account['custom_id'].']';
            $extra = array('custom_id' => $primary_account['custom_id'],'type' => '4','model'=>$model,'not_db_trans'=>true,'can_be_debts'=>true);  //转移积分
            $result = $this->newVariety(0, $child_integral['points_left'], $remark, 0, false, $extra);
        }
        if($result === false){  //转移积分失败
            if($return_integral == true){  //有积分返还，做回滚处理
                $remark = '[客户ID：'.$child_integral_log['custom_id'].',用户ID：'.$child_integral_log['user_id'].']子用户迁移出现异常，返还积分撤销';
                $extra = array('custom_id' => $child_integral_log['to_custom_id'], 'type' => '4','model'=>$model,'not_db_trans'=>true,'can_be_debts'=>true);  //转移积分
                $result = $this->newVariety(0, $child_integral['points_left'], $remark, 0, false, $extra);
                if($result === false){  //失败重试
                    $this->newVariety(0, $child_integral['points_left'], $remark, 0, false, $extra);
                }
            }
            return -3;
        }

        //把子帐号积分无效掉
        $model->table('integral')->where(array('customer_id'=>$child_account['custom_id']))->save(array('is_invalid'=>1));
        //把子帐号的客户ID更新为主帐号的，包含子帐号有存在子帐号的都改
        $model->table('users')->where("custom_id = '$child_account[custom_id]' OR user_id = '$child_account[user_id]'")
            ->save(array('custom_id'=>$primary_account['custom_id'],'custom_no'=>$primary_account['custom_no']));
        //更新客户编号表，把子帐号的客户ID更新为主帐号的，包含子帐号有存在子帐号的都改
        $model->table('user_customer')->where("phone = '$child_account[mobile]'")
            ->save(array('custom_id'=>$primary_account['custom_no'],'custom_no'=>$primary_account['custom_id']));

        //插入合并日记
        $data = array(
            'user_id'=>$child_account['user_id'],
            'custom_id'=>$child_account['custom_id'],
            'to_user_id'=>isset($primary_account['user_id']) ? $primary_account['user_id'] : 0,
            'to_custom_id'=>$primary_account['custom_id'],
            'points_left'=>$child_integral['points_left'],
            'add_time'=>Time::gmTime(),
        );
        $result = $model->table('integral_merge_log')->add($data);
        if($result === false){
            $model->table('integral_merge_log')->add($data);  //失败重试
        }
        return 1;
    }

    private function extend($extra, $log_table_name = 'UserPointLog')
    {
        //处理积分类型扩展
        if (!isset($extra['type']) || !isset($extra['extend_type'])) {
            return true;
        }
        $point = 0;
        switch ($extra['type']) {
            case 2:
                $point = $this->comment($extra['extend_type'], $log_table_name);
                break;
        }
        return $point;
    }

    private function comment($extend, $log_table_name = 'UserPointLog')
    {
        $configKey = $this->extendRule[$extend];
        $config = C($configKey);
        $where['user_id'] = $this->user_id;
        $where['type'] = 2;
        $where['extend_type'] = $extend;
        //处理每日次数
        if (isset($config['dayNum']) && !empty($config['dayNum'])) {
            $dayNum = $config['dayNum'];
            $where['_string'] = "DATE(FROM_UNIXTIME(add_time)) = DATE(NOW())";
            $total = D('Home/'.$log_table_name)->where($where)->count();
            if ($total >= $dayNum) {
                return false;
            }
        }
        //处理单月次数
        if (isset($config['monthNum']) && !empty($config['monthNum'])) {
            $monthNum = $config['monthNum'];
            $where['_string'] = "MONTH(FROM_UNIXTIME(add_time)) = MONTH(NOW())";
            $total = D('Home/'.$log_table_name)->where($where)->count();
            if ($total >= $monthNum) {
                return false;
            }
        }
        return (isset($config['integral']) && !empty($config['integral'])) ? intval($config['integral']) : 0;
    }

    /**
     * 生日获取双倍积分
     * @return int
     */
    private function multiple()
    {
        $multiple = 1;
        $userInfo = D('Home/users')->where(array('user_id' => $this->user_id))->find();
        $birthday = date('m-d', $userInfo['birthday']);
        $addTime = Time::gmTime();
        if ($birthday == date('m-d', $addTime)) {
            $multiple *= 2;
        }
        return $multiple;
    }


    /**
     * 会员积分
     * @param $point
     * @return bool
     */
    private function userPoint($point)
    {
        $accountModel = D('Home/UserAccount');
        $userAccount = $accountModel->where(array('user_id' => $this->user_id))->find();
        if (empty($userAccount) && $point < 0) {
            return false;
        }
        if(isset($userAccount['points_left']) && abs($point) > $userAccount['points_left']){
            return false;
        }
        $rank = 1;
        $accountData = array();
        $accountData['user_id'] = $this->user_id;
        if (empty($userAccount)) {
            $accountData['total_points'] = $point;
            $accountData['points_left'] = $point;
            $userAccountId = $result = $accountModel->add($accountData);
        } else {
            if ($point < 0) {
                $accountData['points_left'] = $userAccount['points_left'] + $point;
                $accountData['pay_points'] = $userAccount['pay_points'] - $point;
            } else {
                $accountData['total_points'] = $userAccount['total_points'] + $point;
                $accountData['points_left'] = $userAccount['points_left'] + $point;
            }
            $result = $accountModel->where(array('id' => $userAccount['id']))->save($accountData);
            $userAccountId = $userAccount['id'];
            $rank = $userAccount['rank'];
        }
        if ($result) {
            //会员等级变化写入等级变化表
            //根据积分来处理等级
            $level = $this->getLevel(); //更新后的等级
            if ($level > $rank) {
                $IsExist = D('Home/UserRankLog')->where(array('user_id' => $this->user_id, 'old_rank' => $rank, 'new_rank' => $level))->count();
                if (empty($IsExist)) {
                    $rankLog['user_id'] = $this->user_id;
                    $rankLog['old_rank'] = $rank;
                    $rankLog['new_rank'] = $level;
                    $rankLog['add_time'] = Time::gmTime();
                    D('Home/UserRankLog')->add($rankLog);
                }
            } elseif ($level < $rank) { //降级时将其对应的变级记录删了
                D('Home/UserRankLog')->where(array('user_id' => $this->user_id, 'old_rank' => $level))->delete();
            }

            //更新用户等级
            $accountModel->where(array('user_id' => $this->user_id))->save(array('rank' => $level));

            //更新登录用户信息到session
            //D('Home/Users')->setUserInfo($this->user_id);
            return $userAccountId;
        } else {
            return false;
        }
    }

    /**
     * 获取积分等级
     * @return int|mixed
     */
    private function getLevel()
    {
        $level = 0;
        $accountModel = D('Home/UserAccount');
        $points = $accountModel->where(array('user_id' => $this->user_id))->getField('total_points'); //获取会员总积分
        if (!empty($points)) {
            $where = array(
                'min_points' => array('ELT', $points),
                'max_points' => array('EGT', $points)
            );
            $level = D('Home/UserRank')->where($where)->getField('rank_id');
        }
        return $level;
    }

    /**
     * 设置错误
     * @param int $code 错误编号
     */
    private function setError($code = 0)
    {
        $class = explode('\\', __CLASS__);
        $err = L($class[0] . '_' . end($class));
        $this->err = $err[$code];
    }

    /**
     * 获取错误
     * @return mixed
     */
    public function getError()
    {
        return $this->err;
    }


}