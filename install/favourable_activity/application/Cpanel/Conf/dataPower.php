<?php
/**
 * ====================================
 * 配置文件
 * ====================================
 * Author: 9009123 (Lemonice)
 * Date: 2017-04-25 15:10
 * ====================================
 * Project: new.m.chinaskin.cn
 * File: data_power.php
 * ====================================
 */
/*
 * //权限设置示例
return array(
    'WechatAccount'=>array(
        'name'=>'微信公众号',
        'type'=>1,  //选择方式，0=单选，1=多选,
        'is_tree'=>1,  //是否用树形显示，0=否，1=是，如果是单选则此项无效
        'remark'=>'',  //备注，会显示在前端
        'value'=>array(  //可选择的值,如果不定义则会调用D('DataPower')->getPowerAllValue('WechatAccount')获取对应格式的值
            array('id'=>3,'text'=>'瓷肌Korea'),  //id=保存到库的值,text=名称
            array('id'=>2,'text'=>'瓷肌定妆服务号'),
        ),
    ),
);
*/
return array(
    'WechatAccount'=>array(
        'name'=>'微信公众号',
        'type'=>1,
        'is_tree'=>0,
        'remark'=>'针对微信公众号设置的权限，不设置则可操作所有公众号',
    ),
);