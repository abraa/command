<?php
/**
 * ====================================
 *
 * ====================================
 * Author: 9004396
 * Date: 2017-11-01 16:49
 * ====================================
 * Project: ggzy
 * File: Tree.php
 * ====================================
 */
namespace extend;
class Tree
{
    static $id = 'id';
    static $pid = 'pid';
    static $text = 'title';
    static $children = 'children';

    /**
     * 查找子分类
     * @param array $data
     * @param integer $pid
     * @return array
     */
    public static function findChild(&$data, $pid)
    {
        $child = array();
        if (!empty($data) && is_array($data)) {
            foreach ($data as $v) {
                if ($v[self::$pid] == $pid) {
                    $child[] = $$v;
                }
            }
        }
        return $child;
    }


    /**
     * 查询所有子分类
     * @param array $data 处理数据
     * @param int $pid 父级id
     * @return array
     */
    public static function findAllChild($data, $pid = 0)
    {
        $child = array();
        $temp = current($data);
        if (!isset($temp[self::$children])) {
            $data = self::treeArray($data, $pid);
        }
        if (!empty($data) && is_array($data)) {
            foreach ($data as $key => $item) {
                if (!empty($item[self::$children])) {
                    $child = self::findAllChild($item[self::$children], $pid);
                }
                unset($item[self::$children]);
                $child[] = $item;
            }
        }
        return $child;
    }

    /**
     * 父级是否存在
     * @param array $data
     * @param string $pid
     * @return bool
     */
    public static function parentExists(&$data, $pid)
    {
        if (empty($pid)) {
            return true;
        }
        if (!empty($data) && is_array($data)) {
            foreach ($data as $item) {
                if ($item[self::$id] == $pid) {
                    return true;
                }
            }
        }
        return false;
    }


    /**
     * 返回树形数据
     * @param array $data
     * @param int $pid
     * @return array
     */
    public static function treeArray(array $data, $pid = 0)
    {
        $tree = array();
        if (!empty($data) && is_array($data)) {
            foreach ($data as $item) {
                if ($item[self::$pid] == $pid) {
                    $item[self::$children] = self::treeArray($data, $item[self::$id]);
                    $tree[] = $item;
                }
            }
        }
        return $tree;
    }

    /**
     * 递归反馈选择框选项
     * @param array $data
     * @param int $pid
     * @param int $level
     * @return array|null
     */
    public function getSelect(array $data, $pid = 0, $level = 0)
    {
        if ($level == 10) {
            return null;
        }
        $l = str_repeat("&nbsp;&nbsp;&nbsp;&nbsp;", $level) . 'L';
        static $select = array();
        $select = empty($level) ? array() : $select;
        if (!empty($data) && is_array($data)) {
            foreach ($data as $item) {
                if ($item[self::$pid] == $pid) {
                    //如果当前遍历的ID不为空
                    $item[self::$text] = $l . $item[self::$text];
                    $item['level'] = $level;
                    $select[] = $item;
                    self::getSelect($data, $item[self::$id], $level++); //递归调用
                }
            }
        }
        return $select;
    }
}