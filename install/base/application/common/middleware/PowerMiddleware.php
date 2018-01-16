<?php
 /**
 * ====================================
 * thinkphp5
 * ====================================
 * Author: 1002571
 * Date: 2018/1/10 17:53
 * ====================================
 * File: PowerMiddleware.php
 * ====================================
 */

namespace app\common\middleware;


class PowerMiddleware {

    /**
     * 获取用户权限菜单
     * @param $userInfo
     * @return mixed
     */
    public static function getPower($userInfo){
        $powerLogic = logic('power');
        return $powerLogic->getPower($userInfo);
    }
}