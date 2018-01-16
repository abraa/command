<?php
 /**
 * ====================================
 * thinkphp5
 * ====================================
 * Author: 1002571
 * Date: 2017/12/29 15:30
 * ====================================
 * File: hello.php
 * ====================================
 */

namespace app\command;

use think\console\Command;
use think\console\Input;
use think\console\input\Argument;
use think\console\input\Option;
use think\console\Output;
use think\Request;

class Hello extends Command{                            //继承think\console\Command

    /**
     * 初始化
     * @param Input  $input  An InputInterface instance
     * @param Output $output An OutputInterface instance
     */
    protected function initialize(Input $input, Output $output)
    {
        $this->setCode([$this,'index']);
    }

    function index(Input $input, Output $output){
        $output->writeln(controller('index/Index')->index());       //执行index模块index控制器index函数 ,并输出返回值
    }

    /**
     * 重写configure
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName('hello')                                 //命令名称
            ->setDefinition([                           //Option 和 Argument都可以有多个, Argument的读取和定义的顺序有关.请注意
                new Option('option', 'o', Option::VALUE_OPTIONAL, "命令option选项"),       //使用方式  php think hello  --option test或 -o test
                new Argument('test',Argument::OPTIONAL,"test参数"),                        //使用方式    php think hello  test1 (将输入的第一个参数test1赋值给定义的第一个Argument参数test)
                //...
            ])
            ->setDescription('hello word !');                               //命令描述
    }

    /**
     * 重写execute
     * {@inheritdoc}
     */
    protected function execute(Input $input, Output $output)
    {                                                           //Input 用于获取输入信息    Output用于输出信息
        $request = Request::instance([                          //如果在希望代码中像浏览器一样使用input()等函数你需要示例化一个Request并手动赋值
            'get'=>$input->getArguments(),                    //示例1: 将input->Arguments赋值给Request->get  在代码中可以直接使用input('get.')获取参数
            'route'=>$input->getOptions()                       //示例2: 将input->Options赋值给Request->route   在代码中可以直接使用request()->route(false)获取
            //...
        ]);
        $request->module("Index");                          //绑定当前模块为Index  只有绑定模块你在代码中使用model('user')等函数才不需要指定模块model('index/user')
//        $request->controller('index')->action('index');       //绑定controller和action

        $output->writeln(controller('index/Index')->index());       //执行index模块index控制器index函数 ,并输出返回值
    }
}