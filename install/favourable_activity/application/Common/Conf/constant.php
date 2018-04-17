<?php
/**
 * ====================================
 * 常量配置
 * ====================================
 * Author: 9004396
 * Date: 2016-06-29 14:40
 * ====================================
 * File: constant.php
 * ====================================
 */

define('CHECK_STOCK', true);    //是否检查库存

define('FIND_PASSWORD', 10);    //找回密码次数

/**
* 快钱支付配置 - 9009123
*/
define('TENPAY_ACCOUNT',  '1216032401');                           //帐号
define('TENPAY_SECRET',   '4338911c6bd28f4cc3d3d70b82423160');     //密钥

/**
 * 快钱支付配置 - 9009123
 */
define('KUAIQIAN_ACCOUNT',     '1002325352501');                   //帐号
define('KUAIQIAN_SECRET',      '8TDL8U22RGIK566S');                //密钥

/**
 * 微信配置 - 9009123
 */
define('WECHAT_TOKEN',       'wx18aa74be546ad67f');                //令牌
define('WECHAT_APPID',       'wxee593c44f2572440');                //应用ID
define('WECHAT_APPSECRET',   '83e32221414acd2a4a160b71c9c47dd1');  //应用密钥
define('WECHAT_MACHINE_ID',  '1238246402');                        //商户号
define('WECHAT_PAY_KEY',     '1E51281FFD48CFD328A1A6B3F4F592E6');  //支付密钥


/**
 * 支付宝配置 - 9009123
 */
define('ALIPAY_APPID',        '2016040701274038');                   //应用ID
define('ALIPAY_ACCOUNT',      '1692877300@qq.com');                  //帐号
define('ALIPAY_KEY',          'wv8ogltn0kug857xd11nidynkaob4wuj');   //密钥
define('ALIPAY_PARTNER',      '2088901653758473');                   //合作ID


///* 微信开发者ID */
//define('TOKEN', C('token'));
//define('APPID', C('appid'));
//define('APPSECRET', C('appsecret'));


//define('TOKEN', '3A043015676D137602567AB6999FA2DC');
//define('APPID', 'wxee593c44f2572440');
//define('APPSECRET', '83e32221414acd2a4a160b71c9c47dd1');
/* test */
//define('TOKEN', 'wx18aa74be546ad67f');
//define('APPID', 'wxcbcc985537a7a009');
//define('APPSECRET', '9535d0cd64be2cd5500fb63c63b16db2');

// define('TOKEN', 'wx18aa74be546ad67f');
// define('APPID', 'wx9e0efb00afc3989a');
// define('APPSECRET', 'a711d9d859925538037129e8b6cd8cd4');

/*
*	优惠券类型
*	@Author  9009123 (Lemonice)
*/
define('COUPON_TYPE_COMMON',              0); // 普通
define('COUPON_TYPE_SHIPPING',            1); // 免邮
define('COUPON_TYPE_ENTITY',              2); // 实物
define('COUPON_TYPE_DISCOUNT',            3); // 折扣

/*
*	优惠券发放的方式
*	@Author  9009123 (Lemonice)
*/
define('SEND_BY_USER',              0); // 按用户发放
define('SEND_BY_GOODS',             1); // 按商品发放
define('SEND_BY_ORDER',             2); // 按订单发放
define('SEND_BY_PRINT',             3); // 线下发放
define('SEND_BY_HAND',              4); // 手动领取

/*
*	优惠券类型
*	@Author  9009123 (Lemonice)
*/
define('COUPON_RANGE_ALL_GOODS',              0); // 全部商品
define('COUPON_RANGE_CLASS',                  1); // 指定分类
define('COUPON_RANGE_PACKAGE',                2); // 指定套装
define('COUPON_RANGE_GOODS',                  3); // 指定单品
define('COUPON_RANGE_ACT',                    4); // 指定活动
define('COUPON_RANGE_GOODS_PACKAGE',          5); // 指定单品和套装


/*
*	是否开启在线支付优惠xx元
*	@Author  9009123 (Lemonice)
*/
define('ONLINE_PAYMENT_DISCOUNT', 0); // 0=不开启，1=开启
define('ONLINE_PAYMENT_DISCOUNT_AMOUNT', 10); //优惠券多少钱

/*
*	减库存时机
*	@Author  9009123 (Lemonice)
*/
define('SDT_SHIP',                  0); // 发货时
define('SDT_PLACE',                 1); // 下订单时
/*
*	支付类型
*	@Author  9009123 (Lemonice)
*/
define('PAY_ORDER',                 0); // 订单支付
define('PAY_SURPLUS',               1); // 会员预付款
/*
*	优惠活动所属级别
*	@Author  9009123 (Lemonice)
*/
define('LEVEL_COMMON', 0);   //普通级别
define('LEVEL_MAIN', 1);     //主管级
define('LEVEL_MANAGER', 2);  //经理级

/*
*	购物车商品类型
*	@Author  9009123 (Lemonice)
*/
define('CART_GENERAL_GOODS',        0); // 普通商品
define('CART_GROUP_BUY_GOODS',      1); // 团购商品
define('CART_AUCTION_GOODS',        2); // 拍卖商品
define('CART_SNATCH_GOODS',         3); // 夺宝奇兵
define('CART_EXCHANGE_GOODS',       4); // 积分商城

/**
 * 订单类型
 */
define('GAT_INTEGRAL_BUY',          'integral_buy'); //积分换购
define('CAT_LOTTERY_BUY',           'lottery_buy'); //抽奖活动

/*
*	优惠活动的优惠范围
*	@Author  9009123 (Lemonice)
*/
define('FAR_ALL',                   0); // 全部商品
define('FAR_ALL_PACKAGE',           1); // 全部套装
define('FAR_CATEGORY',              2); // 按分类选择
define('FAR_GOODS',                 3); // 按商品选择
define('FAR_PACKAGE',               4); // 按套装选择


/*
*	优惠活动的优惠方式
*	@Author  9009123 (Lemonice)
*/
define('FAT_GOODS',                 0); // 默认赠送方式
define('FAT_BUY_ADD',               1); // 递增方式（对应商品进行自增：买一送一）
define('FAT_BUY_PRICE',             2); // 享受等价选购（受订购商品金额限制）
define('FAT_BUY_NUM',               3); // 享受限量选购（受订购商品金额限制）
define('FAT_BUY_DISCOUNT',			4); // 享受折扣选购（受订购数量影响）
define('FAT_BUY_DISCOUNT_PRICE',	5); // 享受折扣选购（受订购金额影响）
define('FAT_BUY_NUM_DERATE',		6); // 享受计件折扣或减免（受订购数量影响）
define('FAT_BUY_ONLINE_PAYMENT',    7); // 在线支付赠送优惠品
define('FAT_GIFT_BONUS',    8); //实物券优惠
define('FAT_FULL_GIFT_BONUS',    9); //满赠优惠
define('FAT_SNATCH', 10); //限时抢购
define('FAT_PICKS', 11);  //精选特卖
define('FAT_LIMITED_BUY', 12);  //限量特卖
define('FAT_FULL_MINUS', 13);  //满立减（受订购商品总价限制）

/*
*	订单状态
*	@Author  9009123 (Lemonice)
*/
define('OS_UNCONFIRMED',            0); // 未确认
define('OS_CONFIRMED',              1); // 已确认
define('OS_CANCELED',               2); // 已取消
define('OS_INVALID',                3); // 无效
define('OS_RETURNED',               4); // 退货
//新增状态
define('OS_ABNORMAL',               5); // 异常
define('OS_LOST',                   6); // 丢失
define('OS_ISDELETED', 				99);//假删除标记

/*
*	配送状态
*	@Author  9009123 (Lemonice)
*/
define('SS_UNSHIPPED',              0); // 未发货
define('SS_SHIPPED',                1); // 已发货
define('SS_RECEIVED',               2); // 已收货
define('SS_PREPARING',              3); // 配货中
define('SS_EXPRESSED',              4); // 打单
//新增状态
define('SS_WAITCHECK',              5); // 配货审核中
define('SS_CHECKBACK',              6); // 配货审核退回
define('SS_PRINTPREPARING',         7); // 已打捡货单
define('SS_BALE',                   8); // 已打包
define('SS_OVERSTOCK',              9); // 压单
define('SS_DRUGS_CHECK',            14); //药师审核中
define('SS_DRUGS_CKBACK',           15); //审核退回
define('SS_UNRECEIVED',             16); //未妥投
define('SS_DEPOSTUNUSUAL',          20); // 仓库返回异常
define('SS_REIURNED',               30); // 退货已签收


/*
*	支付状态
*	@Author  9009123 (Lemonice)
*/
define('PS_UNPAYED',                0); // 未付款
define('PS_PAYING',                 1); // 付款中
define('PS_PAYED',                  2); // 已付款
//新增状态
define('PS_PAY_PARI',               3); // 已支付部分货款
define('PS_FPAYED',                 4); // 前台已付款
define('PS_REFUNDING',              5); // 退款中
define('PS_REFUND',                 6); // 已退款
define('PS_UNREFUND',               7); // 退款失败



/*
*	结算状态
*	@Author  9009123 (Lemonice)
*/
define('SE_UNSETTLE',				0); // 未结算
define('SE_SETTLEING',				1); // 结算中
define('SE_SETTLED',				2); // 已结算
/*
*	综合状态
*	@Author  9009123 (Lemonice)
*/
define('CS_AWAIT_PAY',              100); // 待付款：货到付款且已发货且未付款，非货到付款且未付款
define('CS_AWAIT_SHIP',             101); // 待发货：货到付款且未发货，非货到付款且已付款且未发货
define('CS_FINISHED',               102); // 已完成：已确认、已付款、已发货
/*
*	缺货处理
*	@Author  9009123 (Lemonice)
*/
define('OOS_WAIT',                  0); // 等待货物备齐后再发
define('OOS_CANCEL',                1); // 取消订单
define('OOS_CONSULT',               2); // 与店主协商

//会员微信公众号活动类型
define('USER_ACT_SUBSCRIBE',        1); //关注
define('USER_ACT_UNSUBSCRIBE',      2); //取消关注
define('USER_ACT_REPLY',            3); //回复
define('USER_ACT_MENU',             4); //点击菜单

//新增打印接口的KEY
define('APP_ID','151010000');
define('APP_TOKEN','dy7naiRIevfzpXsglqfZgd08g2Aoxmq0');

/*
*	瓷肌新零售 - 售货机开格子状态
*	@Author  9009123 (Lemonice)
*/
define('RETAIL_OPEN_NOT',				0); // 未打开
define('RETAIL_OPEN_YES',				1); // 已打开
define('RETAIL_OPEN_FALSE',				2); // 打开失败

