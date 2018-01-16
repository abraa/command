<?php
 /**
 * ====================================
 * thinkphp5
 * ====================================
 * Author: 1002571
 * Date: 2018/1/10 17:30
 * ====================================
 * File: Logic.php
 * ====================================
 */

namespace app\admin\logic;


use app\admin\BaseLogic;
use app\common\middleware\PowerMiddleware;
use app\common\support\LoginSupport;
use app\common\support\LogSupport;

class Login extends BaseLogic{

    protected $modelName = false;



    /**
     * 后台登陆
     */
    public function login()
    {
        $username = !empty($this->data['login_name']) ? $this->data['login_name'] : null;
        $password = !empty($this->data['login_password']) ? $this->data['login_password'] : null;
        $verify_code = !empty($this->data['verify_code']) ? $this->data['verify_code'] : null;
        if (empty($username)) {
            $this->err = lang('login_name_lost');
            return false;
        }

        if (empty($password)) {
            $this->err = lang('login_password_lost');
            return false;
        }

        if (config('verify_close')) {
            $verify = false;
            if (!empty($verify_code)) {
                $verify = verify(['code' => $verify_code], true);
            }
            if (empty($verify)) {
                $this->err = lang('verify_code_error');
                return false;
            }
        }

        $where['user_name'] = strip_tags($username);
        $this->setModel('admin');
        $userInfo = $this->dbModel->getManageInfo($where);

        //管理不存在
        if (empty($userInfo)) {
            $this->err = lang('account_not_exists');
            return false;
        }

        //密码不正确
        if ($userInfo['password'] != password($password)) {
            $this->err = lang('password_error');
            return false;
        }
        unset($userInfo['password']);

        //已被锁定
        if ($userInfo['locked'] == 1) {
            $this->err = lang('not_allow_login');
            return false;
        }

        //获取当前用户权限
        $menu = PowerMiddleware::getPower($userInfo);
        $userInfo['menu'] = $menu;
        $userInfo['login_time'] = time();
        LoginSupport::login($userInfo);             //保存登录session
        $logicData = [
            'session_key' => session_id(),
            'last_login_time' => $userInfo['now_login_time'],
            'now_login_time' => $userInfo['login_time'],
            'last_login_ip' => $userInfo['now_login_ip'],
            'now_login_ip' => request()->ip(),
        ];
        $this->dbModel->save($logicData, ['user_id' => intval($userInfo['user_id'])]);
        $this->msg = lang('login') . lang('success');
        LogSupport::adminLog(lang('LOGIN_SUCCESS'),$userInfo['user_id']);
        return true;
    }


    /**
     * 后台退出登陆
     */
    public function logout()
    {
        LoginSupport::logout();
        session_destroy();
        return true;
    }



}
