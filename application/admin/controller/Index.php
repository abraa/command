<?php

namespace app\admin\controller;

use app\admin\BaseController;


class Index extends BaseController
{
    protected $allowAction = '*';

    public function index()
    {

        $version = db()->query('select VERSION()');

        $info = array(
            'SERVER_SOFTWARE'=>PHP_OS.' '.$_SERVER["SERVER_SOFTWARE"],
            'mysql_get_server_info'=>php_sapi_name(),
            'MYSQL_VERSION' => !empty($version)?$version[0]['VERSION()']:'',
            'upload_max_filesize'=> ini_get('upload_max_filesize'),
            'max_execution_time'=>ini_get('max_execution_time').'秒',
            'disk_free_space'=>round((@disk_free_space(".")/(1024*1024)),2).'M',
        );
        $this->assign('server_info',$info);
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

    /**
     * 显示日志
     */
    public function showLog(){
        $log_name =input('log_name','','trim');
        $remove = input('remove',0,'intval');
        if(!empty($log_name) && file_exists(RUNTIME_PATH. 'log/' . $log_name)){
            $content = file_get_contents(RUNTIME_PATH. 'log/' . $log_name);
            if($remove > 0){
                unlink(RUNTIME_PATH. 'log/' . $log_name);
            }
            echo $content;
        }else{
            echo "文件不存在!";
        }
        exit;
    }
}
