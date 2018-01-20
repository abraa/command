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
        $rootPath = $input->getOption('root_path');
        $this->rootPath = empty($rootPath) ? ROOT_PATH : $rootPath;
        $name = $input->getOption('name');
        if('install' == $this->getName() &&empty($name)){
            throw new \Exception("install name can not be empty !");
        }
        $this->path = ROOT_PATH .'install'. DS .$name;
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
            new Option('root_path', 'rp', Option::VALUE_OPTIONAL, "安装路径"),       //使用方式  php think hello  --option test或 -o test
        ]);
        $this->setName('install')
            ->addOption('name', 'name', Option::VALUE_OPTIONAL,'install 名称');
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
        foreach($sqlFileList as $file){
            create_tables($db,$file,$prefix);
        }
//        2.生成文件
        $path = glob($this->path .DS.'*',GLOB_ONLYDIR);
        foreach($path as $dir){
            copy_file($dir,$this->rootPath,$this->path);
        }
        echo "=====================\n";
        echo "copy file Success\n";
        //3.安装扩展依赖composer
        exec( 'cd '.$this->rootPath) ;                      //到root_path安装composer require
        foreach($this->composer as $require){
            $command  = 'php composer.phar require '.$require;
            system($command) ;
        }
        echo "=====================\n";
        echo "system composer Success\n";
        //4. replace 文件内容
        foreach($this->replace as $path => $replace){
            $content =  file_get_contents($path);
            $str = str_replace($this->replace_str , $replace ."\n\n\t".$this->replace_str,$content);
            file_put_contents($path,$str);
        }
        echo "=====================\n";
        echo "replace file Success\n";
        //输出结果s
        return "Success";
    }
}