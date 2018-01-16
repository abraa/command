<?php
 /**
 * ====================================
 * thinkphp5
 * ====================================
 * Author: 1002571
 * Date: 2018/1/10 11:28
 * ====================================
 * File: Power.php
 * ====================================
 */

namespace app\admin\logic;


use app\admin\BaseLogic;
use app\common\support\LoginSupport;
use app\common\support\LogSupport;
use extend\Tree;

class Power extends BaseLogic{

    protected $modelName = false;


    public function checkPower()
    {
        $user= LoginSupport::getUserInfo();
        if (empty($user)) {
            $this->err = lang('LOGIN_AGAIN');                           //没有登录重定向到登录页
            $this->redirect = true;
            $this->jumpUrl = url('/admin/index/login');
            return false;
        }
        //不开权限则无需校验权限
        if (LoginSupport::getUserInfo('is_open') == 0) {
            return true;
        }
        $action = !empty($this->data['action']) ? $this->data['action'] : strtolower(request()->action());
        $controller = !empty($this->data['controller']) ? $this->data['controller'] : strtolower(request()->controller());
        $module = !empty($this->data['module']) ? $this->data['module'] : strtolower(request()->module());
        $allowAction = !empty($this->data['allowAction']) ? $this->data['allowAction'] : null;
        if (!empty($allowAction) && ($allowAction == '*' || in_array($action, array_map_recursive('strtolower', $allowAction)))) {
            return true;
        }
        $power = array(
            join('-', array($module, $controller, $action)),
            join('-', array($module, $controller, '*'))
        );
        $result = LoginSupport::power($power);
        if ($result == false) {
            $this->err = lang('NOT_ACCESS');
            return false;
        }
        return true;
    }


    public function setPower()
    {
        $this->err = lang('save') . lang('error');
        $result = false;         //设置成功编辑
        $logMsg = lang('power_change');
        $name = $no = '';
        //设置角色权限
        if (isset($this->data['role_id'])) {
            $this->setModel('role');
            $data = [
                'id' => $this->data['role_id'],
                'menu_id' => $this->data['menu_list']
            ];
            $result = $this->dbModel->isUpdate(true)->save($data);
            $name = lang('role');
            $no = $this->data['role_id'];
        }

        //设置用户权限
        if (isset($this->data['user_id'])) {
            $this->setModel('admin');
            $data = [
                'user_id' => $this->data['user_id'],
                'menu_id' => $this->data['menu_list'],
            ];
            $name = lang('manage');
            $no = $this->data['user_id'];
            $result = $this->dbModel->isUpdate(true)->save($data);
        }

        if ($result !== false) {
            $this->msg = lang('SAVE') . lang('SUCCESS');
            LogSupport::adminLog(sprintf($logMsg, $no, $name) . lang('SUCCESS'));
            $result = true;
        } else {
            LogSupport::adminLog(sprintf($logMsg, $no, $name) . lang('ERROR'));
        }
        return $result;
    }


    public function getPower($user = [])
    {
        $power = [];
        if (empty($user)) {
            return $power;
        }
        //获取管理员权限
        if(!isset($user['menu_id'])){
            $this->setModel('admin');
            $menuId = $this->dbModel->getManageInfo(['user_id' => $user['user_id']], 'menu_id');
            $user['menu_id'] = $menuId['menu_id'];
        }

        if (!empty($user['menu_id'])) {
            $power = array_merge($power, explode(',', $user['menu_id']));
        }

        //获取角色权限
        $this->setModel('role');
        $role = $this->dbModel->getAll(); //获取所有角色
        if (!empty($role) && is_array($role)) {
            foreach ($role as $key => $item) {
                if ($item['id'] == $user['role_id']) {
                    $power = array_merge($power, explode(',', $item['menu_id']));
                }
            }

            $child = Tree::findAllChild($role, $user['role_id']); //获取所有子角色
            if (!empty($child)) {
                foreach ($child as $item) {
                    $power = array_merge($power, explode(',', $item['menu_id']));
                }
            }
        }
        $power = array_filter($power); //过滤空值
        $power = array_unique($power); //过滤重复的数据
        $this->setModel('menu');
        $where = ['id' => ['IN', $power]];
        $field = ['id', 'concat_ws("-", module, controller, method)' => 'power', 'module', 'controller', 'other_method'];
        $data = $this->dbModel->getAll($where, $field);
        $menu = [];
        if (!empty($data) && is_array($data)) {
            foreach ($data as $row) {
                $menu[$row['id']][] = strtolower($row['power']);
                if (!empty($row['other_method'])) { //获取关联权限
                    $otherMethod = explode(',', $row['other_method']);
                    foreach ($otherMethod as $method) {
                        $menu[$row['id']][] = strtolower(join('-', array($row['module'], $row['controller'], $method)));
                    }
                }
            }
        }
        return $menu;
    }
}