<?php
/**
 * ====================================
 * 管理员管理
 * ====================================
 * Author: 9004396
 * Date: 2017-11-22 18:32
 * ====================================
 * Project: ggzy
 * File: Admin.php
 * ====================================
 */

namespace app\admin\controller;

use app\admin\BaseController;

class Admin extends BaseController
{

    public function information()
    {
        if ($this->request->isPost()) {
            $result = $this->logic->modifyPassword();
            if ($result) {
                $this->success($this->logic->getInfo());
            } else {
                $this->error($this->logic->getError());
            }
        } else {
            $info = $this->logic->getManageInfo();
            $this->assign('info', $info);
            return $this->fetch();
        }
    }

    public function log()
    {
        $this->logic = logic('adminLog');
        $this->logic->setData(input());
        return $this->index();
    }

}