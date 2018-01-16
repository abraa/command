<?php
/**
 * ====================================
 * 角色管理
 * ====================================
 * Author: 9004396
 * Date: 2017-11-22 09:36
 * ====================================
 * Project: ggzy
 * File: Role.php
 * ====================================
 */

namespace app\admin\controller;

use app\admin\BaseController;

class Role extends BaseController
{
    protected $isTree = true;

    public function power()
    {
        $params = input();
        if ($this->request->isAjax()) {
            $powerLogic = logic('power');
            $powerLogic->setData($params);
            $result = $powerLogic->setPower();
            if($result){
                $this->success($powerLogic->getInfo());
            }else{
                $this->error($powerLogic->getError());
            }
        }
        $menuLogic = logic('menu');
        $menuLogic->setData($params);
        $menuLogic->lists($this->isTree);
        return $this->fetch();
    }
}