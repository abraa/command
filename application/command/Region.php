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
use think\console\input\Argument;
use think\console\input\Option;
use think\console\Output;
use think\Db;


class Region extends Install{                            //继承think\console\Command

    protected $path ;                           //安装文件目录
    protected $rootPath ;                           //安装到目录(目标目录)
    protected $composer = [                          //composer require  申明依赖包

    ] ;
    /**
     * 初始化
     * @param Input  $input  An InputInterface instance
     * @param Output $output An OutputInterface instance
     */
    protected function initialize(Input $input, Output $output)
    {
        parent::initialize($input,$output);
        $this->path = ROOT_PATH .'install'. DS .'region';
        $this->rootPath = ROOT_PATH;
    }


    /**
     * 重写configure
     * {@inheritdoc}
     */
    protected function configure()
    {
        parent::configure();
        $this->setName('region')                                 //命令名称
        ->addOption('full', null, Option::VALUE_OPTIONAL, "是否安装完整版地区sql(文件很大!)")
        ->setDescription('地区服务功能!');                               //命令描述
    }

    /**
     * 重写execute
     * {@inheritdoc}
     */
    protected function execute(Input $input, Output $output)
    {                                                           //Input 用于获取输入信息    Output用于输出信息
        //1.生成数据表
        $db = Db::connect();

        $sqlFileList = glob($this->path .DS.'*.sql');
        $prefix = $input->getOption('prefix');
        $full = $input->getOption('full');
        foreach($sqlFileList as $file){
            if(false !== strrpos(substr($file,intval(strrpos($file,DS))),'full_') )    //以full_开头的是完整版数据库
            {
                if(!empty($full))                                                    //是否安装完整版
                {
                    update_tables($db,$file,$prefix);
                }
            }else if(empty($full)){                                                 //不以full_开头的是简略版
                    update_tables($db,$file,$prefix);
            }
        }

        //2.生成文件
        $path = glob($this->path .DS.'*',GLOB_ONLYDIR);
        foreach($path as $dir){
            copy_file($dir,$this->rootPath,$this->path);
        }
        echo "=====================\n";
        echo "copy file Success\n";
        //补充文件
        //3.安装扩展依赖composer
        exec( 'cd '.$this->rootPath) ;                      //到root_path安装composer require
        foreach($this->composer as $require){
            $command  = 'php composer.phar require '.$require;
            system($command) ;
        }
        //3.输出结果
        return "Success";
    }
}