<?php
/**
 * ====================================
 * 脚本公共模块
 * ====================================
 * Author: 9004396
 * Date: 2017-05-13 17:25
 * ====================================
 * Project: new.m.chinaskin.cn
 * File: ScriptController.class.php
 * ====================================
 */
namespace Common\Controller;

use Think\Controller;
use Think\Model;

class ScriptController extends Controller
{

    private $filter = array('index');
    private $dbNum = 0;


    protected $num = 0;       //执行总数
    protected $total = 0;     //记录总数
    protected $noChange = 0;  //未变更数据数
    protected $errMsg = array();  //错误记录


    public $page = 1;      //页数
    public $offset = 60000; //每页的总数
    public $maxPage = 0;   //最大页数

    public function _initialize()
    {
        set_time_limit(0);
        ini_set('memory_limit', '1024M');
        $controller = strtolower(CONTROLLER_NAME);
        $argv = $_SERVER['argc'];
//        if(!in_array($controller,$this->filter) && empty($argv)){
//            redirect('/index/index');
//        }
    }

}