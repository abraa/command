<?php
/**
 * ====================================
 * 菜单管理模型
 * ====================================
 * Author: 9004396
 * Date: 2017-11-01 16:59
 * ====================================
 * Project: ggzy
 * File: Menu.php
 * ====================================
 */

namespace app\admin\model;
use app\admin\BaseModel;

class Menu extends BaseModel
{



    /**
     * 设置分组
     * @param string $field
     * @return $this
     */
    public function setGroup($field = ''){
        $this->group($field);
        return $this;
    }
}