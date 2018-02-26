<?php
/**
 * ====================================
 * 配置管理模型
 * ====================================
 * Author: 9004396
 * Date: 2017-10-31 17:58
 * ====================================
 * File: Config.php
 * ====================================
 */

namespace app\admin\logic;

use app\admin\BaseLogic;
use app\common\support\ConfigSupport;
use app\common\support\LogSupport;

class Config extends BaseLogic
{

    public function _after_save()
    {
        ConfigSupport::cacheConfig();
    }

    public function _after_delete()
    {
        ConfigSupport::cacheConfig();
    }

    public function format($data)
    {
        $config_group_list = ConfigSupport::getConfigGroup();
        $config_type_list = ConfigSupport::getConfigType();
        if (!empty($data['rows'])) {
            foreach ($data['rows'] as &$row) {
                $row['group_name'] = empty($config_group_list[$row['group']]) ? lang('SYSTEM') : $config_group_list[$row['group']];
                $row['type_name'] = $config_type_list[$row['type']];
            }
        }
        return $data;
    }


    public function getList()
    {
        $config = [];
        $list = $this->dbModel->getConfigList();
        if (!empty($list)) {
            foreach ($list as $row) {
                if (empty($row['group']) && !in_array($row['name'], array('CONFIG_GROUP_LIST', 'CONFIG_TYPE_LIST'))) {
                    if(strrpos($row['name'],'.')){
                        $name = explode(".",$row['name']);
                        $row['group'] = $name[0];
                    }else{
                        $config[$row['name']] = unserialize($row['value']);
                    }
                }
                $config[$row['group']][] = $row;
            }
        }
        return $config;
    }

    public function setting($params = [])
    {
        if (empty($params) || !is_array($params)) {
            $this->err = lang('SAVE') . lang('ERROR');
            return false;
        }
        foreach ($params as $name => $value) {
            $map = ['name' => $name];
            $this->dbModel->update(['value' => $value], $map);
        }
        ConfigSupport::cacheConfig();
        $this->msg = lang('SAVE') . lang('SUCCESS');
        LogSupport::adminLog(lang('SYSTEM_CONFIG') . lang('EDIT') . lang('SUCCESS'));
        return true;
    }


}