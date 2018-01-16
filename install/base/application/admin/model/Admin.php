<?php
/**
 * ====================================
 * 管理员模型
 * ====================================
 * Author: 9004396
 * Date: 2017-10-30 17:09
 * ====================================
 * Project: ggzy
 * File: Admin.php
 * ====================================
 */

namespace app\admin\model;
use app\admin\BaseModel;

class Admin extends BaseModel
{
    protected $isRole = false;
    protected $isGroup = false;
    protected $field = [];
    protected $prefix = [];


    public function filter($params)
    {
        $where = [];
        if(!empty($params['keyword'])){
            $where['a.user_name|a.real_name'] = ['like',"%{$params['keyword']}%"];
        }

        if (!empty($params['role_id'])) {
            $where['a.role_id'] = intval($params['role_id']);
        }

        if (!empty($params['group_id'])) {
            $where['a.group_id'] = intval($params['group_id']);
        }

        $this->alias('a')
            ->join('__GROUP__ g', 'a.group_id = g.id', 'left')
            ->join('__ROLE__ r', 'a.role_id = r.id', 'left')
            ->field(['a.user_id', 'a.user_name', 'a.real_name', 'a.sex','a.locked', 'a.group_id', 'a.role_id', 'a.create_time', 'a.update_time', 'a.is_open', 'g.text as group_name', 'r.text as role_name']);

        $this->where($where);
        return $this;
    }

    /**
     * 获取管理员信息
     * @param string $where 查询条件
     * @param array $field 获取字段
     * @param string $prefix 表前缀
     * @return array
     */
    public function getManageInfo($where = '', $field = [], $prefix = 'a')
    {
        if (!empty($where)) {
            $this->where($where);
        }
        if (empty($field) || !is_array($field)) {
            array_push($this->field, $prefix . '.*');
        } else {
            foreach ($field as $item) {
                array_push($this->field, $prefix . '.' . $item);
            }
        }
        $this->alias($prefix);
        if (!empty($this->field)) {
            $this->field($this->field);
        }
        if (!empty($this->isRole)) {
            $this->join('__ROLE__ r', $this->prefix['role'] . '.id=' . $prefix . '.role_id', 'left');
        }
        if (!empty($this->isGroup)) {
            $this->join('__GROUP__ g', $this->prefix['group'] . '.id=' . $prefix . '.group_id', 'left');
        }
        return $this->getRow();
    }

    /**
     * 设置获取角色信息
     * @param array $field 获取字段
     * @param string $prefix 表前缀
     * @return $this
     */
    public function setRole($field = [], $prefix = 'r')
    {
        $this->isRole = true;
        if (!empty($field) && is_array($field)) {
            foreach ($field as &$item) {
                $item = $prefix . '.' . $item;
            }
            $this->prefix['role'] = $prefix;
            $this->field = array_merge($this->field, $field);
        }
        return $this;
    }

    /**
     * 设置获取组织信息
     * @param array $field 获取字段
     * @param string $prefix 表前缀
     * @return $this
     */
    public function setGroup($field = [], $prefix = 'g')
    {
        $this->isGroup = true;
        if (!empty($field) && is_array($field)) {
            foreach ($field as &$item) {
                $item = $prefix . '.' . $item;
            }
            $this->prefix['group'] = $prefix;
            $this->field = array_merge($this->field, $field);
        }
        return $this;
    }

}