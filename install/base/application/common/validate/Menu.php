<?php
/**
 * ====================================
 * 菜单管理校验类
 * ====================================
 * Author: 9004396
 * Date: 2017-11-06 14:31
 * ====================================
 * File: Menu.php
 * ====================================
 */

namespace app\common\validate;


use app\common\support\ValidateSupport;
use think\Validate;

class Menu extends Validate
{
    protected $rule = [
        'pid' => 'checkPid:menu',
        'text' => 'require',
        'module' => 'require',
        'controller' => 'require',
        'method' => 'require'
    ];

    protected $message = [
        'pid' => '{%parent_error}',
        'text' => '{%menu_name_lost}',
        'module' => '{%module_name_lost}',
        'controller' => '{%class_name_lost}',
        'method' => '{%method_name_lost}'
    ];


    protected $scene = [
        'edit' => 'pid',
        'add'=>['pid','text','module','controller','method']
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