<?php
/**
 * ====================================
 * 微信用户同步接口
 * ====================================
 * Author: 9006765
 * Date: 2017-04-22 16:22
 * ====================================
 * Project: new.m.chinaskin.cn
 * File: WxuserController.class.php
 * ====================================
 */

namespace Api\Controller;

use Common\Controller\InterfaceController;
use Home\Model\WxCustomerModel;

class WxuserController extends InterfaceController
{

    public function _initialize()
    {
        parent::_initialize(); // TODO: Change the autogenerated stub
        $busStr = I('param.busData', '', 'htmlspecialchars_decode'); //接受业务参数
        $this->para = json_decode(trim($busStr), true);
    }


    public function getData(){
              $limit = 1000;
              if(isset($this->para['limit'])){
                  $limit = $this->para['limit'];
              }
              $data = D('wxCustomer')->field('id,user_name,mobile,provinces,city,district,address,add_time,openid,goods_id,goods_sn,act_no')->where(array('sync'=>0))->limit($limit)->select();
              if(empty($data)){
                  $this->error('E00011');
              }
              $region = array();
              foreach($data as $k => $v){
                  if(!empty($v['provinces'])){
                      $region[] = $v['provinces'];
                  }
                  if(!empty($v['city'])){
                      $region[] = $v['city'];
                  }
                  if(!empty($v['district'])){
                      $region[] = $v['district'];
                  }
              }
              //获取地区名称
              $region_name = D('region')->field('region_id,region_name')->where(array('region_id'=>array('in',$region)))->select();
              $_region_name = array();
              foreach($region_name as $k => $v){
                  $_region_name[$v['region_id']] = $v['region_name'];
              }
              foreach($data as $k => $v){
                  if(!empty($v['provinces'])){
                      $data[$k]['provinces'] = $_region_name[$v['provinces']];
                  }
                  if(!empty($v['city'])){
                      $data[$k]['city'] = $_region_name[$v['city']];
                  }
                  if(!empty($v['district'])){
                      $data[$k]['district'] = $_region_name[$v['district']];
                  }
              }
              $this->success($data);
    }


    public function setSyn(){

        if(!isset($this->para['ids'] ) || empty($this->para['ids'])){
            $this->error('E00011');
        }
        $check_ids = explode(',',$this->para['ids']);
        if(!is_array($check_ids)){
            $this->error('E00012');
        }
        foreach($check_ids as $k => $v){
            if(!is_numeric($v)){
                $this->error('E00012');
            }
        }
        D('wxCustomer')->create(array('sync'=>1));
        D('wxCustomer')->where(array('id'=>array('in',$check_ids)))->save();
        $this->success();
    }



}