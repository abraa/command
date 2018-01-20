<?php
 /**
 * ====================================
 * ggzy
 * ====================================
 * Author: 1002571
 * Date: 2017/12/6 10:48
 * ====================================
 * File: WechatMenu.php
 * ====================================
 */

namespace app\admin\model;

use app\admin\BaseModel;

class WechatMenu extends BaseModel{
    protected $autoWriteTimestamp = true;

    /**
     * @param array $parmas
     * @return $this
     */
    public function filter($parmas = array()){
        $where = array();
        if(!empty($parmas['level'])){
            switch ($parmas['level']){
                case 1:
                    $level = 0;
                    break;
            }
            $where['pid'] = $level;
        }
        if(isset($parmas['locked'])){
            $where['locked'] = $parmas['locked'];
        }

        if(isset($parmas['account_id'])){
            $where['account_id'] = $parmas['account_id'];
        }

        $this->where($where);
        return $this;
    }


    /**
     * 菜单列表
     * @param array $params
     * @return array|false|\PDOStatement|string|\think\Collection
     */
    public function treegrid($params = array()){
        $this->order("pid DESC, orderby ASC,id DESC");
        $data = $this->getAll();
        if($data){
            foreach($data as $key => $row){
                if(strpos($row['action_param'],'http://') !== false || strpos($row['action_param'],'https://') !== false){
                    $row['action_param'] = htmlspecialchars_decode($row['action_param']);
                }
                $row['level'] = $row['pid'] == 0 ? 0:1;
                $data[$key] = $row;
            }
            $selected = isset($params['selected']) ? $params['selected'] : '';
            $type = isset($params['type']) ? $params['type'] : '';
            tree($data, $selected, $type);
        }
        return $data ? $data : array();
    }
}