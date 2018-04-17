<?php
/**
 * ====================================
 * 微信打印日志模型
 * ====================================
 * Author: 9004396
 * Date: 2017-07-04 16:25
 * ====================================
 * Project: new.m.chinaskin.cn
 * File: PrintLogModel.class.php
 * ====================================
 */
namespace Cpanel\Model;

use Common\Model\CpanelModel;

class PrintLogModel extends CpanelModel
{
    public function filter($params)
    {
        $keyword =  trim($params['keywords']);
        $where = array();
        if(!empty($keyword)){
            $where['terminal_id'] = $keyword;
        }
        if(!empty($params['start_time']) && !empty($params['end_time'])){
            $start_time =strtotime(trim($params['start_time']));
            $end_time = strtotime(trim($params['end_time']));
            $where['add_time'] = array('between', array($start_time, $end_time));
        }

        $this->order('id desc');
        $this->where($where);
    }
}