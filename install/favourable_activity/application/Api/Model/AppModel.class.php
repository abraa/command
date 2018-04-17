<?php
/**
 * ====================================
 * 接口应用模型
 * ====================================
 * Author: 9004396
 * Date: 2017-04-05 11:14
 * ====================================
 * Project: new.m.chinaskin.cn
 * File: AppModel.class.php
 * ====================================
 */
namespace Api\Model;

use Common\Model\CpanelModel;

class AppModel extends CpanelModel{

    protected $connection ='CPANEL';
    protected $tablePrefix = 'py_';

}
