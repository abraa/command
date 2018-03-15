<?php
 /**
 * ====================================
 * thinkphp5
 * ====================================
 * Author: 1002571
 * Date: 2018/3/15 17:26
 * ====================================
 * File: Model.php
 * ====================================
 */

namespace app\admin\controller;


use app\admin\BaseController;

class Model extends BaseController{

    public function index(){
        if ($this->request->isAjax()) {
            $data=[];
            return $data;
        }
        return $this->fetch($this->template);
    }
}