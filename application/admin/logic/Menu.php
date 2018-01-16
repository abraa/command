<?php
/**
 * ====================================
 * 菜单管理
 * ====================================
 * Author: 9004396
 * Date: 2017-11-03 10:07
 * ====================================
 * Project: ggzy
 * File: Menu.php
 * ====================================
 */

namespace app\admin\logic;

use app\admin\BaseLogic;
use app\common\support\LoginSupport;
use extend\Tree;

class Menu extends BaseLogic
{
    /**
     * 获取后台菜单
     * @return array
     */
    public function getPanelMenu()
    {
        $where['display'] = 1;
        $user = LoginSupport::getUserInfo();
        if (!empty($user['is_open'])) {
            if (empty($user['menu'])) {
                return [];
            } else {
                $where['id'] = ['IN', array_keys($user['menu'])];
            }
        }

        $field = ['id', 'pid', 'text', 'module', 'controller', 'method', 'icon'];
        $menu = $this->dbModel->getAll($where, $field);
        if (!empty($menu) && is_array($menu)) {
            foreach ($menu as $key => $item) {
                $action = (empty($item['method']) || $item['method'] == '*') ? config('default_action') : $item['method'];
                $href = [$item['controller'], $action];
                if (!empty($item['module'])) {
                    array_unshift($href, $item['module']);
                }
                $item['href'] = url(join('/', $href));
                $menu[$key] = $item;
            }
            $menu = Tree::treeArray($menu);
        }
        return $menu;
    }


    public function format($data = [])
    {
        if (empty($data) || !is_array($data)) {
            return $data;
        }

        $power = [];
        $disable = [];
        $rolePower = [];
        $checkRole = [];//存储当前勾选数据

        //会员权限处理
        if (!empty($this->data['userId'])) {
            $this->setModel('admin');
            $userInfo = $this->dbModel->getManageInfo(
                ['user_id' => $this->data['userId']],
                ['role_id', 'menu_id']
            );
            if (!empty($userInfo)) {
                $this->data['roleId'] = $userInfo['role_id'];
            }
            $power = array_merge($power, explode(',', $userInfo['menu_id']));
            $checkRole = $power;
        }

        //角色权限处理
        if (!empty($this->data['roleId'])) {
            $this->setModel('role');
            $role = $this->dbModel->getAll(); //获取所有角色
            foreach ($role as $key => $item) {
                if ($item['id'] == $this->data['roleId']) {
                    $rolePower = explode(',', $item['menu_id']);
                    $power = array_merge($power, $rolePower);
                }
            }
            if (!empty($this->data['userId'])) {
                $disable = array_merge($disable, $rolePower);
            } else {
                $checkRole = $rolePower;
            }
            //获取所有子角色
            $child = Tree::findAllChild($role, $this->data['roleId']);
            if (!empty($child)) {
                foreach ($child as $item) {
                    $roleChildPower = explode(',', $item['menu_id']);
                    $power = array_merge($power, $roleChildPower);
                    $disable = array_merge($disable, $roleChildPower);
                }
            }
        }

        //过滤空值
        $power = array_filter($power);
        $disable = array_filter($disable);

        //过滤重复值
        $power = array_unique($power);
        $disable = array_unique($disable);

        foreach ($data as &$item) {
            $item['power'] = "power('{$item['module']}-{$item['controller']}-{$item['method']}')";
            if (!empty($disable)) {
                $item['disable'] = in_array($item['id'], $disable) ? true : false;
            }
            $item['unchecked'] = (in_array($item['id'], $checkRole)) ? false : true;
            $item['checked'] = in_array($item['id'], $power) ? true : false;
        }
        return $data;
    }


    public function getSearchData()
    {
        $result = [
            array('text' => lang('SELECT_NODE'), 'selected' => true, 'value' => '')
        ];
        $where = [];
        $field = [];

        if (!empty($this->data['module'])) {
            $where['module'] = $this->data['module'];
        }
        if (!empty($this->data['controller'])) {
            $where['controller'] = $this->data['controller'];
        }
        if (!empty($this->data['type'])) {
            $this->dbModel->setGroup($this->data['type']);
        }
        if (empty($this->data['field'])) {
            $field = $this->data['type'] . " as text";
        }

        $data = $this->dbModel->getMenu($where, $field);
        foreach ($data as &$item) {
            $item['value'] = $item['text'];
        }
        $result = array_merge($result, $data);
        return $result;
    }

    function _after_delete(){
        /*==== menu删除时删除子菜单 ====*/
        $pk = $this->dbModel->getPk();
        if (isset($this->data[$pk]) && !empty($this->data[$pk])) {
            if (strpos($this->data[$pk], ',') !== false) {
                $where['pid'] = ['IN', $this->data[$pk]];
            } else {
                $where['pid'] = $this->data[$pk];
            }
            $this->dbModel->where($where)->delete();
        }
    }
}