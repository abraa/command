<?php
/**
 * ====================================
 * 角色管理校验类
 * ====================================
 * Author: 9004396
 * Date: 2017-11-22 18:03
 * ====================================
 * File: Role.php
 * ====================================
 */

namespace app\common\validate;


use app\common\support\ValidateSupport;
use think\Validate;

class Role extends Validate
{


    protected $rule = [
        'text' => 'require|unique:role',
        'pid' => 'checkPid:role',
    ];

    protected $message = [
        'text.require' => '{%role_name_lost}',
        'text.unique' => '{%role_name_exists}',
        'pid' => '{%parent_error}',
    ];

    protected $scene = [
        'edit' => 'pid',
        'add' => ['text', 'pid'],
    ];

    /**
     * 判断分类设置是否异常。  不能设置pid为本身id或者子id
     * @param mixed     $value  字段值
     * @param mixed     $rule  验证规则 格式：数据表,字段名,排除ID,主键名
     * @param array     $data  数据
     * @return bool
     */
    public function checkPid($value , $rule , $data)
    {
        return ValidateSupport::checkPid($value,$rule,$data);
    }
}