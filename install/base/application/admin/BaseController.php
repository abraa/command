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

namespace app\admin;


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
        $power = logic('power');
        if(!is_null($this->allowAction)){
            $power->setData('allowAction',$this->allowAction);
        }
        $action = $this->request->action();
        $power->setData('action',$action);
        if(in_array($action,$this->allow)){
            return true;
        }
        $result = $power->checkPower();
        if ($result == false) {
           $this->logicError([],$power);
        }
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

    /**
     * 列表入口
     * @return mixed
     */
    public function index()
    {
        if ($this->request->isAjax()) {
            $data = $this->logic->lists($this->isTree);
            return $data;
        }
        return $this->fetch($this->template);
    }


    /**
     * 表单入口
     */
    public function form()
    {
        if ($this->request->isPost()) {
            $this->save();
        }
        return $this->fetch();
    }

    /**
     *  保存
     */
    public function save()
    {
        $result = $this->logic->save();
        if ($result) {
            $this->success($this->logic->getInfo());
        } else {
            $this->error($this->logic->getError());
        }
    }

    /**
     *  删除
     */
    public function delete()
    {
        $result = $this->logic->delete();
        if ($result) {
            $this->success($this->logic->getInfo());
        } else {
            $this->error($this->logic->getError());
        }
    }

    /**
     *  修改状态
     */
    public function lock()
    {
        $result = $this->logic->lock();
        if ($result) {
            $this->success($this->logic->getInfo());
        } else {
            $this->error($this->logic->getError());
        }
    }

    /**
     * 下拉列表
     * @return mixed
     */
    public function select()
    {
        $data = $this->logic->select();
        return $data;
    }
    //TODO...
}