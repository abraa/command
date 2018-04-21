<?php
 /**
 * ====================================
 * thinkphp5
 * ====================================
 * Author: 1002571
 * Date: 2018/1/23 14:46
 * ====================================
 * File: alipay.php
 * ====================================
 */
return [
    //应用ID,您的APPID。
    'app_id' => "2016040701274038",
    //商户私钥
    'merchant_private_key' => "../cert/alipay_private_key.pem",
    //异步通知地址
    'notify_url' => 'http://www.baidu.com',
    //同步跳转
    'return_url' => '',
    //编码格式
    'charset' => "UTF-8",
    //签名方式
    'sign_type'=>"RSA2",
    //支付宝网关
    'gatewayUrl' => "https://openapi.alipay.com/gateway.do",
    //支付宝公钥,查看地址：https://openhome.alipay.com/platform/keyManage.htm 对应APPID下的支付宝公钥。
    'alipay_public_key' =>  "../cert/alipay_public_key.pem",
];

