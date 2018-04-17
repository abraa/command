<?php
/**
 * ====================================
 * 微信打印日志
 * ====================================
 * Author: 9004396
 * Date: 2017-07-04 16:19
 * ====================================
 * Project: new.m.chinaskin.cn
 * File: PrintController.class.php
 * ====================================
 */
namespace Cpanel\Controller;

use Common\Controller\CpanelController;

class PrintController extends CpanelController
{
    protected $tableName= 'print_log';

    public function form(){
        $id = I('request.id', 0, 'intval');
        if(empty($id)){
            $this->error('数据不存在');
        }
        $data = $this->dbModel->find($id);
        $data['params'] = json_decode($data['params'],true);
        $data['returndata'] = json_decode($data['returndata'],true);
        $this->assign('data', $data);
        $this->display();
    }
}