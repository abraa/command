<?php
/**
 * ====================================
 * 积分处理
 * ====================================
 * Author: 9004396
 * Date: 2017-06-06 13:33
 * ====================================
 * Project: new.m.chinaskin.cn
 * File: IntegralController.class.php
 * ====================================
 */
namespace Crontab\Controller;

use Common\Controller\CrontabController;
use Common\Extend\Integral;
use Common\Extend\Time;
use Think\Model;

class IntegralController extends CrontabController
{
    private $userRankList = array();  //用户等级列表
    private $model;
    private $limit = 4000;

    // 积分计算的积分系数，根据会员等级获取系数
    protected $pointModulusArr = array();

    public function __construct()
    {
        parent::__construct();
        if (is_null($this->model)) {
            $this->model = new Model('', null, 'USER_CENTER');
        }
        $config = load_config(CONF_PATH . '/integral.config.php');
        $this->pointModulusArr = $config['ORDER'];
    }


    /**
     * 初始化订单退货扣分
     */
    public function initUpdatePointLog()
    {
        $logId = $this->insertLog(__CLASS__ . '-' . __FUNCTION__, "开始执行");
        $num = 0;

        $order_list = $this->model->table('order_info')->field('user_id,mobile,tel,order_id,order_sn,add_time,site_id,user_rank_id')
            ->where(array('shipping_status' => 2, 'order_status' => 4, 'update_time' => array('GT', Time::gmTime() - 24 * 60 * 60)))->select();

        if (!empty($order_list)) {
            foreach ($order_list as $value) {
                $where = array();
                if (empty($value['user_id'])) {
                    $mobile = !empty($value['mobile']) ? $value['mobile'] : (!empty($value['tel']) ? $value['tel'] : 0);
                    $where['mobile'] = $mobile;
                } else {
                    $where['user_id'] = $value['user_id'];
                }

                $user = $this->model->table('users')->field('user_id,custom_id')->where($where)->find();
                $value['user_id'] = !empty($user['user_id']) ? $user['user_id'] : 0;
                if ($value['user_id'] <= 0) {  //最终还是找不到用户ID就放弃处理
                    continue;
                }

                //检查积分是否扣除
                $integral_log_id = $this->model->table('integral_log')->where(array('state' => -1, 'order_id' => $value['order_id']))->getField('log_id');
                if ($integral_log_id > 0) {
                    continue;
                }

                //检测该订单是否添加过积分
                $log_info = $this->model->table('integral_log')->field('log_id,points')->where(array('state' => 0, 'point_type' => 0, 'order_id' => $value['order_id']))->find();
                if (empty($log_info)) {
                    continue;
                }
                //获取等级详情
                $rank_info = $this->getUserRankList($value['user_rank_id']); //如果等级不存在则会返回默认V1级别

                $this->model->startTrans(); //开启事务
                $trans = false;
                $result = $this->updateUserPoint(intval('-' . $log_info['points']), $value['user_id'], $rank_info['rank_id'], $user['custom_id']);  //减积分

                if ($result !== false) {
                    $rs = $this->model->table('integral_log')->where(array('log_id' => $log_info['log_id']))->setField('deal_with', '-1');
                    if ($rs !== false) {
                        ++$num;
                        $pointLog = array(
                            'user_id' => $value['user_id'],
                            'site_id' => $value['site_id'],
                            'customer_id' => $user['custom_id'],
                            'order_sn' => $value['order_sn'],
                            'order_id' => $value['order_id'],
                            'state' => -1,
                            'points' => intval('-' . $log_info['points']),
                            'integral_id' => $result,
                            'remark' => '退货',
                            'add_time' => Time::gmTime(),
                        );
                        $result = $this->model->table('integral_log')->add($pointLog);  //插入积分日记
                        if ($result !== false) {
                            $trans = true;
                            $this->model->commit();
                        }
                    }
                }
                if ($trans === false) {
                    $this->model->rollback();
                }

            }
        }
        $this->updateLog($logId, '添加' . $num . "条退扣积分记录");
    }

    /**
     * 自动注册会员
     * @author lirunqing 2017-6-28
     * @param  string $mobile 手机号码
     * @param  string $orderId 订单id
     * @return [type]  [description]
     */
    public function createUser($mobile, $orderId = '')
    {

        if (empty($mobile)) {
            return false;
        }
        $where['mobile'] = $mobile;
        // 判断该手机号码是否注册过
        $user = $this->model->table('users')->where($where)->find();
        $custom = $this->model->table('user_customer')->field('custom_no,custom_id')->where(array('phone' => $mobile))->find();

        //根据手机号码获取客户资料
        $custom_id = !empty($custom['custom_no']) ? $custom['custom_no'] : 0;
        $custom_no = !empty($custom['custom_id']) ? $custom['custom_id'] : 0;

        if (!empty($user)) {
            $userData = array();
            if ($user['custom_id'] != $custom_id) {
                $userData['custom_id'] = $custom_id;
            }
            if ($user['custom_no'] != $custom_no) {
                $userData['custom_no'] = $custom_no;
            }
            if (!empty($userData)) { //客户资料表和会员表客户编号或ID不一致则重新绑定
                $this->model->table('users')->where($where)->save($userData);
            }
            return $user['user_id'];
        } else {
            $password = md5(md5('cj123456')); //自动注册初始密码：cj123456
            $userData = array(
                'mobile' => $mobile,
                'password' => $password,
                'custom_id' => $custom_id,
                'custom_no' => $custom_no,
                'user_num' => '',
                'paypwd' => '',
                'paytype' => '',
                'state' => 1,
                'push_state' => 0,
                'auto_reg_time' => Time::gmTime(),
            );
            $userId = $this->model->table('users')->add($userData);

            if (empty($userId)) {
                $this->insertLog(__CLASS__ . '-' . __FUNCTION__, "订单ID{$orderId}新增会员失败");
                return false;
            }
        }

        if (!empty($orderId)) {
            $update['user_id'] = $userId;
            $updateWhere['order_id'] = $orderId;
            $this->model->table('order_info')->where(array('id' => $orderId))->setField('user_id', $userId);
        }

        return $userId;
    }

    /**
     * 增积分
     * @param int $site_id
     * @param int $point
     * @param int $state
     * @param string $remark
     * @param array $extra
     * @return bool
     */
    public function addPoints($site_id = 0, $point = 0, $state = 0, $remark = '', $extra = array())
    {

        $userId = (isset($extra['user_id']) && $extra['user_id'] > 0) ? $extra['user_id'] : 0;
        $rank = isset($extra['rank']) ? abs(intval($extra['rank'])) : 0;
        if (empty($userId)) {
            return false;
        }
        $userInfo = $this->model->table('users')->alias('u')
            ->join('__USER_INFO__ AS ui ON u.user_id=ui.user_id', 'LEFT')
            ->field('ui.birthday,u.custom_id')
            ->where(array('u.user_id' => $userId))
            ->find();
        if (empty($userInfo)) {
            return false;
        }
        if (empty($userInfo['custom_id'])) {  //客户编号未绑定，无法获取积分
            return false;
        }

        //检测积分记录是否存在
        $integral = $this->model->table('integral')->where(array('customer_id' => $userInfo['custom_id']))->find();

        if (empty($point)) {
            //积分0是变更等级
            if (!empty($integral) && !empty($rank)) {
                $this->model->table('integral')->where(array('id' => $integral['id']))->setField('rank', $rank);
            }
            return false;
        }

        //检查是否无效的积分
        if (isset($integral['is_invalid']) && $integral['is_invalid'] == 1) {
            return false;
        }


        $this->model->startTrans(); //开启事务
        $flag = false;

        if (empty($integral)) { //积分账户不存在
            $integralData = array(
                'customer_id' => $userInfo['custom_id'],
                'rank' => $rank,
                'total_points' => $point,
                'points_left' => $point,
            );
            $integral['id'] = $integralRet = $this->model->table('integral')->add($integralData);
        } else {

            $integralData = array(
                'total_points' => $integral['total_points'] + $point,
                'points_left' => $integral['points_left'] + $point,
            );
            if ($rank > 0) {
                $integralData['rank'] = $rank;
            }

            $integralRet = $this->model->table('integral')->where(array('customer_id' => $userInfo['custom_id']))->save($integralData);
        }

        if (empty($integralRet)) {
            return false;
        }

        $pointLog = array(
            'user_id' => $userId,
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
        $logRet = $this->model->table('integral_log')->add($pointLog);

        if ($logRet) {
            $this->model->commit();
            $flag = true;
        }

        if ($flag == false) {
            $this->model->rollback();
            return false;
        }

        return true;

    }

    /**
     * 根据订单处理用户积分
     * @return bool
     */
    public function processUserIntegralByOrder()
    {

        $logId = $this->insertLog(__CLASS__ . '-' . __FUNCTION__, '开始执行');

        // 获取状态已经收货的订单并且没有计算积分的订单
        $orderInfoFields = 'id,site_id,order_sn,order_id,user_id,add_time,mobile,tel,user_rank_id,goods_amount,shipping_fee,discount,bonus,integral_money';
        $orderInfoWhere['shipping_status'] = 2;
        $orderInfoWhere['point_calculate'] = 0;
        $orderInfoWhere['update_time'] = array('gt', (Time::gmTime() - 24 * 60 * 60));
        $orderInfoLimit = 300;
        $orderInfoList = $this->model->table('order_info')->field($orderInfoFields)->where($orderInfoWhere)->limit($orderInfoLimit)->select();
        $total = count($orderInfoList);
        $num = 0;

        if ($total == 0) {
            $this->updateLog($logId, '需处理' . $total . '条记录，已处理' . $num . '条记录');
            exit;
        }
        $orderIds = array();
        foreach ($orderInfoList as $key => $value) {

            //该订单已经添加过积分
            $integral_log_points = $this->model->table('integral_log')->where(array('state' => 0, 'point_type' => 0, 'order_id' => $value['order_id']))->getField('points');


            if (!empty($integral_log_points)) {
                continue;
            }

            // 根据会员等级获取积分系数
            $rank_info = $this->getUserRankList($value['user_rank_id']);
            $rank = $rank_info['rank_name'];
            $rank_id = $rank_info['rank_id'];
            $pointModulus = $this->pointModulusArr[$rank];

            // 积分计算规则 订单获得的积分=（商品金额-折扣金额-优惠券）*积分系数; 积分有小数获取向下取整
            $points = bcmul(($value['goods_amount'] - $value['discount'] - $value['bonus']), $pointModulus);
            $remark = '交易获得';

            // 游客订单需要帮用户注册会员
            if (empty($value['user_id'])) {
                $mobile = !empty($value['mobile']) ? $value['mobile'] : $value['tel'];
                $userId = $this->createUser($mobile, $value['id']);
                $value['user_id'] = $userId;
                $remark = '交易获得';
            }


            if (empty($value['user_id'])) {
                continue;
            }

            $state = 0;
            $extra = array(
                'user_id' => $value['user_id'],
                'rank' => $rank_id,
                'order_sn' => $value['order_sn'],
                'order_id' => $value['order_id']
            );
            $res = $this->addPoints($value['site_id'], $points, $state, $remark, $extra);
            if (!empty($res) || $points == 0) {
                $orderIds[] = $value['id'];
                $num++;
            }
        }
        if (!empty($orderIds)) {
            $this->model->table('order_info')->where(array('id' => array('in', $orderIds)))->setField('point_calculate', 1);
        }

        $this->updateLog($logId, '需处理' . $total . '条记录，已处理' . $num . '条记录');

        return true;
    }

    /**
     * 处理过期积分
     */
    public function expiredPoint()
    {
        $limit = I('param.limit', 0, 'intval');
        $limit = empty($limit) ? $this->limit : $limit;
        $logId = $this->insertLog(__CLASS__ . '-' . __FUNCTION__, '开始执行');
        $where = array(
            'add_time' => array(
                array('GT', Time::localStrtotime("-1 year -3 days")),
                array('LT', strtotime("-1 year"))
            ),
            'deal_with' => 0,
            'state' => 0
        );
        $expiredPointData = $this->model->table('integral_log')
            ->field("log_id,integral_id,customer_id,SUM(points) AS expire_point")
            ->where($where)
            ->group('customer_id')
            ->limit($limit)
            ->select();

        $total = count($expiredPointData);
        $num = 0;
        if ($total == 0) {
            $this->updateLog($logId, '需处理' . $total . '条记录，已处理' . $num . '条记录');
            exit;
        }
        foreach ($expiredPointData as $expiredPoint) {
            //获取积分记录
            $integralInfo = $this->model->table('integral')->field('points_left,expire_points')->find($expiredPoint['integral_id']);
            if (empty($integralInfo)) {
                $this->insertLog(__CLASS__ . '-' . __FUNCTION__, '客户ID为 ' . $expiredPoint['customer_id'] . " 积分日志异常或未创建");
                continue;
            }

            //获取消费记录
            $consume = $this->model->table('integral_log')
                ->field('points,log_id,deal_with')
                ->where(array(
                    'customer_id' => $expiredPoint['customer_id'],
                    'state' => array('IN', array(-3, -4)),
                    'deal_with' => array('neq', array('exp', 'ABS(points)')),
                ))->order('add_time ASC')
                ->select();

            $expirePoint = $expiredPoint['expire_point']; //需要处理的过期积分
            $deal_with = 0;  //消费扣减剩余积分
            $integralLogIds = array(); //已处理日志

            if ($consume) {
                foreach ($consume as $item) {
                    $expirePoint -= $item['deal_with'] == 0 ? abs($item['points']) : $item['deal_with'];
                    $integralLogIds[] = $item['log_id'];
                    if ($expirePoint <= 0) {
                        $deal_with = $expirePoint;
                        break;
                    }
                }
            }
            $this->model->startTrans(); //开启事务
            $flag = false;
            if ($expirePoint < 0) {  //消费积分多余过期积分
                $integralLogId = array_pop($integralLogIds);
                $result = $this->model->table('integral_log')->where(array('log_id' => $integralLogId))->setField('deal_with', abs($deal_with));  //更新多余积分，待下次处理使用
                if ($result) {
                    $flag = true;
                }
            } elseif ($expirePoint > 0) { //过期积分多余消费积分
                $integralData = array(
                    'id' => $expiredPoint['integral_id'],
                    'points_left' => $integralInfo['points_left'] - $expirePoint,
                    'expire_points' => $integralInfo['expire_points'] + $expirePoint,
                );
                $integralRet = $this->model->table('integral')->save($integralData);
                if ($integralRet) {
                    $logData = array(
                        'user_id' => 0,
                        'customer_id' => $expiredPoint['customer_id'],
                        'site_id' => 0,
                        'order_sn' => '',
                        'order_id' => 0,
                        'state' => -2,
                        'points' => $expirePoint,
                        'remark' => '过期积分',
                        'add_time' => time(),
                        'integral_id' => $expiredPoint['integral_id'],
                        'deal_with' => 0,
                        'point_type' => 0,
                        'extend_type' => 0,
                    );
                    $result = $this->model->table('integral_log')->add($logData);
                    if ($result) {
                        $flag = true;
                    }
                }
            }

            if ($integralLogIds) {  //有消费记录处理
                $data['deal_with'] = array('exp', 'ABS(points)');
                $result = $this->model->table('integral_log')->where(array('log_id' => array('IN', $integralLogIds)))->save($data);
                if ($result) {
                    $ret = $this->model->table('integral_log')->where(array_merge($where, array('customer_id' => $expiredPoint['customer_id'])))->setField('deal_with', -1);
                    $flag = $ret ? true : false;
                } else {
                    $flag = false;
                }
            }

            if (empty($consume)) {//无消费记录处理
                $ret = $this->model->table('integral_log')->where(array_merge($where, array('customer_id' => $expiredPoint['customer_id'])))->setField('deal_with', -1);
                $flag = $ret ? true : false;
            }

            if ($flag) {
                $num++;
                $this->model->commit();
            } else {
                $this->insertLog(__CLASS__ . '-' . __FUNCTION__, '客户ID为 ' . $expiredPoint['customer_id'] . " 异常，" . $this->model->getError());
                $this->model->rollback();
            }
        }
        $this->updateLog($logId, '需处理' . $total . '条记录，已处理' . $num . '条记录');
    }

    /**
     * 获取积分等级列表，并且把返回的key指向对应的字段值
     * @param string $rank_name
     * @return array
     */
    private function getUserRankList($rank_name = '')
    {
        if (empty($this->user_rank_list)) {
            $list = $this->model->table('user_rank')->field('rank_id,rank_name')->select();
            $this->userRankList = array();
            foreach ($list as $value) {
                $this->userRankList[$value['rank_name']] = $value;
            }
        }
        if (empty($rank_name)) {
            return $this->userRankList;
        }
        return isset($this->userRankList[$rank_name]) ? $this->userRankList[$rank_name] : $this->userRankList['V1'];
    }

    /**
     * 加减总积分、可用积分、等级, 不做日记
     * @param int $points 加减的积分
     * @param int $userId 用户会员ID
     * @param int $rank_id 等级ID，如果不修改则传0
     * @param int $custom_id 客户ID，没传则用user_id获取
     * @return bool
     */
    private function updateUserPoint($points, $userId, $rank_id = 0, $custom_id = 0)
    {
        if ($custom_id <= 0) {
            $custom_id = $this->model->table('users')->where(array('user_id' => $userId))->getField('custom_id');
            if (is_null($custom_id) || $custom_id <= 0) {
                return false;
            }
        }
        $integral = $this->model->table('integral')->field('id,total_points,points_left')->where(array('customer_id' => $custom_id, 'is_invalid' => 0))->find();
        if (empty($integral)) {
            return false;
        } else {

            $data = array(
                'total_points' => $integral['total_points'] + $points,
                'points_left' => $integral['points_left'] + $points,
            );
            if ($rank_id > 0) {
                $data['rank'] = $rank_id;
            }
            $result = $this->model->table('integral')->where(array('id' => $integral['id']))->save($data);
            if ($result === false) {
                return false;
            }
        }
        return $integral['id'];
    }
}