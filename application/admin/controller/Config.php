<?php
/**
 * ====================================
 * 配置管理
 * ====================================
 * Author: 9004396
 * Date: 2017-11-24 17:45
 * ====================================
 * Project: ggzy
 * File: Config.php
 * ====================================
 */

namespace app\admin\controller;



use app\admin\BaseController;
use app\common\support\ConfigSupport;

class Config extends BaseController
{
    public function _initialize()
    {
        parent::_initialize();
        //校验权限
        $this->assign('groupList', ConfigSupport::getConfigGroup());
        $this->assign('typeList', ConfigSupport::getConfigType());

    }

    public function setting($config = [])
    {
        if ($this->request->isPost()) {
            $result = $this->logic->setting($config);
            if ($result) {
                $this->success($this->logic->getInfo());
            } else {
                $this->error($this->logic->getError());
            }
        } else {
            $list = $this->logic->getList();
            $this->assign('list', $list);
            return $this->fetch();
        }
    }
}