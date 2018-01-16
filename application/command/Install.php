<?php
/**
 * ====================================
 * thinkphp5
 * ====================================
 * Author: 1002571
 * Date: 2017/12/29 15:30
 * ====================================
 * File: base.php
 * ====================================
 */

namespace app\command;

use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\console\input\Argument;
use think\console\input\Option;
use think\Db;


class Install extends Command{                            //继承think\console\Command

    protected $path ;                           //安装文件目录
    protected $rootPath ;                           //安装到目录(目标目录)
    protected $composer = [                          //composer require  申明依赖包

    ] ;
    protected $replace_str ="//TODO...";            //默认替换内容
    protected $replace = [                          //需要替换的公共文件内容  [file_path => content]

    ] ;
    /**
     * 初始化
     * @param Input  $input  An InputInterface instance
     * @param Output $output An OutputInterface instance
     */
    protected function initialize(Input $input, Output $output)
    {
        include __DIR__.DS . 'common' . EXT;
        $this->setReplace();

    }

    /**
     *  替换文件内容设置   $this->replace = [file_path => content]
     */
    protected function setReplace(){

    }


    /**
     * 重写configure
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setDefinition([
            new Option('prefix', 'p', Option::VALUE_OPTIONAL, "数据库表前缀"),       //使用方式  php think hello  --option test或 -o test
        ]);
    }

}