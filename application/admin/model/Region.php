<?php
/**
 * ====================================
 * ggzy
 * ====================================
 * Author: 1002571
 * Date: 2017/11/14 13:56
 * ====================================
 * File: Region.php
 * ====================================
 */

namespace app\admin\model;


use app\admin\BaseModel;

class Region extends BaseModel
{
    /**
     * 查询用户地址详情
     * @param array $where
     * @param string $field
     * @return array
     * @throws \think\Exception
     */
    public function getInfo($where = array(), $field = '*')
    {
        $info = $this->field($field)->where($where)->find();
        return !empty($info) ? $info->toArray() : array();
    }


    public function grid($params = [])
    {
        if(empty($params['type'])){
            $subQuery = $this->alias('s')->where('s.pid=b.id')->fetchSql(true)->count();
            $where = [];
            if (!empty($params['id'])) {
                $where['b.pid'] = (int)$params['id'];
            } else {
                $where['b.pid'] = 0;
            }
            $this->alias('b')
                ->field("b.*,({$subQuery}) as have_children")
                ->where($where);
        }else {
            if (!empty($params['id'])) {
                $where['b.pid'] = (int)$params['id'];
            } else {
                $where['b.pid'] = 0;
            }

            $this->alias('b')
                ->field('b.*')
                ->where($where)
                ->order('b.pid ASC');
        }
        $data = $this->getAll();
        return $data;
    }
}
