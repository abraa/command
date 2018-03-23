<?php
/**
 * ====================================
 * 请求Deepstream
 * ====================================
 * Author: 9009123 (Lemonice)
 * Date: 2017-08-08 15:48
 * ====================================
 * File: DeepstreamRequest.class.php
 * ====================================
 */
namespace Common\Logic;
use Common\Extend\Deepstream;

class DeepstreamRequest extends Deepstream{
    public function __construct(){
        parent::__construct();
    }

    /**
     * 查询申请退款的订单 - 每次最多返回200条
     * @return array
     */
    public function getRefundList(){
        $this->setAddress('service.php.refundList');
        $this->setParam('refund_status', 0);
        $this->requestRpc();
        $this->close();
        $list = $this->getResponse();
        $data = array();
        if(isset($list->result->refund_list) && !empty($list->result->refund_list)){
            foreach($list->result->refund_list as $value){
                $data[] = array(
                    'refund_id'=>isset($value->refund_id) ? trim($value->refund_id) : '',  //退款申请id
                    'refund_fee'=>isset($value->money) ? round($value->money, 2) : 0.00,  //退款金额
                    'order_sn'=>isset($value->order_sn) ? trim($value->order_sn) : '',  //订单号
                    'mobile'=>isset($value->customer_mobile) ? trim($value->customer_mobile) : '',  //客户手机号
                    'is_confirmed'=>isset($value->is_confirmed) ? intval($value->is_confirmed) : 0,  //是否导购确认
                    'create_time'=>isset($value->create_date) ? strtotime($value->create_date) : '',  //申请退款时间
                    'unionid'=>isset($value->unionid) ? trim($value->unionid) : '',  //微信unionid
                    'refund_desc'=>isset($value->note) ? trim($value->note) : '格子柜故障了',
                );
            }
        }
        return $data;
    }

    /**
     * 设置申请退款的订单状态
     * @return string
     */
    public function setRefundResult($data = array()){
        $this->setAddress('service.php.refundResult');
        $this->setParams(array('refund_list'=>$data));
        $this->requestRpc();
        $this->close();
        $result = $this->getResponse();
        return isset($result->code)&&$result->code==0 ? true : false;
    }

    /**
     * 查询客户经理微信帐号
     * @param string $unionid
     * @return bool|string
     */
    public function getWechatManager($unionid = ''){
        if(empty($unionid)){
            $this->close();
            return false;
        }
        $this->setAddress('service.php.manager');
        $this->setParam('unionid', $unionid);  //微信unionid ID
        $result = $this->requestRpc();
        return isset($result->result->manager) ? $result->result->manager : '';
    }

    /**
     * 绑定客户经理微信帐号
     * @param string $unionid
     * @return bool|string
     */
    public function setWechatManager($unionid = '', $account = ''){
        if(empty($unionid) || empty($account)){
            $this->close();
            return false;
        }
        $this->setAddress('service.php.bindmanager');
        $this->setParam('unionid', $unionid);  //微信unionid ID
        $this->setParam('manager', $account);  //微信号
        $result = $this->requestRpc();
        return true;
    }

    /**
     * 获取商品数据
     * @param $m_code
     * @param $grid_code
     * @param bool $no_cache
     * @return array
     */
    public function getGoodInfo($m_code, $grid_code, $no_cache = false){
        $key = md5($m_code . $grid_code);
        if($no_cache === false){
            $data = S($key);
            if(!empty($data)){
                //return $data;
            }
        }
        $this->setAddress('service.php.goods');
        $this->setParam('m_code', $m_code);  //机器码
        $this->setParam('grid_code', $grid_code);  //格子号
        $goods = $this->requestRpc();

        if(isset($goods->code) && $goods->code == 0){  //找到商品
            $goods = $goods->result;  //商品详情
            $info = array(
                'machine_name'=>(isset($goods->m_name) ? $goods->m_name : ''),  //机器名称
                'machine_code'=>(isset($goods->m_code) ? $goods->m_code : ''),  //机器码
                'goods_sn'=>(isset($goods->g_code) ? $goods->g_code : ''),  //商品编号
                'goods_name'=>(isset($goods->g_name) ? $goods->g_name : ''),
                'goods_price'=>(isset($goods->g_price) ? $goods->g_price : 0),
                'buy_count'=>(isset($goods->sale_num) ? $goods->sale_num : 0),  //已购买数量
                'desc'=>(isset($goods->g_desc) ? $goods->g_desc : ''),  //产品简介
                'effect'=>(isset($goods->g_fnc) ? $goods->g_fnc : ''),  //核心功效
                'goods_thumb'=>(isset($goods->g_image) ? $goods->g_image : ''),  //商品缩略图
                'goods_picture'=>!is_array($goods->g_image) ? array($goods->g_image) : $goods->g_image,  //商品轮播图
                'goods_images'=>(array)$goods->g_images,  //商品对比图
                'is_packaged'=>$goods->is_packaged=='Y'||!empty($goods->g_detaillist) ? 1 : 0,  //是否为套装
            );
            if(!empty($goods->g_detaillist)){  //套装
                foreach($goods->g_detaillist as $key=>$value){
                    $info['package_goods'][] = array(
                        'goods_name'=>(isset($value->g_name) ?  $value->g_name : (isset($value['g_name']) ? $value['g_name'] : '')),
                    );
                }
            }
            S($key, $info, array('expire'=>1800));
            return $info;
        }
        return array();
    }

    /**
     * 通知打开售卖机的格子
     * @param $m_code
     * @param $grid_code
     * @param $pay_type
     * @param $pay_account
     * @param $money
     * @param $order_sn
     * @return string
     */
    public function openDoor($m_code, $grid_code, $pay_type,$pay_account, $money, $order_sn){
        $sucess_url = getDomain() . 'Home/ShowQrCode/payResult.shtml?lockCode='.$grid_code.'&amount='.$money.'&pay_result=success';
        $this->setAddress('service.php.order');
        $this->setParam('m_code', $m_code);  //专柜机器号
        $this->setParam('grid_code', $grid_code);  //格子编号
        $this->setParam('pay_type', $pay_type);  //支付方式:1支付宝，2微信
        $this->setParam('pay_time', time()*1000);  //付款时间，格式如1491234567833  (时间戳/ms)
        $this->setParam('pay_account', $pay_account);  //付款帐号
        $this->setParam('money', $money);  //付款金额
        $this->setParam('order_sn', $order_sn);  //订单号
        $this->setParam('sucess_url', $sucess_url);  //屏幕上支付结果跳转页面
        return $this->requestRpc();
    }

    /**
     * 通知打印照片
     * @param $m_code
     * @param $unionid
     * @param $imgUrl
     * @param $score
     * @return string
     */
    public function printPicture($m_code, $unionid, $imgUrl, $score){
        $this->setAddress('service.php.print');
        $this->setParam('m_code', $m_code);  //机器码
        $this->setParam('unionid', $unionid);  //微信UnionID
        $this->setParam('imgUrl', $imgUrl);  //合成后的图片地址
        $this->setParam('score', $score);  //颜值分数
        $this->rpc();
        $this->close();
        return $this->getResponse();
    }

    /**
     * 重复调用请求RPC
     * @return bool
     */
    private function requestRpc(){
        //最多重复调用3次
        for($i=0;$i<3;$i++){
            $result = $this->rpc();
            if($result === true){
                $this->close();
                return $this->getResponse();
            }
        }
        $this->close();
        return NULL;
    }

    /**
     *  spRPC5.	PHP提交会员绑定微信请求                           -- From Common/Logic/WechatEvent/signUp
     * @param array $unionid 用户微信unionid
     * @return bool
     */
    public function binding($uid,$unionid)
    {
        if(empty($unionid)||empty($uid)){
            $this->close();
            return false;
        }
        //传给后台参数
        $params = array(
            "vip_code"=>$uid,
            "unionid"=>$unionid,
        );
        //发送给后台
        $this->setAddress("service.php.binding");
        $this->setParams($params);
        $this->rpc();
        $this->close();
        $result =$this->getResponse();
        //返回通讯结果
        if($result->code===0){
            return $result->result;
        }else{
            return false;
        }
    }


    /**
     * spRPC6.	PHP提交微信关注请求                                           -- From Common/Logic/WechatEvent/subscribe
     * @param array $userinfo   微信用户数据
     * @return bool
     */
    public function follow($userinfo=array()){
        $this->setAddress("service.php.follow");
        $this->setParams($userinfo);
        $this->rpc();
        $this->close();
        $result =$this->getResponse();
        if($result->code===0){
            return true;
        }else{
            return false;
        }
    }

    /**
     * spRPC7.	PHP查看肌肤评测报告请求                                             -- From Bridge/report
     * @param $report_id
     * @param $unionid
     * @return bool|Json
     */
    public function getreport($report_id,$unionid){
        if(empty($unionid)||empty($report_id)){
            $this->close();
            return false;
        }
        $params =array(
            "report_id"=>$report_id,
            "unionid"=>$unionid
        );
        $this->setAddress("service.php.getreport");
        $this->setParams($params);
        $this->rpc();
        $this->close();
        $result =$this->getResponse();
        //返回通讯结果
        if($result->code===0){
            return $result->result;
        }else{
            return false;
        }
    }


    /**
     * spRPC8.    PHP注册会员请求                                          -- From User/doRegister
     * @param $m_code   机器码
     * @param $unionid
     * @param array $userinfo 用户注册信息 mobile|age|name|sex|note
     * @param int $level 1.普通会员
     * @return bool
     */
    public function addvip($m_code,$unionid,$userinfo=array(),$level = 1){
        if(empty($unionid)||empty($m_code)||empty($level)||empty($userinfo['mobile'])){
            $this->close();
            return false;
        }
        $params =array(
            'm_code'=>$m_code,
            "unionid"=>$unionid,
            'level'=>$level
        );
        $params = array_merge($userinfo,$params);
        $this->setAddress("service.php.addvip");
        $this->setParams($params);
        $this->rpc();
        $this->close();
        $result =$this->getResponse();
        //返回通讯结果
        if($result->code===0){
            return true;
        }else{
            return false;
        }
    }

    /**
     * RPC4.php提交切换屏显图片请求                                               --废弃
     * @param $m_code  机器码
     * @return bool
     */
    function machineToggle($m_code){
        $this->setAddress('machine.'.$m_code.'.toggle');
        $this->setParams(array("1"=>1));
        $this->rpc();
        $this->close();
        $result =$this->getResponse();
        //返回通讯结果
        if($result->code===0){
            return true;
        }else{
            return false;
        }
    }

    /**
     * spRPC11.	PHP提交退款请求                                               -- From User/subSuccess
     * @param $params     参考文档
     * @param int $type
     * @return bool
     */
    function refund($params  , $type = 1){
        if(empty($params['unionid'])||empty($params['grid_code'])||empty($params['m_code'])||empty($params['money'])||empty($params['order_sn'])||empty($params['mobile'])){
            $this->close();
            return false;
        }
        $params['type'] = $type;
        $this->setAddress('service.php.refund');
        $this->setParams($params);
        $this->rpc();
        $this->close();
        $result =$this->getResponse();
        //返回通讯结果
        return $result;

    }

    /**
     * spRPC14.	PHP判断用户是否已注册                                            -- From Common/Init/checkRegister
     * @param $unionid              微信 unionid
     * @return mixed
     */
    function registered($unionid){
        if(empty($unionid)){
            $this->close();
            return false;
        }
        $params = array("unionid"=>$unionid);
        $this->setAddress('service.php.registered');
        $this->setParams($params);
        $this->rpc();
        $this->close();
        $result =$this->getResponse();
        //返回通讯结果
       return $result;
    }

    /**
     * spRPC15.	PHP提交订单与手机号绑定请求                                      -- From Bridge/bindmobile
     * @param $mobile
     * @param $order_sn
     * @param string $unionid
     * @param string $nickname
     * @return bool
     */
    function bindmobile($mobile,$order_sn,$unionid="",$nickname=""){
        if(empty($mobile)||empty($order_sn)){
            $this->close();
            return false;
        }
        $params = compact('mobile','order_sn','unionid','nickname');
        $this->setAddress('service.php.bindmobile');
        $this->setParams($params);
        $this->rpc();
        $this->close();
        $result =$this->getResponse();
        //返回通讯结果
        return $result->code === 0 ? true : false;
    }
}