<?php
 /**
 * ====================================
 * thinkphp5
 * ====================================
 * Author: 1002571
 * Date: 2018/1/11 11:09
 * ====================================
 * File: AdminLog.php
 * ====================================
 */

namespace app\admin\logic;


use app\admin\BaseLogic;
use app\common\support\LoginSupport;

class AdminLog extends BaseLogic{

    public function _initialize()
    {
    }


    public function format($data)
    {
        if (!empty($data['rows'])) {
            foreach ($data['rows'] as &$item) {
                $item['user_name'] = LoginSupport::getUserInfo('user_name');
                $item['real_name'] = LoginSupport::getUserInfo('real_name');
            }
        }
        return $data;
    }

}