<?php
/**
 * ====================================
 * 二线客服接口
 * ====================================
 * Author: 9004396
 * Date: 2017-05-09 14:02
 * ====================================
 * Project: new.m.chinaskin.cn
 * File: SecondLineCustomerController.class.php
 * ====================================
 */
namespace Api\Controller;

use Common\Controller\InterfaceController;

class SecondLineCustomerController extends InterfaceController
{
    private $para;

    public function _initialize()
    {
        parent::_initialize(); // TODO: Change the autogenerated stu
        $this->para = json_decode(trim($this->busData), true);
    }


    public function syncBindQrCode()
    {
        $limit = 100;
        if (isset($this->para['limit'])) {
            $limit = $this->para['limit'];
        }
        $bindQrCode = D('Home/CustomerQrcodeBind')->alias('cqb')
            ->join('__CUSTOMER_QRCODE__ AS cq ON cq.id=cqb.cq_id', 'left')
            ->where(array('cqb.sync' => 0))
            ->field('cqb.id,cqb.customer_id,cq.qrcode_id,cq.job_number')
            ->limit($limit)
            ->select();
        if (empty($bindQrCode)) {
            $this->error('S00001');
        } else {
            $this->success($bindQrCode);
        }
    }

    public function sync()
    {
        if (!isset($this->para['ids']) || empty($this->para['ids'])) {
            $this->error('S00001');
        }
        $ids = array();
        if (strpos($this->para['ids'], ',') !== false) {
            $ids = explode(',', $this->para['ids']);
        }else{
            array_push($ids,$this->para['ids']);
        }
        if (empty($ids) || !is_array($ids)) {
            $this->error('S00001');
        }

        $ret = D('Home/CustomerQrcodeBind')->where(array('id' => array('IN',$ids)))->setField('sync',1);
        if ($ret === false){
            $this->error('S00002');
        }else{
            $this->success();
        }
    }
}