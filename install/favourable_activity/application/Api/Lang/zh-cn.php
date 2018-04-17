<?php
/**
 * ====================================
 * 接口语言包
 * ====================================
 * Author: 9004396
 * Date: 2017-04-01 13:54
 * ====================================
 * Project: new.m.chinaskin.cn
 * File: zh-cn.php
 * ====================================
 */
return array(
    'SUCCESS' => array(
        '10000' => '成功'
    ),
    'SYSTEM' => array(
        '20001' => '参数异常',
        '20002' => '请求超时',
        '20003' => '应用ID或密钥不存在',
        '20004' => '无效的密钥',
        '20005' => '签名不正确',
        '20006' => '程序处理出现未知错误',
    ),
    'REMARK' => array(
        1 => '呼叫中心客户评价积分',
        2 => '会员中心订单评价积分'
    ),

    'STATE' => array(
        2 => '积分商品删除',
        3 => '订单无效',
        4 => '取消订单',
        '-4' => '客服消费'
    ),


    'INTEGRAL' => array(
        'integral' => array(
            '20001' => '评论类型不存在',
            '20002' => '会员不存在',
            '20003' => '积分添加失败',
        ),
        'batchIntegral' => array(
            '20001' => '参数异常',
        ),
        'getIntegralInfo' => array(
            'E00012' => 'lack_of_param',
            'E00013' => 'user_not_exist',
            'E00014' => 'system_error',
            'E00016' => 'user_locked',
            'E00017' => 'user_suspend',
        ),
        'operateUserInt' => array(
            'E00033' => 'state_cannot_empty',
            'E00034' => 'state_not_match_type',
            'E00024' => 'type_error',
            'E00025' => 'lack_order_info',
            'E00026' => 'operate_fail',
            'E00027' => 'repeat_operate',
            'E00028' => 'log_insert_fail',
            'E00029' => 'param_error',
            'E00030' => 'integral_not_enough',
            'E00031' => 'restore_point_gt_left_point',
            'E00032' => 'system_error',
        ),
        'userMarge' => array(
            'E00101' => '主帐号或者子帐号不存在',
            'E00102' => '主帐号的积分无效或者不存在',
            'E00103' => '转移子帐号积分失败',
            'E00104' => '旧主帐号积分出现异常',
            'E00105' => '旧主帐号积分积分返还出错',
            'E00106' => '帐号已经被合并，不可再次合并',
            'E00107' => '子帐号的所有手机号码都未注册',
        ),
        'operateUser' => array(
            'E00151' => '积分状态不允许',
            'E00152' => '积分操作类型不允许',
            'E00153' => '积分异常',
            'E00154' => '订单不存在',
            'E00155' => '操作失败',
            'E00156' => '重复操作',
            'E00157' => '日记记录失败',
            'E00158' => '用户不存在',
            'E00159' => '该用户积分不足操作',
            'E00160' => '该用户积分不足操作',
            'E00161' => '系统错误',
        ),
        'getUserIntegral' => array(
            '20000' => '参数异常',
        ),
        'getIntegralLogList' => array(
            '20000' => '参数异常',
        ),
    ),

    'WXUSER'=>array(
          'getData'=>array(
                'E00011'=>'no_find_data',
          ),
          'setSyn'=>array(
                'E00011' => 'id参数缺失',
                'E00012' => '数据格式错误'
          )
    ),
    'SecondLineCustomer' => array(
        'syncBindQrCode' => array(
            'S00001' => 'data Empty',
        ),
        'sync' => array(
            'S00001' => '参数不存在',
            'S00002' => '更新失败',
        )
    ),

    'PRINT' =>array(
        'addImage' => array(
            'p00001' => '机器编号不存在',
            'p00002' => '数据类型错误',
            'p00003' => '图片路径不存在',
        ),
    ),
);