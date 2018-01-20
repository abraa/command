<?php
/**
 * ====================================
 * thinkphp5
 * ====================================
 * Author: 1002571
 * Date: 2018/1/9 14:15
 * ====================================
 * File: BaseController.php
 * ====================================
 */

namespace app\people;


use app\common\support\ConfigSupport;
use app\common\support\LoginSupport;
use think\Controller;


class BaseController extends Controller{
    /**
     * 设置模版
     * @var string
     */
    protected $template = '';

    /**
     * 是否树状数据
     * @var bool
     */
    protected $isTree = false;
    /**
     * 需求访问的方法（无需权限校验）
     * @var null
     */
    protected $allowAction = null;

    /**
     * 是否开启校验
     * @var bool
     */
    protected $isValidate;

    /**
     * 逻辑层名称
     * @var string
     */
    protected $logicName;
    /**
     * 逻辑层对象
     * @var mixed
     */
    protected $logic;

    /**
     * 无需用户登陆都可以访问的模块
     * @var array
     */
    protected $allow = ['login','logout','verify'];
    public function _initialize()
    {
        config(ConfigSupport::getConfig());
        parent::_initialize();
        //校验权限
        $this->access();
        $params = input();
        if (is_null($this->logicName)) {
            $this->logicName = $this->request->controller();
        }
        try {
            $this->logic = logic($this->logicName);
        } catch (\Exception $e) {
            //后台当逻辑层文件不存在，初始化公共逻辑类
            $this->logic = new BaseLogic();
            $this->logic->setModel($this->logicName);
        }
        if (!is_null($this->isValidate)) {
            $this->logic->isValidate = $this->isValidate;
        }
        $this->logic->setData($params);
    }

    /**
     * 访问页面权限检查
     * @return bool
     */
    private function access()
    {
        if(!is_null($this->allowAction)){
            $this->allow = array_merge($this->allow,$this->allowAction);
        }
        $action = $this->request->action();
        if(in_array($action,$this->allow)){
            return true;
        }
        //检查是否登录
        return LoginSupport::power('*');
    }

    /**
     * 逻辑层错误默认处理
     * @param string $data
     * @param null $logic
     * @return bool
     */
    protected function logicError($data='',$logic=null){
        $logic = is_null($logic) ? $this->logic : $logic;
        if(empty($logic)) return false;
        $jumpUrl = $logic->getJumpUrl();
        if (!empty($jumpUrl)) {
            if ($logic->getRedirect()) {
                $this->redirect($jumpUrl);
            }else{
                $this->error($logic->getError(),$jumpUrl,$data);
            }
        }
        $this->error($logic->getError());
    }

    /**
     * 设置layout模板  false关闭
     * @param $layout
     */
    public function layout($layout)
    {
        $this->view->engine->layout($layout);
    }

    //TODO...
}