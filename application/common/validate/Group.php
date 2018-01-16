<?php
/**
 * ====================================
 * 组织校验类
 * ====================================
 * Author: 9004396
 * Date: 2017-11-20 14:31
 * ====================================
 * File: Group.php
 * ====================================
 */

namespace app\common\validate;


use app\common\support\ValidateSupport;
use think\Validate;

class Group extends Validate
{

    protected $name = 'panel';

    protected $rule = [
        'text' => 'require|unique:group',
        'pid' => 'checkPid:group',
    ];

    protected $message = [
        'text.require' => '{%GROUP_NAME_EMPTY}',
        'text.unique' => '{%GROUP_NAME_EXISTS}',
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