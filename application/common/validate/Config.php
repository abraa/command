<?php
/**
 * ====================================
 * 配置验证类
 * ====================================
 * Author: 9004396
 * Date: 2017-11-27 10:21
 * ====================================
 * File: Config.php
 * ====================================
 */

namespace app\common\validate;

use think\Validate;

class Config extends Validate
{
    protected $rule = [
        'name' => 'require|unique:config',
        'title' => 'require',
    ];

    protected $message = [
        'name.require' => '{%name_lost}',
        'text.unique' => '{%name_exists}',
        'title' => '{%title_lost}',
    ];

    protected $scene = [
        'edit' =>['name', 'title'],
        'add' => ['name', 'title'],
    ];
}