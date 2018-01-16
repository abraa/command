<?php
/**
 * ====================================
 * 管理员模型
 * ====================================
 * Author: 9004396
 * Date: 2017-10-30 17:09
 * ====================================
 * Project: ggzy
 * File: Admin.php
 * ====================================
 */

namespace app\admin\logic;
use app\admin\BaseLogic;
use app\common\support\LoginSupport;

class Admin extends BaseLogic
{

    public function _before_save()
    {
        if (isset($this->data['password']) && !empty($this->data['password'])) {
            $this->data['password'] = password($this->data['password']);
        } else {
            unset($this->data['password']);
        }
    }

    public function modifyPassword()
    {
        $user = LoginSupport::getUserInfo();
        $this->data['user_name'] = $user['user_name'];
        $this->data['real_name'] = $user['real_name'];
        $this->data['group_id'] = $user['group_id'];
        $this->data['role_id'] = $user['role_id'];
        $this->data['user_id'] = $user['user_id'];
        return $this->save();
    }

    /**
     * 获取当前管理员信息
     * @param array $field 获取字段
     * @param bool $isRole 是否关联角色表
     * @param bool $isGroup 是否关联组织架构表
     * @return mixed
     */
    public function getManageInfo($field = [], $isRole = true, $isGroup = true)
    {
        if ($isRole) {
            $this->dbModel->setRole(['text as role_name']);
        }
        if ($isGroup) {
            $this->dbModel->setGroup(['text as group_name']);
        }
        $result = $this->dbModel->getManageInfo(['user_id' => LoginSupport::getUserId()], $field);
        formatTime($result);
        return $result;
    }

}