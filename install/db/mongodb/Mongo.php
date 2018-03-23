<?php
/**
 * ====================================
 * mongodb数据库模型
 * ====================================
 * Author: 9004396
 * Date: 2017-10-30 16:42
 * ====================================
 * Project: ggzy
 * File: Mongo.class.php
 * ====================================
 */
namespace app\common\model;

use think\Model;

class Mongo extends Model {


    public function __construct($data = [])
    {
        config('database.mongo',[
        // 数据库部署方式:0 集中式(单一服务器),1 分布式(主从服务器)
        'deploy'         => 0,
        // 数据库类型
        'type'           => '\fm\Mongodb',
        // 服务器地址
        'hostname'       => '192.168.148.85',
        // 数据库名
        'database'       => 'jimizyDb',

        // 实际操作数据库名
        'real_database' => 'jimizyDb',

        // 用户名
        'username'       => 'jimizyUser',
        // 密码
        'password'       => 'jimizyPwd',
        // 端口
        'hostport'       => '27017',
        // 数据库编码默认采用utf8
        'charset'        => 'utf8',
        // 数据库表前缀
        'prefix'         => 'jm_',
        // 数据库读写是否分离 主从式有效
        'rw_separate'    => false,
        // 是否_id转换为id
        'pk_convert_id' => true,
    ]);
        $this->connection = 'database.mongo';               //配置信息
        parent::__construct($data);
    }


}