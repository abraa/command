<?php
/**
 * ====================================
 * 脚本默认页
 * ====================================
 * Author: 9004396
 * Date: 2017-05-13 17:24
 * ====================================
 * Project: new.m.chinaskin.cn
 * File: IndexController.class.php
 * ====================================
 */
namespace Script\Controller;

use Common\Controller\ScriptController;
use Common\Extend\Time;
use Think\Model;

class IndexController extends ScriptController
{

    public $urls = array();

    public function index()
    {
        if (empty($_SERVER['argc'])) {
            $str = '<h1 style="text-align: center; padding-top: 20%">SCRIPT</h1>';
            die($str);
        } else {
            echo 'script run';
            exit;
        }
    }
}