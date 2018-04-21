<?php
 /**
 * ====================================
 * thinkphp5
 * ====================================
 * Author: 1002571
 * Date: 2018/3/31 17:46
 * ====================================
 * File: Index.php
 * ====================================
 */

namespace app\index\controller;


class Index {
    function index(){
        $client = new \swoole_client(SWOOLE_SOCK_TCP);

//连接到服务器
        if (!$client->connect('192.168.218.2', 9501, 0.5))
        {
            die("connect failed.");
        }
//向服务器发送数据
        if (!$client->send("hello world"))
        {
            die("send failed.");
        }
//从服务器接收数据
        $data = $client->recv();
        if (!$data)
        {
            die("recv failed.");
        }
        echo $data;
//关闭连接
        $client->close();
    }
}