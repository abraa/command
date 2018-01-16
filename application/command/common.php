<?php
// +----------------------------------------------------------------------
// | OneThink [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013 http://www.onethink.cn All rights reserved.
// +----------------------------------------------------------------------
// | Author: 麦当苗儿 <zuojiazi@vip.qq.com> <http://www.zjzit.cn>
// +----------------------------------------------------------------------

/**
 * 系统环境检测
 * @return array 系统环境数据
 */
function check_env(){
    $items = array(
        'os'      => array('操作系统', '不限制', '类Unix', PHP_OS, 'success'),
        'php'     => array('PHP版本', '5.3', '5.3+', PHP_VERSION, 'success'),
        'upload'  => array('附件上传', '不限制', '2M+', '未知', 'success'),
        'gd'      => array('GD库', '2.0', '2.0+', '未知', 'success'),
        'disk'    => array('磁盘空间', '5M', '不限制', '未知', 'success'),
    );

    //PHP环境检测
    if($items['php'][3] < $items['php'][1]){
        $items['php'][4] = 'error';
        session('error', true);
    }

    //附件上传检测
    if(@ini_get('file_uploads'))
        $items['upload'][3] = ini_get('upload_max_filesize');

    //GD库检测
    $tmp = function_exists('gd_info') ? gd_info() : array();
    if(empty($tmp['GD Version'])){
        $items['gd'][3] = '未安装';
        $items['gd'][4] = 'error';
        session('error', true);
    } else {
        $items['gd'][3] = $tmp['GD Version'];
    }
    unset($tmp);

    //磁盘空间检测
    if(function_exists('disk_free_space')) {
        $items['disk'][3] = floor(disk_free_space(INSTALL_APP_PATH) / (1024*1024)).'M';
    }

    return $items;
}

/**
 * 目录，文件读写检测
 * @return array 检测数据
 */
function check_dirfile(){
    $items = array(
        array('dir',  '可写', 'success', './Uploads/Download'),
        array('dir',  '可写', 'success', './Uploads/Picture'),
        array('dir',  '可写', 'success', './Uploads/Editor'),
        array('dir',  '可写', 'success', './Runtime'),
        array('dir',  '可写', 'success', './Data'),
        array('dir', '可写', 'success', './Application/User/Conf'),
        array('file', '可写', 'success', './Application/Common/Conf'),

    );

    foreach ($items as &$val) {
        $item =	INSTALL_APP_PATH . $val[3];
        if('dir' == $val[0]){
            if(!is_writable($item)) {
                if(is_dir($items)) {
                    $val[1] = '可读';
                    $val[2] = 'error';
                    session('error', true);
                } else {
                    $val[1] = '不存在';
                    $val[2] = 'error';
                    session('error', true);
                }
            }
        } else {
            if(file_exists($item)) {
                if(!is_writable($item)) {
                    $val[1] = '不可写';
                    $val[2] = 'error';
                    session('error', true);
                }
            } else {
                if(!is_writable(dirname($item))) {
                    $val[1] = '不存在';
                    $val[2] = 'error';
                    session('error', true);
                }
            }
        }
    }

    return $items;
}

/**
 * 函数检测
 * @return array 检测数据
 */
function check_func(){
    $items = array(
        array('pdo','支持','success','类'),
        array('pdo_mysql','支持','success','模块'),
        array('file_get_contents', '支持', 'success','函数'),
        array('mb_strlen',		   '支持', 'success','函数'),
    );

    foreach ($items as &$val) {
        if(('类'==$val[3] && !class_exists($val[0]))
            || ('模块'==$val[3] && !extension_loaded($val[0]))
            || ('函数'==$val[3] && !function_exists($val[0]))
        ){
            $val[1] = '不支持';
            $val[2] = 'error';
            session('error', true);
        }
    }

    return $items;
}


/**
 * 创建数据表
 * @param  resource $db 数据库连接资源
 */
function create_tables($db, $sql, $prefix = ''){
    //读取SQL文件
    $sql = file_get_contents($sql);
    $sql = str_replace("\r", "\n", $sql);
    $sql = explode(";\n", $sql);

    //替换表前缀
    if(!empty($prefix)){
        $sql = str_replace(" `py_", " `{$prefix}", $sql);
    }

    //开始安装
    show_msg('Install DataBase...');
    foreach ($sql as $value) {
        $value = trim($value);
        if(empty($value)) continue;
        if(substr($value, 0, 12) == 'CREATE TABLE') {
            $name = preg_replace("/^CREATE TABLE `(\w+)` .*/s", "\\1", $value);
            $msg  = "create table {$name}";
            if(false !== $db->execute($value)){
                show_msg($msg . '...Success');
            } else {
                show_msg($msg . '...Error！', 'error');
                session('error', true);
            }
        } else {
            $db->execute($value);
        }

    }
}


/**
 * 更新数据表
 * @param  resource $db 数据库连接资源
 * @author lyq <605415184@qq.com>
 */
function update_tables($db,$sql, $prefix = ''){
    //读取SQL文件
    $sql = str_replace("\r", "\n", $sql);
    $sql = explode(";\n", $sql);

    //替换表前缀
    if(!empty($prefix)){
        $sql = str_replace(" `py_", " `{$prefix}", $sql);
    }

    //开始安装
    show_msg('Install DataBase...');
    foreach ($sql as $value) {
        $value = trim($value);
        if(empty($value)) continue;
        if(substr($value, 0, 12) == 'CREATE TABLE') {
            $name = preg_replace("/^CREATE TABLE `(\w+)` .*/s", "\\1", $value);
            $msg  = "create table {$name}";
            if(false !== $db->execute($value)){
                show_msg($msg . '...Succress');
            } else {
                show_msg($msg . '...Error！', 'error');
                session('error', true);
            }
        } else {
            if(substr($value, 0, 8) == 'UPDATE `') {
                $name = preg_replace("/^UPDATE `(\w+)` .*/s", "\\1", $value);
                $msg  = "update table {$name}";
            } else if(substr($value, 0, 11) == 'ALTER TABLE'){
                $name = preg_replace("/^ALTER TABLE `(\w+)` .*/s", "\\1", $value);
                $msg  = "ALTER TABLE {$name}";
            } else if(substr($value, 0, 11) == 'INSERT INTO'){
                $name = preg_replace("/^INSERT INTO `(\w+)` .*/s", "\\1", $value);
                $msg  = "INSERT TABLE {$name}";
            }
            if(($db->execute($value)) !== false){
                show_msg($msg . '...Succress');
            } else{
                show_msg($msg . '...Error！', 'error');
                session('error', true);
            }
        }
    }
}

/**
 * 及时显示提示信息
 * @param  string $msg 提示信息
 */
function show_msg($msg, $class = ''){
    echo "{$msg}, {$class}\n";

}

/**
 * 生成系统AUTH_KEY
 * @author 麦当苗儿 <zuojiazi@vip.qq.com>
 */
function build_auth_key(){
    $chars  = 'abcdefghijklmnopqrstuvwxyz0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $chars .= '`~!@#$%^&*()_+-=[]{};:"|,.<>/?';
    $chars  = str_shuffle($chars);
    return substr($chars, 0, 40);
}

/**
 * 系统非常规MD5加密方法
 * @param  string $str 要加密的字符串
 * @return string
 */
function user_md5($str, $key = ''){
    return '' === $str ? '' : md5(sha1($str) . $key);
}


/**
 * 复制文件
 * @param $file_path
 * @param string $root_path
 * @param string $path
 * @return int
 */
function copy_file($file_path,$root_path="",$path=""){
    if(is_dir($file_path)){                                      //如果是目录继续遍历
        if(makeDir(str_replace($path,$root_path,$file_path))){   //在root_path创建相同目录
            $files  = glob($file_path .DS.'*');
            foreach($files as $file){
                copy_file($file,$root_path,$path);
            }
        }
    }else{
        $data = file_get_contents($file_path);                //把文件复制到root_path
        $file_name = str_replace($path,$root_path,$file_path);
        return file_put_contents($file_name,$data);
    }

}
