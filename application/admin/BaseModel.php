<?php
 /**
 * ====================================
 * thinkphp5
 * ====================================
 * Author: 1002571
 * Date: 2018/1/9 14:18
 * ====================================
 * File: BaseModel.php
 * ====================================
 */

namespace app\admin;


use think\Model;
use think\model\Collection as ModelCollection;

class BaseModel extends Model{

    protected $autoWriteTimestamp = true;               //自动写入时间戳

    protected static function init()
    {

    }

    /**
     * 一个Bug model没有实现base函数导致全局范围不能使用, 每次使用Query函数都会new一个新的query
     * @param $query
     * @return mixed
     */
    protected function base($query){
        $this->queryInstance = $query;
        return $query;
    }

    /**
     * 设置当前模型名称
     * @param $name
     */
    public function setName($name){
        $this->name = $name;
    }

    /**
     * 后台grid列表查询
     * @param array $params
     * @return array
     */
    public function grid($params = [])
    {
        $orderBy = isset($params['sort']) ? trim($params['sort']) . ' ' . trim($params['order']) : '';
        $page = isset($params['page']) && $params['page'] > 0 ? intval($params['page']) : 1;
        $pageSize = isset($params['rows']) && $params['rows'] > 0 ? intval($params['rows']) : 0;
        $this->order($orderBy);
        if(empty($pageSize)){
            $data = $this->select();
            if (false !== $data) {
                $data = collection($data)->toArray();
            }
            $total = count($data);
        }else{
            $result = $this->paginate($pageSize, false, ['page' => $page])->toArray();
            $total = $result['total'];
            $data = $result['data'];
        }
//        formatTime($data);
        return [
            'total' => (int)$total,
            'rows' => (empty($data) ? [] : $data),
            'pagecount' => empty($pageSize) ? 1: ceil($total / $pageSize),
        ];
    }

    /**
     * 获取所有记录
     * @param string $where
     * @param string $field
     * @return array|false|\PDOStatement|string|\think\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getAll($where = '', $field = '')
    {
        if (!empty($where)) {
            $this->where($where);
        }
        if (!empty($field)) {
            $this->field($field);
        }

        $data = $this->select();


        if ($data) {
            $data = $data->toArray();
        }
//        formatTime($data);
        return $data;
    }

    /**
     * 获取一条记录
     * @param string $where
     * @param string $field
     * @return array
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getRow($where = '', $field = '')
    {
        if (!empty($where)) {
            $this->where($where);
        }
        if (!empty($field)) {
            $this->field($field);
        }
        $row = $this->find();
        if ($row instanceof Model || $row instanceof ModelCollection) {
            $row = $row->toArray();
        }
//        formatTime($row);
        return $row;
    }
    //TODO...
}