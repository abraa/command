<?php
 /**
 * ====================================
 * thinkphp5
 * ====================================
 * Author: 1002571
 * Date: 2018/1/11 15:52
 * ====================================
 * File: ValidateSupport.php
 * ====================================
 */

namespace app\common\support;


use extend\Tree;
use think\exception\ClassNotFoundException;

class ValidateSupport {

    /**
     * 判断分类设置是否异常。  不能设置pid为本身id或者子id
     * @param mixed     $value  字段值
     * @param mixed     $rule  验证规则 格式：数据表,字段名,排除ID,主键名
     * @param array     $data  数据
     * @param string    $field  验证字段名
     * @param string    $title  字段描述
     * @return bool
     */
    public static function checkPid($value , $rule , $data = [] , $field = '' , $title = '')
    {
        if (is_string($rule)) {
            $rule = explode(',', $rule);
        }
        if (false !== strpos($rule[0], '\\')) {
            // 指定模型类
            $db = new $rule[0];
        } else {
            try {
                $db = model($rule[0]);
            } catch (ClassNotFoundException $e) {
                $db = db($rule[0]);
            }
        }
        $pk = $db->getPk();
        if ($data[$pk] == $value) {
            return false;
        }
        $MenuData = $db->select();
        if ($MenuData) {
            $MenuData = collection($MenuData)->toArray();
        }
        $result = Tree::findAllChild($MenuData, $data[$pk]);
        $pid = [];
        if (!empty($result)) {
            foreach ($result as $item) {
                $pid[] = $item[$pk];
            }
        }
        return !in_array($value, $pid) ? true : false;
    }
}