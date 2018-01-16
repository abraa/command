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

namespace app\admin;


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
            ////实例化失败默认初始化公共model
            $this->dbModel = new BaseModel();
            $this->dbModel->setName($this->modelName);
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
    /**
     * 列表管理
     * @param boolean $isTree 是否返回树状数据
     * @return mixed
     */
    public function lists($isTree = false)
    {

        if (method_exists($this->dbModel, 'filter')) {
            $this->dbModel->filter($this->data);
        }
        if (method_exists($this->dbModel, 'grid') && $isTree == false) {
            $data = $this->dbModel->grid($this->data);
        } else {
            $data = $this->dbModel->getAll();
        }

        if (method_exists($this, 'format')) {
            $data = $this->format($data);
        }
        $selected = isset($this->data['selected']) ? $this->data['selected'] : '';
        $type = isset($this->data['type']) ? $this->data['type'] : '';
        if ($isTree) {
            tree($data, $selected, $type);
        }
        return $data;
    }

    public function save()
    {
        $pk = $this->dbModel->getPk();
        if ($this->data[$pk]) {
            $this->dbModel->isUpdate(true);
            $this->data['update_by'] = LoginSupport::getUserId();
            $this->data['update_time'] = time();
            $scene = "edit";
            $msg = lang('edit');
            $logMsg = lang('edit_data');
        } else {
            $this->dbModel->isUpdate(false);
            $this->data['create_by'] =  LoginSupport::getUserId();
            $this->data['create_time'] = time();
            $scene = "add";
            $msg = lang('add');
            $logMsg = lang('edit_data');
        }
        if($this->isValidate){                                                         //验证字段
            try{
                $validate = validate($this->modelName);
                $result = $validate->scene($scene)->check($this->data);
                if($result === false){
                    $this->err = $validate->getError();
                    return false;
                }
            }catch (ClassNotFoundException $e){
                //文件不存在不做校验
            }
        }
        if (method_exists($this, '_before_save')) {
            $before = $this->_before_save();
            if(!is_null($before)){
                return $before;
            }
        }
        $result = $this->dbModel->allowField(true)->save($this->data);
        if (empty($this->data[$pk])) {
            $this->data[$pk] = $this->dbModel->getLastInsID();
        }
        if ($result !== false) {
            //调用保存后需要处理的方法
            if (method_exists($this, '_after_save')) {
                $after = $this->_after_save();
                if(!is_null($after)){
                    return $after;
                }
            }
            $this->msg = $msg . lang('success');
            LogSupport::adminLog(sprintf($logMsg, $this->data[$pk]) . lang('success'),LoginSupport::getUserId());     //保存日志
            return true;
        } else {
            $this->err = $this->dbModel->getError();
            LogSupport::adminLog(sprintf($logMsg, $this->data[$pk]) . lang('error'),LoginSupport::getUserId());     //保存日志
            return false;

        }
    }

    public function delete()
    {
        if (method_exists($this, '_before_delete')) {
            $before = $this->_before_delete();
            if(!is_null($before)){
                return $before;
            }
        }
        $where = [];
        $pk = $this->dbModel->getPk();
        if (!empty($this->data[$pk])) {
            if (strpos($this->data[$pk], ',') !== false) {
                $where[$pk] = ['IN', $this->data[$pk]];
            } else {
                $where[$pk] = $this->data[$pk];
            }
        }
        if (!empty($where)) {
            $this->dbModel->where($where);
        }
        $result = $this->dbModel->delete();
        if ($result) {
            if (method_exists($this, '_after_delete')) {
                $after = $this->_after_delete();
                if(!is_null($after)){
                    return $after;
                }
            }
            $this->msg = lang('DELETE') . lang('SUCCESS');
            LogSupport::adminLog(sprintf(lang('delete_data'), $this->data[$pk]) . lang('SUCCESS'),LoginSupport::getUserId());     //保存日志
           return true;
        } else {
            $this->err = lang('DELETE') . lang('ERROR');
            LogSupport::adminLog(sprintf(lang('delete_data'), $this->data[$pk]) . lang('ERROR'),LoginSupport::getUserId());     //保存日志
            return false;
        }
    }

    public function lock()
    {
        if (empty($this->data)) {
            $this->err = lang('SELECT_NODE') . lang('ADMIN');
            return false;
        }
        $data = $this->data;
        if (method_exists($this, '_before_lock')) {
            $before = $this->_before_lock();
            if(!is_null($before)){
                return $before;
            }
        }

        $where = [];
        $pk = $this->dbModel->getPk();
        if (!empty($this->data[$pk])) {
            if (strpos($this->data[$pk], ',') !== false) {
                $where[$pk] = ['IN', $this->data[$pk]];
            } else {
                $where[$pk] = $this->data[$pk];
            }
            unset($this->data[$pk]);
        }
        if (!empty($where)) {
            $this->dbModel->where($where);
        }
        $result = $this->dbModel->update($this->data);
        if ($result) {
            if (method_exists($this, '_after_lock')) {
                $after = $this->_after_lock();
                if(!is_null($after)){
                    return $after;
                }
            }
            $this->msg = lang('SAVE') . lang('SUCCESS');
            LogSupport::adminLog(sprintf(lang('edit_data'), $data[$pk]) . lang('SUCCESS'),LoginSupport::getUserId());     //保存日志
            return true;
        } else {
            $this->err = lang('SAVE') . lang('ERROR');
            LogSupport::adminLog(sprintf(lang('edit_data'), $this->data[$pk]) . lang('ERROR'),LoginSupport::getUserId());     //保存日志
            return false;
        }
    }

    /**
     *  下拉列表
     */
    public function select(){
        if (method_exists($this->dbModel, 'filter')) {
            $this->dbModel->filter($this->data);
        }
        $field = [];
        if(isset($this->data['field'])){
            $field = $this->data['field'];
        }
        $data = $this->dbModel->getAll([],$field);
        if (method_exists($this, '_after_select')) {
            $data = $this->_after_select($data);
        }
        return $data;
    }
    //TODO...
}