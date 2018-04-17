<?php
/**
 * ====================================
 * 站点模型
 * ====================================
 * Author: 9004396
 * Date: 2017-05-05 16:26
 * ====================================
 * Project: new.m.chinaskin.cn
 * File: DomainModel.class.php
 * ====================================
 */
namespace Cpanel\Model;
use Common\Model\CpanelModel;

class DomainModel extends CpanelModel{
    protected $_validate = array(
        array('site_id','require','{%SITE_ID_NOT_EXIT}'),
        array('site_id','number','{%SITE_ID_IS_NUMBER}'),
        array('site_id','','{%SITE_ID_UNIQUE}',self::EXISTS_VALIDATE,'unique'),
        array('name','require','{%NAME_NOT_EXIT}'),
        array('domain','require','{%DOMAIN_NOT_EXIT}'),
        array('domain','','{%DOMAIN_UNIQUE}',self::EXISTS_VALIDATE,'unique'),
    );
}