<?php
/**
 * ====================================
 * 配置管理
 * ====================================
 * Author: Hugo
 * Date: 14-5-20 下午9:58
 * ====================================
 * File: SettingController.class.php
 * ====================================
 */
namespace Cpanel\Controller;

use Common\Controller\CpanelController;

class ConfigController extends CpanelController
{
    protected $tableName = 'Config';

    public function group()
    {
        $evaluate = array();
        $list = $this->dbModel
            ->field('id,name,title,extra,value,remark,type, group')
            ->order('orderby')
            ->select();
        if ($list) {
            $config = array();
            foreach ($list as $row) {
                if (empty($row['group']) && !in_array($row['name'],array('CONFIG_GROUP_LIST','CONFIG_TYPE_LIST'))){
                    $evaluate[$row['name']] = unserialize($row['value']);
                }
                $config[$row['group']][] = $row;
            }
            $this->assign('list', $config);
            $this->assign('evaluate',$evaluate);
        }
        $this->display();
    }

    public function group_save($config)
    {
        #评价积分特殊处理
        if (isset($config['EVALUATE'])) {
            $evaluate = $config['EVALUATE'];
            unset($config['EVALUATE']);
            $this->evaluate($evaluate);
        }

        if ($config && is_array($config)) {
            foreach ($config as $name => $value) {
                $map = array('name' => $name);
                $this->dbModel->where($map)->setField('value', $value);
            }
        }
        S('DB_CONFIG_DATA', null);
        $this->success(L('SAVE') . L('SUCCESS'));
    }

    /**
     * 处理评论积分
     * @param $evaluate
     * @return bool|mixed
     */
    private function evaluate($evaluate)
    {
        if (empty($evaluate)) {
            return false;
        }
        $data = array();
        foreach ($evaluate as $name => $value) {
            $data['name'] = $name;
            $data['title'] = L($name);
            $data['type'] = 2;
            $data['group'] = 0;
            $data['orderby'] = 50;
            $data['value'] = serialize(array_filter($value));
            $unique = $this->dbModel->where(array('name' => $name))->count();
            if($unique > 0){
                $data['update_time'] = time();
                $this->dbModel->where(array('name' => $name))->save($data);
            }else{
                $data['create_time'] = time();
                $this->dbModel->add($data);
            }
        }
    }
}