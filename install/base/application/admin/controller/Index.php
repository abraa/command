<?php

namespace app\admin\controller;

use app\admin\BaseController;


class Index extends BaseController
{
    protected $allowAction = '*';

    public function index()
    {
        $this->layout(false);
        return $this->fetch();
    }

    public function menu()
    {
        $menuLogic = logic('menu')->getPanelMenu();
        return $menuLogic;
    }

    public function login()
    {
        $this->layout(false);
        if (request()->isAjax()) {
            $params = request()->post();
            $loginLogic = logic('login');
            $loginLogic->setData($params);
            $result = $loginLogic->login();
            if ($result) {
                $this->success($loginLogic->getInfo(), url('index/index'));
            } else {
                $this->error($loginLogic->getError());
            }
        } else {
            return $this->fetch();
        }
    }


    /**
     *  退出登录
     */
    public function logout()
    {
        $login = logic('login');
        $login->logout();
        $this->redirect(url('admin/index/login'));
    }

    public function verify()
    {
        return captcha();
    }
}
