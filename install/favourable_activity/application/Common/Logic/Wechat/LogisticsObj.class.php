<?php
/**
 * ====================================
 * 微信公众号物流相关消息处理
 * ====================================
 * Author: 9009123 (Lemonice)
 * Date: 2017-09-01 13:44
 * ====================================
 * File: Logistics.class.php
 * ====================================
 */
namespace Common\Logic\Wechat;
use Common\Extend\Wechat;
use Common\Extend\Curl;
use Common\Extend\Time;
use Common\Logic\Wechat\WechatData;

class LogisticsObj extends WechatData{
    private $Logistics = NULL;
    public function __construct(){
        $this->Logistics = new \Common\Extend\Logistics();
    }
    /**
     * 订单状态
     * @var array
     */
    private $order_status = array(
        '0' => '未确认',
        '1' => '已确认',
        '2' => '已取消',
        '3' => '无效',
        '4' => '退货',
        '5' => '异常',
        '6' => '丢失',
        '99' => '假删除标记',
    );
    /**
     * 物流状态
     * @var array
     */
    private $shipping_status = array(
        '0' => '未发货',
        '1' => '已发货',
        '2' => '已收货',
        '3' => '配货中',
        '4' => '已打单',
        '5' => '配货审核中',
        '6' => '配货审核退回',
        '7' => '已打捡货单',
        '8' => '已打包',
        '9' => '压单',
        '20' => '仓库返回异常',
        '30' => '退货已签收',
    );

    /**
     * 物流推送
     * @param string $invoice_no
     */
    public function send($invoice_no = '') {
        $data = $this->getDatas();

        $mobile = D('BindUser')->where("openid = '".$data['FromUserName']."'")->getField('mobile');
        if (!$mobile) {
            $content = "您好，请先绑定您的手机号码\n<a href='" . siteUrl() . "#/check-code?openid={$data['FromUserName']}'>（手机认证）</a>";
            Wechat::serviceText($content);
            $this->sayNothing();
        }

        $info = array(
            'FromUserName'=>$data['FromUserName'],
            'Content'=>$data['Content'],
            'invoice_no'=>$invoice_no ? $invoice_no : 0,
            //为了兼容多个公众号，传appid指定推送信息
            'appid'=>Wechat::$app_id,
            'appsecret'=>Wechat::$app_secret,
        );
        Curl::$timeOut = 1;  //设置为一秒超时，为的是不堵塞微信，不然微信超过5秒会报错
        Curl::$headers = array(
            'Content-Type: application/x-www-form-urlencoded;charset=UTF-8'
        );
        Curl::post(siteUrl() . 'Wechat/sendLogistics.json', $info);
        $this->sayNothing();
    }

    /**
     * 物流微信推送
     * @params info 微信详情
     * @params invoice_no 快递单号码
     */
    public function sendWechatLogistics() {
        $orderList = $this->getOrder();  //获取符合条件的所有订单
        $content = "订单跟踪通知\n十分抱歉！翻遍整个系统也找不到您的订单信息，请您再次核对下您的收货手机号码是否正确";
        if (!empty($orderList)) {
            foreach ($orderList as $k => $v) {
                //发送订单信息
                $this->getOrderContent($v);

                //发送物流信息
                $content = $this->getInvoiceContent($v['shipping_name'], $v['order_sn'], $v['invoice_no']);

                //拆分超长物流信息,并且发送到微信
                $this->subContent($content);

                //插入或更新物流信息
                $this->invoiceLog(array(
                    'mobile'=>empty($mobile) ? empty($info['Content']) ? '' : $info['Content'] : $mobile,
                    'order_id'=>$v['order_sn'],
                    'invoice_no'=>$v['invoice_no'],
                    'add_time'=>Time::gmTime(),
                    'msg'=>$content,
                    'logistics_platform'=>$v['shipping_name'],
                ));
            }
        } else {
            Wechat::serviceText($content);
        }
        $this->log($content);  //记录此次查询记录
    }

    /**
     * 提示如何查询物流信息
     */
    public function help(){
        Wechat::serviceText('发送您手机号码到公众号，即可查询您的订单物流信息。');
    }

    /**
     * 根据物流名称获取对应扩展类的方法名
     * @param string $shipping_name
     * @return string
     */
    private function getMethodName($shipping_name = ''){
        $function_name = '';
        switch ($shipping_name) {
            case 'ems快递':
            case 'EMS特快专递':
                $function_name = 'ems';
                break;
            case '京东瓷肌快递':
                $function_name = 'jingDong';
                break;
            case '思迈':
                $function_name = 'sm';
                break;
            case '顺丰速运':
                $function_name = 'sf';
                break;
            case '韵达快运':
                $function_name = 'yunDa';
                break;
            case '申通快递':
                $function_name = 'shengTong';
                break;
        }
        return $function_name;
    }

    /**
     * 插入或更新物流信息
     * @param $log
     */
    private function invoiceLog($log){
        $userModel = M('WxLog', null, 'USER_CENTER');
        $log_exist = $userModel->where(array('invoice_no' => $log['invoice_no']))->find();
        if (empty($log_exist)) {
            $userModel->data($log)->add();
        } else {
            $userModel->where(array('invoice_no' => $log['invoice_no']))->save($log);
        }
    }

    /**
     * 拆分物流信息内容为多条发送，微信每次只支持2048个字符，并且发送微信
     */
    private function subContent($content = ''){
        $len_int = ceil(strlen($content) / 1950);
        if ($len_int > 1) {
            $content_arr = explode("\n\n", $content);
            $content_arr_ceil = ceil(count($content_arr) / $len_int);
            $content_arr_2 = array_chunk($content_arr, $content_arr_ceil);
            foreach ($content_arr_2 as $key => $val) {
                Wechat::serviceText(trim(implode("\n\n", $val)));
            }
        } else {
             Wechat::serviceText($content);
        }
    }

    /**
     * 查询物流信息
     * @param string $shipping_name
     * @param string $order_sn
     * @param string $invoice_no
     * @return mixed|string
     */
    private function getInvoiceContent($shipping_name = '', $order_sn = '', $invoice_no = ''){
        $this->Logistics->setConfig('order_sn', $order_sn);
        $this->Logistics->setConfig('invoice_no', $invoice_no);
        $this->Logistics->setConfig('shipping_name', $shipping_name);
        $functio_name = $this->getMethodName($shipping_name);
        $result = $functio_name != '' ? $this->Logistics->$functio_name() : false;  //请求发送
        if ($result == false) {  //如果都获取不到，试着去图灵找
            if (in_array($shipping_name, array('EMS特快专递', 'ems快递'))) {
                $this->Logistics->setConfig('shipping_name', 'EMS快递');
            }
            $this->Logistics->tuRing();
        }
        $content = $this->Logistics->getResponse();  //获取返回值
        return $content;
    }

    /**
     * 组装订单信息，并且发送微信
     * @param array $order
     */
    private function getOrderContent($order = array()){
        $order_content = "订单编号：" . $order['order_sn'] . "\n";
        //2016-10-18 16:00之后的订单总价都用  money_paid+order_amount
        if ($order['update_time'] >= Time::localStrtotime('2016-10-18 16:00:00') || $order['integral_money'] > 0) {
            $money = $order['order_amount'] + $order['money_paid'];
        } else {
            $money = $order['goods_amount'] - $order['bonus'] - $order['integral_money'] + $order['shipping_fee'] - $order['discount'] - $order['payment_discount'];
        }
        $order_content .= "订单金额：" . $money . "元\n";
        //显示订单和物流状态
        if (isset($v['order_status']) && isset($this->order_status[$v['order_status']])) {
            $order_content .= "当前状态：" . $this->order_status[$v['order_status']];
            if (isset($v['shipping_status']) && isset($this->shipping_status[$v['shipping_status']])) {
                $order_content .= '、' . $this->shipping_status[$v['shipping_status']];
            }
            $order_content .= "\n";
        }
        $order_content = trim($order_content);
        Wechat::serviceText($order_content);
    }

    /**
     * 获取查询的订单信息
     */
    private function getOrder(){
        $info = $this->getDatas();
        $invoice_no = isset($info['invoice_no']) ? $info['invoice_no'] : '';
        if (!empty($invoice_no)) {
            $params['outid'] = $invoice_no;
        } else {
            $params['mobile'] = \Common\Extend\PhxCrypt::phxEncrypt($info['Content']);
        }
        $orderList = D('Users')
            ->field('shipping_name,order_sn,invoice_no,update_time,integral_money,order_amount,money_paid,goods_amount,bonus,integral_money,shipping_fee,discount,payment_discount,order_status,shipping_status')
            ->getOrder($params, true);
        return $orderList;
    }

    /**
     * 查询查询日记
     * @param string $content
     */
    private function log($content = ''){
        $openid = $this->getData('FromUserName');
        M('WxMsg')->add(array(
            'open_id'=>!empty($openid) ? $openid : '',
            'content'=>$content,
            'type'=>'公众号查询推送',
            'errcode'=>0,
            'errmsg'=>'',
            'addtime'=>Time::gmTime()
        ));
    }
}