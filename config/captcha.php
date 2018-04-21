<?php
 /**
 * ====================================
 * 验证码配置项
 * ====================================
 * Author: 1002571
 * Date: 2018/1/19 15:04
 * ====================================
 * File: captcha.php
 * ====================================
 */

return [
    'seKey'    => 'ThinkPHP.CN',
    // 验证码加密密钥
    'codeSet'  => '2345678abcdefhijkmnpqrstuvwxyzABCDEFGHJKLMNPQRTUVWXY',
    // 验证码字符集合
    'expire'   => 1800,
    // 验证码过期时间（s）
    'useZh'    => false,
    // 使用中文验证码
    // 中文验证码字符串
    'useImgBg' => false,
    // 使用背景图片
    'fontSize' => 25,
    // 验证码字体大小(px)
    'useCurve' => true,
    // 是否画混淆曲线
    'useNoise' => true,
    // 是否添加杂点
    'imageH'   => 0,
    // 验证码图片高度
    'imageW'   => 0,
    // 验证码图片宽度
    'length'   => 5,
    // 验证码位数
    'fontttf'  => '',
    // 验证码字体，不设置随机获取
    'bg'       => [243, 251, 254],
    // 背景颜色
    'reset'    => true,
    // 验证成功后是否重置

];