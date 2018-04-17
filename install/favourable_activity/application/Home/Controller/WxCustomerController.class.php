<?php
/**
 * ====================================
 * 公众号引流客户资料功能 控制器
 * ====================================
 * Author: 9006758
 * Date: 2017-04-21
 * ====================================
 * File: WxCustomerController.class.php
 * ====================================
 */

namespace Home\Controller;

use Common\Controller\InitController;
use Common\Extend\PhxCrypt;
use Common\Extend\Send;

class WxCustomerController extends InitController
{
    private $dbModel = NULL;  //储存地址数据表对象
    private $cache_time = 1800; //缓存时间
    private $act_config = array();

    public function __construct()
    {
        parent::__construct();
        $this->dbModel = D('WxCustomer');
        $this->act_config = load_config(CONF_PATH . 'wx_customer.php');
    }

    //获取活动商品数据
    public function index()
    {
        $act_no = I('request.act_no', 0, 'intval'); //活动期号
        $goods_num_cache = S($act_no); //商品的剩余可领取量集合
        $act_info = $this->act_config[$act_no];
        $goods_num_cache_data = array();
        //开启并且在活动时间范围内


        $now_time = time();
        //判断活动是否关闭，是否开始以及是否已经结束
        $config = $this->act_config;
        if (!isset($config[$act_no]) || empty($config[$act_no]['is_close'])) {
            $this->error('活动未开始或已经结束');
        }
        if (isset($config[$act_no]['start_time']) && isset($config[$act_no]['end_time'])) {
            $start_time = strtotime($config[$act_no]['start_time']);
            $end_time = strtotime($config[$act_no]['end_time']);
            if (!($start_time <= $now_time && $now_time < $end_time)) {
                $this->error('活动未开始或已经结束');
            }

        }

        $goods = $act_info['goods'];

        //将缓存中的可领取量替换配置中的商品数量
        foreach ($goods as $key => $val) {
            if (!empty($goods_num_cache[$val['goods_sn']])) {
                $goods_num_cache_data[$val['goods_sn']] = $goods_num_cache[$val['goods_sn']];
            } else {
                //没有缓存，则去读取数据库的记录，做差额
                $count = $this->dbModel->where(array('act_no' => $act_no, 'goods_sn' => $val['goods_sn']))->count();
                $goods_num_cache_data[$val['goods_sn']] = $val['goods_num'] - $count;
            }
            $goods[$key]['surplus'] = $goods_num_cache_data[$val['goods_sn']];
        }
        S($act_no, $goods_num_cache_data, $this->cache_time);
        if(I('request.delc', 0, 'intval')){
            S($act_no, null);
        }

        $this->ajaxReturn($goods);
    }

    //保存客户资料
    public function save()
    {
        $user_name = I('request.name', '', 'trim');    //收货人
        $mobile = I('request.mobile', '', 'trim');    //手机号
        $code = I('request.code', '', 'trim');    //验证码
        $provinces = I('request.provinces', 0, 'intval');   //省份
        $city = I('request.city', 0, 'intval');   //城市
        $district = I('request.district', 0, 'intval');//区、县
        $address = I('request.address', '', 'trim'); //详细地址
        $act_no = I('request.act_no', 0, 'intval'); // 活动期号
        $goods_sn = I('request.goods_sn', '', 'trim'); // 商品货号

        if (!$this->openId) {
            $this->error('网络数据连接失败，稍后重试！');
        }
        if (!$goods_sn) {
            $this->error('商品货号丢失');
        }
        if (!$user_name) {
            $this->error('请输入收货人');
        }
        if (!isMobile($mobile)) {
            $this->error('请输入正确的手机号');
        }
        if (!$provinces) {
            $this->error('请选择省份');
        }
        if (!$city) {
            $this->error('请选择城市');
        }
        if (!$district) {
            $this->error('请选择县或市区');
        }
        if (!$address) {
            $this->error('请输入具体地址信息');
        }
        $now_time = time();

        //判断活动是否关闭，是否开始以及是否已经结束
        $config = $this->act_config;
        if (!isset($config[$act_no]) || empty($config[$act_no]['is_close'])) {
            $this->error('活动未开始或已经结束');
        }
        if (isset($config[$act_no]['start_time']) && isset($config[$act_no]['end_time'])) {
            $start_time = strtotime($config[$act_no]['start_time']);
            $end_time = strtotime($config[$act_no]['end_time']);
            if (!($start_time <= $now_time && $now_time < $end_time)) {
                $this->error('活动未开始或已经结束');
            }

        }

        //判断奖品数量,先读取缓存
        $goods_num_cache = S($act_no);
        if ($goods_num_cache && $goods_num_cache[$goods_sn] <= 0) {
            $this->error('商品已经被领完');
        } else {
            $count = $this->dbModel->where(array('act_no' => $act_no, 'goods_sn' => $goods_sn))->count();
            if ($count >= $config[$act_no]['goods'][$goods_sn]['goods_num']) {
                $this->error('商品已经被领完');
            }
        }

        //判断手机号是否已经领取过商品，一个openid一个活动只能领取一次商品
        $phx_mobile = PhxCrypt::phxEncrypt($mobile);
        $where = array(
            'act_no' => $act_no,
            'openid' => array(array('eq', $this->openId), array('neq', ''), 'and'),
        );
        $is_got = $this->dbModel->where($where)->count();
        if ($is_got) {
            $this->error('亲亲你已经领过一次了，下期活动再来吧！');
        }

        //校验手机验证码
        if (!Send::checkMobileCode($code, 0, $mobile)) {
            $this->error('验证码不正确');
        }

        //缓存中的商品数量先 -1
        $goods_num_cache[$goods_sn]--;
        S($act_no, $goods_num_cache, $this->cache_time);
        $this->dbModel->create(array(
            'user_name' => $user_name,
            'mobile' => $phx_mobile,
            'provinces' => $provinces,
            'city' => $city,
            'district' => $district,
            'address' => $address,
            'add_time' => $now_time,
            'openid' => $this->openId,
            'act_no' => $act_no,
            'goods_sn' => $goods_sn,
        ));
        $res = $this->dbModel->add();
        if ($res) {
            $this->success();
        } else {
            //执行失败，则将缓存的商品数量还原 +1
            $goods_num_cache[$goods_sn]++;
            S($act_no, $goods_num_cache, $this->cache_time);
            $this->error('领取失败，请联系客服');
        }
    }

    /**
     * 最新的领取名单
     */
    public function newUser(){
        $act_no = I('request.act_no', 0, 'intval'); //活动期号
        if(empty($act_no)){
            $this->error('请指定期号');
        }
        $act_info = $this->act_config[$act_no];
        if(isset($act_info['is_close']) && $act_info['is_close'] == 0){
            $this->error('活动已关闭');
        }

        $now_time = time();
        if (isset($act_info['start_time']) && isset($act_info['end_time'])) {
            $start_time = strtotime($act_info['start_time']);
            $end_time = strtotime($act_info['end_time']);
            if (!($start_time <= $now_time && $now_time < $end_time)) {
                $this->error('活动未开始或已经结束');
            }
        }

        $goods = array();
        $goods_name = array();
        foreach($act_info['goods'] as $v){
            if(!empty($v['goods_sn'])){
                $goods[] = $v['goods_sn'];
            }
                $goods_name[$v['goods_sn']] = $v['goods_name'];
        }
        if(empty($goods)){
            $this->error('无商品信息');
        }
        $new_user = $this->dbModel->field('user_name,goods_sn')->where(array('goods_sn'=>array('in',$goods)))->order(' id desc')->limit(15)->select();
        if(empty($new_user)){
            $new_user = $this->getDefaut(15);
        }else{
            foreach($new_user as $k=>$v){
                if(!empty($v['user_name'])){
                    $new_user[$k]['user_name'] = mb_substr($v['user_name'],0,3).'**'.mb_substr($v['user_name'],6,3);
                }
                $new_user[$k]['goods_name'] = $goods_name[$v['goods_sn']];
            }
            $new_user_count = count($new_user);
            if($new_user_count < 15){
                $default = $this->getDefaut(15 - $new_user_count);
                $new_user = array_merge($new_user, $default);
            }
        }
        $this->success($new_user);
    }


    //发送短信验证码
    public function sendSms()
    {
        $mobile = I('request.mobile', '', 'trim');
        if (!isMobile($mobile)) {
            $this->error('请输入正确的手机号');
        }
        $code = Send::setMobileCode(300, $mobile);
        $msg = '亲爱的用户，您本次操作的验证码为' . $code;
        if (!empty($mobile) && !empty($code)) {
            $result = Send::send_sms($mobile, 0, get_client_ip(), $msg, 'code');
            if ($result['error'] == 'M000000') {
                $this->success();
            } elseif ($result['error'] == 'M000006') {
                $this->error('发送失败');
            } else {
                $this->error($result['message']);
            }
        }
    }

    /**
     * 免费领取默认数据
     */
    protected function getDefaut($return_num = 0){
        $default = array(
            array('user_name' => '王**敏','goods_name' => '去黑头会员专享四件套礼盒'),
            array('user_name' => '李**清','goods_name' => '酵素光感肌密MINI套'),
            array('user_name' => '戴**玲','goods_name' => '医用愈肤生物膜面膜'),
            array('user_name' => '汪**洋','goods_name' => '医用愈肤生物膜面膜'),
            array('user_name' => '吴**妹','goods_name' => '焕颜遮瑕会员专享四件套礼盒'),
            array('user_name' => '张**瑜','goods_name' => '医用愈肤生物膜面膜'),
            array('user_name' => '李**铭','goods_name' => '去黑头会员专享四件套礼盒'),
            array('user_name' => '夏**丽','goods_name' => '亮肤水润补水面膜'),
            array('user_name' => '苏**曦','goods_name' => '去黑头会员专享四件套礼盒'),
            array('user_name' => '汪**朝','goods_name' => '酵素光感肌密MINI套'),
            array('user_name' => '郑**晓','goods_name' => '酵素光感肌密MINI套'),
            array('user_name' => '张**晴','goods_name' => '焕颜遮瑕会员专享四件套礼盒'),
            array('user_name' => '黄**微','goods_name' => '去黑头会员专享四件套礼盒'),
            array('user_name' => '钟**苘','goods_name' => '焕颜遮瑕会员专享四件套礼盒'),
            array('user_name' => '袁**城','goods_name' => '酵素光感肌密MINI套'),
            array('user_name' => '郑**花','goods_name' => '亮肤水润补水面膜'),
        );
        krsort($default);
        if(!$return_num){
            $return_num = count($default);
        }
        return array_slice($default, 0, $return_num);
    }


}