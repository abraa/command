<?php
 /**
 * ====================================
 * thinkphp5
 * ====================================
 * Author: 1002571
 * Date: 2018/1/11 14:32
 * ====================================
 * File: Admin.php
 * ====================================
 */

namespace app\common\validate;


use think\Validate;

class Admin extends Validate{
    protected $rule = [
        'group_id' => 'require',
        'role_id' => 'require',
        'user_name' => 'require|unique:admin',
        'real_name' => 'require',
        'password' => 'length:6,32|confirm'
    ];

    protected $message = [
        'group_id' => '{%GROUP_NAME_REQUIRE}',
        'role_id' => '{%ROLE_NAME_REQUIRE}',
        'user_name.require' => '{%USER_NAME_REQUIRE}',
        'user_name.unique' => '{%USER_NAME_EXISTS}',
        'real_name' => '{%REAL_NAME_REQUIRE}',
        'password.length' => '{%PASSWORD_LENGTH_ERROR}',
        'password.confirm' => '{%CONFIRM_PASSWORD_ERROR}',
    ];

    protected $scene = [
        'add' => ['user_name','password', 'role_id'],
        'login' =>  ['user_name','password'],
        'edit' => ['user_name', 'password', 'role_id']
    ];

}