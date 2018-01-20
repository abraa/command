<?php
 /**
 * ====================================
 * thinkphp5
 * ====================================
 * Author: 1002571
 * Date: 2018/1/9 14:18
 * ====================================
 * File: BaseLogic.php
 * ====================================
 */

namespace app\people;


use app\common\support\LoginSupport;
use app\common\support\LogSupport;
use think\exception\ClassNotFoundException;

class BaseLogic {

    /**
     * 信息
     * @var string
     */
    protected $msg;

    /**
     * 错误信息
     * @var string
     */
    protected $err;

    /**
     * 是否php重定向
     * @var string
     */
    protected $redirect = false;
    /**
     * 跳转链接
     * @var string
     */
    protected $jumpUrl = null;

    /**
     * 数据信息
     * @var array
     */
    protected $data = [];


    /**
     * 是否开启校验
     * @var bool
     */
    public $isValidate = true;

    /**
     * 模型名称
     * @var bool
     */
    protected $modelName = null;
    /**
     * 模型对象
     * @var string
     */
    protected $dbModel;

    public function __construct()
    {
        if(is_null($this->modelName)){
            $class = get_class($this);
            $start = strrpos($class,'\\');
            if(false !== $start){                           //当前类名 , 不包含命名空间
                $this->modelName = substr($class,$start+1);
            }else{
                $this->modelName = $class;
            }
        }
        $this->setModel($this->modelName);
        $this->_initialize();
    }

    /**
     * 初始化操作
     * @access protected
     */
    protected function _initialize()
    {
    }

    /**
     * 在外面手动实例化model
     * @param $modelName
     * @return BaseModel|string|\think\Model
     */
    public function setModel($modelName){
        $this->modelName = $modelName;
        try{
            $this->dbModel = model($this->modelName);
        }catch (ClassNotFoundException $e){
            //实例化失败不做处理
        }
        return $this->dbModel;
    }
    
    /**
     * 获取信息
     * @return string
     */
    public function getInfo()
    {
        return $this->msg;
    }

    /**
     * 获取错误信息
     * @return string
     */
    public function getError()
    {
        return $this->err;
    }
    /**
     * 获取跳转链接
     * @return string|null
     */
    public function getJumpUrl(){
        return $this->jumpUrl;
    }


    /**
     * 获取是否php重定向
     * @return bool
     */
    public function getRedirect(){
        return $this->redirect;
    }
    
    /**
     * 设置数据
     * @param string $name 名称
     * @param string $value 值
     */
    public function setData($name, $value = '')
    {
        if (is_array($name)) {
            $this->data = $name;
        } else {
            $this->data[$name] = $value;
        }
    }

    /**
     * 获取数据
     * @param string $name
     * @return array|mixed
     */
    public function getData($name = NULL)
    {
        return is_null($name) ? $this->data : (isset($this->data[$name]) ? $this->data[$name] : null);
    }

    //TODO...
}