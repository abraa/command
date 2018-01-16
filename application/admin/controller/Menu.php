<?php
/**
 * ====================================
 * 菜单管理
 * ====================================
 * Author: 9004396
 * Date: 2017-11-03 14:07
 * ====================================
 * Project: ggzy
 * File: Menu.php
 * ====================================
 */

namespace app\admin\controller;

use app\admin\BaseController;

class Menu extends BaseController
{
    protected $isTree = true;


    public function getSearchData()
    {
        return $this->logic->getSearchData();
    }


    public function icon()
    {
       return $this->fetch();
    }
}