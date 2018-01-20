<?php
 /**
 * ====================================
 * File: send_email.php
 * ====================================
 */


return [

    'mail_auth' =>  true,           //开启SMTP认证
    'debug'     =>  0,               // 改为2可以开启调试
    'mail_server'=> 'smtp.exmail.qq.com',  //smtp服务器地址
    'mail_port'  => 465,                    //smtp服务器端口
    'is_ssl'  => true,                    //是否ssl连接
    'mail_user' => '380531734@qq.com',          //账号
    'mail_password' => 'xxxxx',               //密码
    'mail_from' => '380531734@qq.com',       //发件邮箱
    'mail_name' => '',                      //发件名称
    'mail_reply' => '',                     //回复邮箱
    'reply_name' => '',                     //回复名称

];