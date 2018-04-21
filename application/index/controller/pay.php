<?php
 /**
 * ====================================
 * thinkphp5
 * ====================================
 * Author: 1002571
 * Date: 2018/1/22 16:29
 * ====================================
 * File: pay.php
 * ====================================
 */

namespace app\index\controller;

use aggregation\pay\AlipayPagePay;
use aggregation\pay\Tenpay;
use aggregation\pay\WeChatNative;
use app\index\BaseController;


class Pay extends BaseController{

    protected $pay;

    public function initialize()
    {
        parent::initialize();
    }

    /**
     * 微信扫码支付
     */
    public function weChatNative(){
        $this->pay = new WeChatNative();
        $config = config('wechat');
        $config['notify_url'] = url('weChatNativeNotify','',true,true);
//        print_r($this->pay->setup());             //需要填写的内容
         $this->pay->init($config);
//        $url = $res->getCode(['product_id'=>8888]);         //模式一 后台预定义产品生成二维码
        $url = $this->pay->getCode(['body'=>'测试',
            'out_trade_no' =>   212352153513,            //商户系统内部订单号，要求32个字符内，只能是数字、大小写字母_-|*@ ，且在同一个商户号下唯一。详见商户订单号
            'total_fee' =>   30,            //订单总金额，单位为分，详见支付金额
            'trade_type' =>   'NATIVE',            //交易类型 取值如下：JSAPI，NATIVE，APP等
            'product_id' =>   '33',            //产品id
        ]);         //模式二 统一下单
        header( "Content-type: image/jpeg");
        echo $this->pay->getQrCode($url);       //二维码图片
        exit;
    }

    /**
     *  微信扫码支付回调通知地址
     */
    public function weChatNativeNotify(){
        $this->pay = new WeChatNative();
        $this->pay->init(config('wechat'));
        if($this->pay->verify()){         //验证通过
            //TODO...
//       $query_result =  $this->queryOrder($result);           //查询订单
//        if(array_key_exists("return_code", $query_result)
//            && array_key_exists("result_code", $query_result)
//            && $query_result["return_code"] == "SUCCESS"
//            && $query_result["result_code"] == "SUCCESS")
//        {
//            return $result;
//        }else{
//            return false;
//        }
        }
        $this->pay->notify();       //返回微信通知结果
    }

    /**
     *  财富通支付
     */
    public function tenpay(){
        $this->pay = new Tenpay(config('tenpay'));
        $data = [
            'return_url' => 'http://www.baidu.com',
            'notify_url' => 'http://www.baidu.com',
            'bank_type' => 'DEFAULT',          //银行卡类型
            'trade_mode' => '1',
            'out_trade_no' => '12345682555111',
            'total_fee' => '100',       //分
            'body' => 'test',
            'subject' => 'test',
        ];
        $code = $this->pay->getCode($data);
        echo $code;
        exit;
    }

    /**
     * 财付通通知
     */
    public function tenpayNotify(){
        $this->pay = new Tenpay(config('tenpay'));
        $data = array_merge(input('get.',[]),input('post.',[]));
        if($this->pay->notify($data,[$this,'order'])){              //回调函数处理通过逻辑
            return ;
        }
        //TODO.. 不通过处理
    }

    /**
     *  通过执行订单更新操作
     */
    public function order(){
        //TODO...
    }

    /**
     * 财付通回调
     */
    public function tenpayReturn(){
        $this->pay = new Tenpay(config('tenpay'));
        $data = array_merge(input('get.',[]),input('post.',[]));
        if($this->pay->verify($data)){              //通过验证
            return "pay OK";
        }
        return "pay fail";
    }

    public function alipay(){
        $this->pay = new AlipayPagePay();
        $this->pay->init(config('alipay'));
        $data = [
           'out_trade_no' => '655285536666512',
           'subject' => 'test',
           'total_amount' => '100',
           'body' => 'test',
        ];
        echo $this->pay->getCode($data);
    }

    /**
     *实际验证过程建议商户添加以下校验。
    1、商户需要验证该通知数据中的out_trade_no是否为商户系统中创建的订单号，
    2、判断total_amount是否确实为该订单的实际金额（即商户订单创建时的金额），
    3、校验通知中的seller_id（或者seller_email) 是否为out_trade_no这笔单据的对应的操作方（有的时候，一个商户可能有多个seller_id/seller_email）
    4、验证app_id是否为该商户本身。
     */
    public function alipayNotify(){
        $this->pay = new AlipayPagePay();
        $this->pay->init(config('tenpay'));
        $params = input('post.',[]);
        if($this->pay->verify($params)){
            ///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
            //请在这里加上商户的业务逻辑程序代
            //——请根据您的业务逻辑来编写程序（以下代码仅作参考）——

            //获取支付宝的通知返回参数，可参考技术文档中服务器异步通知参数列表

            //商户订单号

            $out_trade_no = $params['out_trade_no'];

            //支付宝交易号

            $trade_no = $params['trade_no'];

            //交易状态
            $trade_status = $params['trade_status'];


            if($params['trade_status'] == 'TRADE_FINISHED') {

                //判断该笔订单是否在商户网站中已经做过处理
                //如果没有做过处理，根据订单号（out_trade_no）在商户网站的订单系统中查到该笔订单的详细，并执行商户的业务程序
                //请务必判断请求时的total_amount与通知时获取的total_fee为一致的
                //如果有做过处理，不执行商户的业务程序

                //注意：
                //退款日期超过可退款期限后（如三个月可退款），支付宝系统发送该交易状态通知
            }
            else if ($params['trade_status'] == 'TRADE_SUCCESS') {
                //判断该笔订单是否在商户网站中已经做过处理
                //如果没有做过处理，根据订单号（out_trade_no）在商户网站的订单系统中查到该笔订单的详细，并执行商户的业务程序
                //请务必判断请求时的total_amount与通知时获取的total_fee为一致的
                //如果有做过处理，不执行商户的业务程序
                //注意：
                //付款完成后，支付宝系统发送该交易状态通知
            }
            $this->pay->notify(true);
        }else{
            $this->pay->notify(false);
        }
    }

    public function alipayReturn(){
        $this->pay = new AlipayPagePay();
        $this->pay->init(config('tenpay'));
        $params = input('post.',[]);
        if($this->pay->verify($params)){
            //验证通过
        }
    }
    public function index(){
        echo 1;
    }
}