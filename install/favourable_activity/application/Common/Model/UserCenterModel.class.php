<?php
/**
 * ====================================
 * 会员中心公共模型
 * ====================================
 * Author: 9004396
 * Date: 2016-06-25 10:25
 * ====================================
 * File: UserCenterModel.class.php
 * ====================================
 */
namespace Common\Model;

class UserCenterModel extends CommonModel{
    public function _initialize(){
        $this->connection = 'USER_CENTER';
        $config = C($this->connection);
        $this->dbName = $config['DB_NAME'];
        $this->tablePrefix = '';
        parent::_initialize();
    }
}