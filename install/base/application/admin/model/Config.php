<?php
/**
 * ====================================
 * 配置管理模型
 * ====================================
 * Author: 9004396
 * Date: 2017-10-31 17:58
 * ====================================
 * Project: ggzy
 * File: Config.php
 * ====================================
 */

namespace app\admin\model;

use app\admin\BaseModel;

class Config extends BaseModel
{

    public function filter($params = [])
    {
        $where = [];
        if (isset($params['search_type']) && $params['search_type'] > -1) {
            $where['type'] = $params['search_type'];
        }
        if (isset($params['search_group']) && $params['search_group'] > -1) {
            $where['group'] = $params['search_group'];
        }
        if(!empty($params['keyword'])){
            $where['name|title'] = ['like',"%{$params['keyword']}%"];
        }
        $this->where($where);
        return $this;
    }

    /**
     * 获取配置列表
     * @param string $field 字段名
     * @return array
     */
    public function getConfigList($field = '')
    {
        if (!empty($field)) {
            $this->field($field);
        }
        $result = $this->getAll();
        return $result;
    }
}