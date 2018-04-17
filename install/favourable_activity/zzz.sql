/*
Navicat MySQL Data Transfer

Source Server         : localhost
Source Server Version : 50540
Source Host           : localhost:3306
Source Database       : zzz

Target Server Type    : MYSQL
Target Server Version : 50540
File Encoding         : 65001

Date: 2018-04-17 10:28:32
*/

SET FOREIGN_KEY_CHECKS=0;

-- ----------------------------
-- Table structure for `ecs_account_log`
-- ----------------------------
DROP TABLE IF EXISTS `ecs_account_log`;
CREATE TABLE `ecs_account_log` (
  `log_id` mediumint(8) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` mediumint(8) unsigned NOT NULL,
  `user_money` decimal(10,2) NOT NULL,
  `frozen_money` decimal(10,2) NOT NULL,
  `rank_points` mediumint(9) NOT NULL,
  `pay_points` mediumint(9) NOT NULL,
  `change_time` int(10) unsigned NOT NULL,
  `change_desc` varchar(255) NOT NULL,
  `change_type` tinyint(3) unsigned NOT NULL,
  PRIMARY KEY (`log_id`),
  KEY `user_id` (`user_id`) USING BTREE
) ENGINE=MyISAM AUTO_INCREMENT=38 DEFAULT CHARSET=utf8;

-- ----------------------------
-- Records of ecs_account_log
-- ----------------------------

-- ----------------------------
-- Table structure for `ecs_ad`
-- ----------------------------
DROP TABLE IF EXISTS `ecs_ad`;
CREATE TABLE `ecs_ad` (
  `ad_id` smallint(5) unsigned NOT NULL AUTO_INCREMENT,
  `position_id` smallint(5) unsigned NOT NULL DEFAULT '0',
  `media_type` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `ad_name` varchar(60) NOT NULL DEFAULT '',
  `ad_link` varchar(255) NOT NULL DEFAULT '',
  `ad_code` text NOT NULL,
  `start_time` int(11) NOT NULL DEFAULT '0',
  `end_time` int(11) NOT NULL DEFAULT '0',
  `link_man` varchar(60) NOT NULL DEFAULT '',
  `link_email` varchar(60) NOT NULL DEFAULT '',
  `link_phone` varchar(60) NOT NULL DEFAULT '',
  `click_count` mediumint(8) unsigned NOT NULL DEFAULT '0',
  `enabled` tinyint(3) unsigned NOT NULL DEFAULT '1',
  PRIMARY KEY (`ad_id`),
  KEY `position_id` (`position_id`) USING BTREE,
  KEY `enabled` (`enabled`) USING BTREE
) ENGINE=MyISAM AUTO_INCREMENT=19 DEFAULT CHARSET=utf8;

-- ----------------------------
-- Records of ecs_ad
-- ----------------------------

-- ----------------------------
-- Table structure for `ecs_admin_action`
-- ----------------------------
DROP TABLE IF EXISTS `ecs_admin_action`;
CREATE TABLE `ecs_admin_action` (
  `action_id` tinyint(3) unsigned NOT NULL AUTO_INCREMENT,
  `parent_id` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `action_code` varchar(20) NOT NULL DEFAULT '',
  `relevance` varchar(20) NOT NULL DEFAULT '',
  PRIMARY KEY (`action_id`),
  KEY `parent_id` (`parent_id`) USING BTREE
) ENGINE=MyISAM AUTO_INCREMENT=142 DEFAULT CHARSET=utf8;

-- ----------------------------
-- Records of ecs_admin_action
-- ----------------------------

-- ----------------------------
-- Table structure for `ecs_admin_log`
-- ----------------------------
DROP TABLE IF EXISTS `ecs_admin_log`;
CREATE TABLE `ecs_admin_log` (
  `log_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `log_time` int(10) unsigned NOT NULL DEFAULT '0',
  `user_id` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `log_info` varchar(255) NOT NULL DEFAULT '',
  `ip_address` varchar(15) NOT NULL DEFAULT '',
  PRIMARY KEY (`log_id`),
  KEY `log_time` (`log_time`) USING BTREE,
  KEY `user_id` (`user_id`) USING BTREE
) ENGINE=MyISAM AUTO_INCREMENT=2852 DEFAULT CHARSET=utf8;

-- ----------------------------
-- Records of ecs_admin_log
-- ----------------------------

-- ----------------------------
-- Table structure for `ecs_admin_message`
-- ----------------------------
DROP TABLE IF EXISTS `ecs_admin_message`;
CREATE TABLE `ecs_admin_message` (
  `message_id` smallint(5) unsigned NOT NULL AUTO_INCREMENT,
  `sender_id` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `receiver_id` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `sent_time` int(11) unsigned NOT NULL DEFAULT '0',
  `read_time` int(11) unsigned NOT NULL DEFAULT '0',
  `readed` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `deleted` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `title` varchar(150) NOT NULL DEFAULT '',
  `message` text NOT NULL,
  PRIMARY KEY (`message_id`),
  KEY `sender_id` (`sender_id`,`receiver_id`) USING BTREE,
  KEY `receiver_id` (`receiver_id`) USING BTREE
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- ----------------------------
-- Records of ecs_admin_message
-- ----------------------------

-- ----------------------------
-- Table structure for `ecs_admin_user`
-- ----------------------------
DROP TABLE IF EXISTS `ecs_admin_user`;
CREATE TABLE `ecs_admin_user` (
  `user_id` smallint(5) unsigned NOT NULL AUTO_INCREMENT,
  `user_name` varchar(60) NOT NULL DEFAULT '',
  `email` varchar(60) NOT NULL DEFAULT '',
  `password` varchar(32) NOT NULL DEFAULT '',
  `add_time` int(11) NOT NULL DEFAULT '0',
  `last_login` int(11) NOT NULL DEFAULT '0',
  `last_ip` varchar(15) NOT NULL DEFAULT '',
  `action_list` text NOT NULL,
  `nav_list` text NOT NULL,
  `lang_type` varchar(50) NOT NULL DEFAULT '',
  `agency_id` smallint(5) unsigned NOT NULL,
  `suppliers_id` smallint(5) unsigned DEFAULT '0',
  `todolist` longtext,
  `role_id` smallint(5) DEFAULT NULL,
  PRIMARY KEY (`user_id`),
  KEY `user_name` (`user_name`) USING BTREE,
  KEY `agency_id` (`agency_id`) USING BTREE
) ENGINE=MyISAM AUTO_INCREMENT=15 DEFAULT CHARSET=utf8;

-- ----------------------------
-- Records of ecs_admin_user
-- ----------------------------

-- ----------------------------
-- Table structure for `ecs_adsense`
-- ----------------------------
DROP TABLE IF EXISTS `ecs_adsense`;
CREATE TABLE `ecs_adsense` (
  `from_ad` smallint(5) NOT NULL DEFAULT '0',
  `referer` varchar(255) NOT NULL DEFAULT '',
  `clicks` int(10) unsigned NOT NULL DEFAULT '0',
  KEY `from_ad` (`from_ad`) USING BTREE
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- ----------------------------
-- Records of ecs_adsense
-- ----------------------------

-- ----------------------------
-- Table structure for `ecs_ad_custom`
-- ----------------------------
DROP TABLE IF EXISTS `ecs_ad_custom`;
CREATE TABLE `ecs_ad_custom` (
  `ad_id` mediumint(8) unsigned NOT NULL AUTO_INCREMENT,
  `ad_type` tinyint(1) unsigned NOT NULL DEFAULT '1',
  `ad_name` varchar(60) DEFAULT NULL,
  `add_time` int(10) unsigned NOT NULL DEFAULT '0',
  `content` mediumtext,
  `url` varchar(255) DEFAULT NULL,
  `ad_status` tinyint(3) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`ad_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- ----------------------------
-- Records of ecs_ad_custom
-- ----------------------------

-- ----------------------------
-- Table structure for `ecs_ad_position`
-- ----------------------------
DROP TABLE IF EXISTS `ecs_ad_position`;
CREATE TABLE `ecs_ad_position` (
  `position_id` tinyint(3) unsigned NOT NULL AUTO_INCREMENT,
  `position_name` varchar(60) NOT NULL DEFAULT '',
  `ad_width` smallint(5) unsigned NOT NULL DEFAULT '0',
  `ad_height` smallint(5) unsigned NOT NULL DEFAULT '0',
  `position_desc` varchar(255) NOT NULL DEFAULT '',
  `position_style` text NOT NULL,
  PRIMARY KEY (`position_id`)
) ENGINE=MyISAM AUTO_INCREMENT=11 DEFAULT CHARSET=utf8;

-- ----------------------------
-- Records of ecs_ad_position
-- ----------------------------

-- ----------------------------
-- Table structure for `ecs_affiliate_log`
-- ----------------------------
DROP TABLE IF EXISTS `ecs_affiliate_log`;
CREATE TABLE `ecs_affiliate_log` (
  `log_id` mediumint(8) NOT NULL AUTO_INCREMENT,
  `order_id` mediumint(8) NOT NULL,
  `time` int(10) NOT NULL,
  `user_id` mediumint(8) NOT NULL,
  `user_name` varchar(60) DEFAULT NULL,
  `money` decimal(10,2) NOT NULL DEFAULT '0.00',
  `point` int(10) NOT NULL DEFAULT '0',
  `separate_type` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`log_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- ----------------------------
-- Records of ecs_affiliate_log
-- ----------------------------

-- ----------------------------
-- Table structure for `ecs_agency`
-- ----------------------------
DROP TABLE IF EXISTS `ecs_agency`;
CREATE TABLE `ecs_agency` (
  `agency_id` smallint(5) unsigned NOT NULL AUTO_INCREMENT,
  `agency_name` varchar(255) NOT NULL,
  `agency_desc` text NOT NULL,
  PRIMARY KEY (`agency_id`),
  KEY `agency_name` (`agency_name`) USING BTREE
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- ----------------------------
-- Records of ecs_agency
-- ----------------------------

-- ----------------------------
-- Table structure for `ecs_area_region`
-- ----------------------------
DROP TABLE IF EXISTS `ecs_area_region`;
CREATE TABLE `ecs_area_region` (
  `shipping_area_id` smallint(5) unsigned NOT NULL DEFAULT '0',
  `region_id` smallint(5) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`shipping_area_id`,`region_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- ----------------------------
-- Records of ecs_area_region
-- ----------------------------

-- ----------------------------
-- Table structure for `ecs_article`
-- ----------------------------
DROP TABLE IF EXISTS `ecs_article`;
CREATE TABLE `ecs_article` (
  `article_id` mediumint(8) unsigned NOT NULL AUTO_INCREMENT,
  `cat_id` smallint(5) NOT NULL DEFAULT '0',
  `title` varchar(150) NOT NULL DEFAULT '',
  `content` longtext NOT NULL,
  `author` varchar(30) NOT NULL DEFAULT '',
  `author_email` varchar(60) NOT NULL DEFAULT '',
  `keywords` varchar(255) NOT NULL DEFAULT '',
  `article_type` tinyint(1) unsigned NOT NULL DEFAULT '2',
  `is_open` tinyint(1) unsigned NOT NULL DEFAULT '1',
  `add_time` int(10) unsigned NOT NULL DEFAULT '0',
  `thumb_url` varchar(255) DEFAULT NULL,
  `file_url` varchar(255) NOT NULL DEFAULT '',
  `video_name` varchar(255) NOT NULL COMMENT '视频文件名',
  `open_type` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `link` varchar(255) NOT NULL DEFAULT '',
  `description` varchar(255) DEFAULT NULL,
  `author_age` tinyint(3) NOT NULL COMMENT '作者年龄',
  `fav_count` mediumint(8) NOT NULL DEFAULT '0' COMMENT '文章点赞数量',
  `wx_num` varchar(150) NOT NULL DEFAULT '' COMMENT '微信号',
  `view_num` mediumint(8) NOT NULL DEFAULT '0' COMMENT '浏览数',
  `is_show_relate` tinyint(1) NOT NULL COMMENT '是否显示关联',
  `is_prcode` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '显示模板二维码模块，0为不显示，默认为1',
  `topimg` varchar(255) NOT NULL DEFAULT '' COMMENT '头部图片',
  `diimg` varchar(255) NOT NULL DEFAULT '' COMMENT '底部图片',
  `tishi` varchar(255) NOT NULL DEFAULT '' COMMENT '提示语',
  `banquan` varchar(255) NOT NULL DEFAULT '' COMMENT '版权',
  `dizhi` varchar(255) NOT NULL DEFAULT '' COMMENT '地址',
  `piao` varchar(255) NOT NULL DEFAULT '' COMMENT 'SEM头部飘浮语',
  PRIMARY KEY (`article_id`),
  KEY `cat_id` (`cat_id`) USING BTREE
) ENGINE=MyISAM AUTO_INCREMENT=1621 DEFAULT CHARSET=utf8;

-- ----------------------------
-- Records of ecs_article
-- ----------------------------

-- ----------------------------
-- Table structure for `ecs_article_cat`
-- ----------------------------
DROP TABLE IF EXISTS `ecs_article_cat`;
CREATE TABLE `ecs_article_cat` (
  `cat_id` smallint(5) NOT NULL AUTO_INCREMENT,
  `cat_name` varchar(255) NOT NULL DEFAULT '',
  `cat_type` tinyint(1) unsigned NOT NULL DEFAULT '1',
  `keywords` varchar(255) NOT NULL DEFAULT '',
  `cat_desc` varchar(255) NOT NULL DEFAULT '',
  `sort_order` tinyint(3) unsigned NOT NULL DEFAULT '50',
  `show_in_nav` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `parent_id` smallint(5) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`cat_id`),
  KEY `cat_type` (`cat_type`) USING BTREE,
  KEY `sort_order` (`sort_order`) USING BTREE,
  KEY `parent_id` (`parent_id`) USING BTREE
) ENGINE=MyISAM AUTO_INCREMENT=67 DEFAULT CHARSET=utf8;

-- ----------------------------
-- Records of ecs_article_cat
-- ----------------------------

-- ----------------------------
-- Table structure for `ecs_article_comment`
-- ----------------------------
DROP TABLE IF EXISTS `ecs_article_comment`;
CREATE TABLE `ecs_article_comment` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `article_id` int(11) NOT NULL DEFAULT '0' COMMENT '文章id',
  `username` char(16) NOT NULL DEFAULT '' COMMENT '用户名',
  `content` varchar(255) NOT NULL DEFAULT '' COMMENT '评论内容',
  `add_time` char(12) NOT NULL DEFAULT '',
  `ymcontent` varchar(255) NOT NULL DEFAULT '' COMMENT '引用评论内容，评论',
  PRIMARY KEY (`id`),
  KEY `index_article_id` (`article_id`) USING BTREE
) ENGINE=MyISAM AUTO_INCREMENT=1783 DEFAULT CHARSET=utf8 COMMENT='文章评论管理表';

-- ----------------------------
-- Records of ecs_article_comment
-- ----------------------------

-- ----------------------------
-- Table structure for `ecs_article_like_log`
-- ----------------------------
DROP TABLE IF EXISTS `ecs_article_like_log`;
CREATE TABLE `ecs_article_like_log` (
  `id` mediumint(9) NOT NULL AUTO_INCREMENT,
  `article_id` mediumint(9) NOT NULL COMMENT '文章id',
  `key` varchar(32) NOT NULL COMMENT '识别用户键',
  `ip` varchar(15) NOT NULL,
  `time` int(11) NOT NULL COMMENT '加入时间',
  `status` tinyint(4) NOT NULL COMMENT '1有效，2为无效',
  `updatetime` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=414655 DEFAULT CHARSET=utf8;

-- ----------------------------
-- Records of ecs_article_like_log
-- ----------------------------

-- ----------------------------
-- Table structure for `ecs_article_sem`
-- ----------------------------
DROP TABLE IF EXISTS `ecs_article_sem`;
CREATE TABLE `ecs_article_sem` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `article_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '文章id',
  `title` varchar(255) NOT NULL DEFAULT '' COMMENT '小标题',
  `uname` char(8) NOT NULL DEFAULT '' COMMENT '用户名字',
  `age` char(2) DEFAULT '' COMMENT '年龄',
  `zhiye` char(20) NOT NULL DEFAULT '' COMMENT '职业',
  `tedian` varchar(255) NOT NULL DEFAULT '' COMMENT '使用前特点',
  `sydesc` text NOT NULL COMMENT '使用后描述',
  `filepath` varchar(64) NOT NULL DEFAULT '' COMMENT '使用效果图片路径',
  `fileafter` varchar(255) NOT NULL COMMENT '使用后图片',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=853 DEFAULT CHARSET=utf8;

-- ----------------------------
-- Records of ecs_article_sem
-- ----------------------------

-- ----------------------------
-- Table structure for `ecs_article_weixin`
-- ----------------------------
DROP TABLE IF EXISTS `ecs_article_weixin`;
CREATE TABLE `ecs_article_weixin` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `article_id` int(6) NOT NULL DEFAULT '0' COMMENT '文章Id',
  `wx_pic` varchar(255) NOT NULL DEFAULT '' COMMENT '微信二维码图片路径',
  `wx_zh` char(20) NOT NULL DEFAULT '' COMMENT '微信帐号',
  PRIMARY KEY (`id`),
  KEY `article_id` (`article_id`) USING BTREE
) ENGINE=MyISAM AUTO_INCREMENT=4807 DEFAULT CHARSET=utf8 COMMENT='微信二维码图片存放表';

-- ----------------------------
-- Records of ecs_article_weixin
-- ----------------------------

-- ----------------------------
-- Table structure for `ecs_attribute`
-- ----------------------------
DROP TABLE IF EXISTS `ecs_attribute`;
CREATE TABLE `ecs_attribute` (
  `attr_id` smallint(5) unsigned NOT NULL AUTO_INCREMENT,
  `cat_id` smallint(5) unsigned NOT NULL DEFAULT '0',
  `attr_name` varchar(60) NOT NULL DEFAULT '',
  `attr_input_type` tinyint(1) unsigned NOT NULL DEFAULT '1',
  `attr_type` tinyint(1) unsigned NOT NULL DEFAULT '1',
  `attr_values` text NOT NULL,
  `attr_index` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `sort_order` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `is_linked` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `attr_group` tinyint(1) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`attr_id`),
  KEY `cat_id` (`cat_id`) USING BTREE
) ENGINE=MyISAM AUTO_INCREMENT=229 DEFAULT CHARSET=utf8;

-- ----------------------------
-- Records of ecs_attribute
-- ----------------------------

-- ----------------------------
-- Table structure for `ecs_auction_log`
-- ----------------------------
DROP TABLE IF EXISTS `ecs_auction_log`;
CREATE TABLE `ecs_auction_log` (
  `log_id` mediumint(8) unsigned NOT NULL AUTO_INCREMENT,
  `act_id` mediumint(8) unsigned NOT NULL,
  `bid_user` mediumint(8) unsigned NOT NULL,
  `bid_price` decimal(10,2) unsigned NOT NULL,
  `bid_time` int(10) unsigned NOT NULL,
  PRIMARY KEY (`log_id`),
  KEY `act_id` (`act_id`) USING BTREE
) ENGINE=MyISAM AUTO_INCREMENT=2 DEFAULT CHARSET=utf8;

-- ----------------------------
-- Records of ecs_auction_log
-- ----------------------------

-- ----------------------------
-- Table structure for `ecs_auto_manage`
-- ----------------------------
DROP TABLE IF EXISTS `ecs_auto_manage`;
CREATE TABLE `ecs_auto_manage` (
  `item_id` mediumint(8) NOT NULL,
  `type` varchar(10) NOT NULL,
  `starttime` int(10) NOT NULL,
  `endtime` int(10) NOT NULL,
  PRIMARY KEY (`item_id`,`type`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- ----------------------------
-- Records of ecs_auto_manage
-- ----------------------------

-- ----------------------------
-- Table structure for `ecs_back_goods`
-- ----------------------------
DROP TABLE IF EXISTS `ecs_back_goods`;
CREATE TABLE `ecs_back_goods` (
  `rec_id` mediumint(8) unsigned NOT NULL AUTO_INCREMENT,
  `back_id` mediumint(8) unsigned DEFAULT '0',
  `goods_id` mediumint(8) unsigned NOT NULL DEFAULT '0',
  `product_id` mediumint(8) unsigned NOT NULL DEFAULT '0',
  `product_sn` varchar(60) DEFAULT NULL,
  `goods_name` varchar(120) DEFAULT NULL,
  `brand_name` varchar(60) DEFAULT NULL,
  `goods_sn` varchar(60) DEFAULT NULL,
  `is_real` tinyint(1) unsigned DEFAULT '0',
  `send_number` smallint(5) unsigned DEFAULT '0',
  `goods_attr` text,
  PRIMARY KEY (`rec_id`),
  KEY `back_id` (`back_id`) USING BTREE,
  KEY `goods_id` (`goods_id`) USING BTREE
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- ----------------------------
-- Records of ecs_back_goods
-- ----------------------------

-- ----------------------------
-- Table structure for `ecs_back_order`
-- ----------------------------
DROP TABLE IF EXISTS `ecs_back_order`;
CREATE TABLE `ecs_back_order` (
  `back_id` mediumint(8) unsigned NOT NULL AUTO_INCREMENT,
  `delivery_sn` varchar(20) NOT NULL,
  `order_sn` varchar(20) NOT NULL,
  `order_id` mediumint(8) unsigned NOT NULL DEFAULT '0',
  `invoice_no` varchar(50) DEFAULT NULL,
  `add_time` int(10) unsigned DEFAULT '0',
  `shipping_id` tinyint(3) unsigned DEFAULT '0',
  `shipping_name` varchar(120) DEFAULT NULL,
  `user_id` mediumint(8) unsigned DEFAULT '0',
  `action_user` varchar(30) DEFAULT NULL,
  `consignee` varchar(60) DEFAULT NULL,
  `address` varchar(250) DEFAULT NULL,
  `country` smallint(5) unsigned DEFAULT '0',
  `province` smallint(5) unsigned DEFAULT '0',
  `city` smallint(5) unsigned DEFAULT '0',
  `district` smallint(5) unsigned DEFAULT '0',
  `sign_building` varchar(120) DEFAULT NULL,
  `email` varchar(60) DEFAULT NULL,
  `zipcode` varchar(60) DEFAULT NULL,
  `tel` varchar(60) DEFAULT NULL,
  `mobile` varchar(60) DEFAULT NULL,
  `best_time` varchar(120) DEFAULT NULL,
  `postscript` varchar(255) DEFAULT NULL,
  `how_oos` varchar(120) DEFAULT NULL,
  `insure_fee` decimal(10,2) unsigned DEFAULT '0.00',
  `shipping_fee` decimal(10,2) unsigned DEFAULT '0.00',
  `update_time` int(10) unsigned DEFAULT '0',
  `suppliers_id` smallint(5) DEFAULT '0',
  `status` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `return_time` int(10) unsigned DEFAULT '0',
  `agency_id` smallint(5) unsigned DEFAULT '0',
  PRIMARY KEY (`back_id`),
  KEY `user_id` (`user_id`) USING BTREE,
  KEY `order_id` (`order_id`) USING BTREE
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- ----------------------------
-- Records of ecs_back_order
-- ----------------------------

-- ----------------------------
-- Table structure for `ecs_bind_user`
-- ----------------------------
DROP TABLE IF EXISTS `ecs_bind_user`;
CREATE TABLE `ecs_bind_user` (
  `bind_id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '主键id',
  `openid` char(32) NOT NULL DEFAULT '' COMMENT '微信openid',
  `mobile` char(60) NOT NULL COMMENT '手机号码',
  `nickname` varchar(100) NOT NULL DEFAULT '' COMMENT '用户的微信昵称',
  `sex` tinyint(1) unsigned NOT NULL DEFAULT '1' COMMENT '性别1：男，2：女',
  `language` char(10) NOT NULL DEFAULT '' COMMENT '语言',
  `city` varchar(50) NOT NULL DEFAULT '' COMMENT '城市',
  `province` varchar(50) NOT NULL DEFAULT '' COMMENT '省份',
  `country` varchar(50) NOT NULL DEFAULT '' COMMENT '国家',
  `headimgurl` varchar(500) NOT NULL DEFAULT '' COMMENT '头像链接',
  `remark` varchar(100) NOT NULL DEFAULT '' COMMENT '备注',
  `subscribe` tinyint(1) unsigned NOT NULL DEFAULT '1' COMMENT '关注状态，1为关注，0为取消关注',
  `is_merge_integral` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '是否已经合并过积分（签到）',
  `add_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '首次关注时间',
  `cancel_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '取消关注时间',
  `subscribe_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '最近关注时间（可能是第二次以上关注）',
  `points_left` int(10) NOT NULL DEFAULT '0' COMMENT '当前剩余的有效积分',
  `wechatAcountId` smallint(5) unsigned NOT NULL DEFAULT '0' COMMENT '对应的公众帐号',
  `update_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
  PRIMARY KEY (`bind_id`),
  UNIQUE KEY `only_openid` (`openid`) USING BTREE
) ENGINE=MyISAM AUTO_INCREMENT=18 DEFAULT CHARSET=utf8 COMMENT='微信关注日志';

-- ----------------------------
-- Records of ecs_bind_user
-- ----------------------------

-- ----------------------------
-- Table structure for `ecs_bonus_type`
-- ----------------------------
DROP TABLE IF EXISTS `ecs_bonus_type`;
CREATE TABLE `ecs_bonus_type` (
  `type_id` smallint(5) unsigned NOT NULL AUTO_INCREMENT,
  `type_name` varchar(60) NOT NULL DEFAULT '',
  `type_money` decimal(10,2) NOT NULL DEFAULT '0.00',
  `send_type` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `min_amount` decimal(10,2) unsigned NOT NULL DEFAULT '0.00',
  `max_amount` decimal(10,2) unsigned NOT NULL DEFAULT '0.00',
  `send_start_date` int(11) NOT NULL DEFAULT '0',
  `send_end_date` int(11) NOT NULL DEFAULT '0',
  `use_start_date` int(11) NOT NULL DEFAULT '0',
  `use_end_date` int(11) NOT NULL DEFAULT '0',
  `min_goods_amount` decimal(10,2) unsigned NOT NULL DEFAULT '0.00',
  PRIMARY KEY (`type_id`)
) ENGINE=MyISAM AUTO_INCREMENT=9 DEFAULT CHARSET=utf8;

-- ----------------------------
-- Records of ecs_bonus_type
-- ----------------------------

-- ----------------------------
-- Table structure for `ecs_booking_goods`
-- ----------------------------
DROP TABLE IF EXISTS `ecs_booking_goods`;
CREATE TABLE `ecs_booking_goods` (
  `rec_id` mediumint(8) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` mediumint(8) unsigned NOT NULL DEFAULT '0',
  `email` varchar(60) NOT NULL DEFAULT '',
  `link_man` varchar(60) NOT NULL DEFAULT '',
  `tel` varchar(60) NOT NULL DEFAULT '',
  `goods_id` mediumint(8) unsigned NOT NULL DEFAULT '0',
  `goods_desc` varchar(255) NOT NULL DEFAULT '',
  `goods_number` smallint(5) unsigned NOT NULL DEFAULT '0',
  `booking_time` int(10) unsigned NOT NULL DEFAULT '0',
  `is_dispose` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `dispose_user` varchar(30) NOT NULL DEFAULT '',
  `dispose_time` int(10) unsigned NOT NULL DEFAULT '0',
  `dispose_note` varchar(255) NOT NULL DEFAULT '',
  PRIMARY KEY (`rec_id`),
  KEY `user_id` (`user_id`) USING BTREE
) ENGINE=MyISAM AUTO_INCREMENT=3 DEFAULT CHARSET=utf8;

-- ----------------------------
-- Records of ecs_booking_goods
-- ----------------------------

-- ----------------------------
-- Table structure for `ecs_brand`
-- ----------------------------
DROP TABLE IF EXISTS `ecs_brand`;
CREATE TABLE `ecs_brand` (
  `brand_id` smallint(5) unsigned NOT NULL AUTO_INCREMENT,
  `brand_name` varchar(60) NOT NULL DEFAULT '',
  `brand_logo` varchar(80) NOT NULL DEFAULT '',
  `brand_desc` text NOT NULL,
  `site_url` varchar(255) NOT NULL DEFAULT '',
  `sort_order` tinyint(3) unsigned NOT NULL DEFAULT '50',
  `is_show` tinyint(1) unsigned NOT NULL DEFAULT '1',
  PRIMARY KEY (`brand_id`),
  KEY `is_show` (`is_show`) USING BTREE
) ENGINE=MyISAM AUTO_INCREMENT=8 DEFAULT CHARSET=utf8;

-- ----------------------------
-- Records of ecs_brand
-- ----------------------------

-- ----------------------------
-- Table structure for `ecs_brand_activity`
-- ----------------------------
DROP TABLE IF EXISTS `ecs_brand_activity`;
CREATE TABLE `ecs_brand_activity` (
  `id` mediumint(9) NOT NULL AUTO_INCREMENT COMMENT 'id',
  `pic` varchar(300) NOT NULL COMMENT '海报图',
  `title` varchar(300) NOT NULL COMMENT '标题',
  `content` text NOT NULL COMMENT '内容',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- ----------------------------
-- Records of ecs_brand_activity
-- ----------------------------

-- ----------------------------
-- Table structure for `ecs_brand_dynamics`
-- ----------------------------
DROP TABLE IF EXISTS `ecs_brand_dynamics`;
CREATE TABLE `ecs_brand_dynamics` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(90) NOT NULL COMMENT '标题',
  `short_title` varchar(60) NOT NULL COMMENT '短标题',
  `title_color` varchar(10) NOT NULL COMMENT '列表标题颜色',
  `dcrs` tinytext NOT NULL COMMENT '描述',
  `content` text NOT NULL COMMENT '内容',
  `thumb_one` varchar(200) NOT NULL COMMENT '列表缩略图1',
  `thumb_two` varchar(200) NOT NULL COMMENT '列表缩略图2',
  `thumb_three` varchar(200) NOT NULL COMMENT '列表缩略图3',
  `img_one_md5` char(32) NOT NULL COMMENT '缩略图的原图路径MD5',
  `img_two_md5` char(32) NOT NULL COMMENT '缩略图的原图路径MD5',
  `img_three_md5` char(32) NOT NULL COMMENT '缩略图的原图路径MD5',
  `author` varchar(20) NOT NULL COMMENT '作者',
  `source` varchar(40) NOT NULL COMMENT '来源',
  `keyword` tinytext NOT NULL COMMENT '关键字',
  `target` tinyint(4) NOT NULL DEFAULT '0' COMMENT '是否新窗口打开（0否，1是）',
  `display` tinyint(3) unsigned NOT NULL DEFAULT '1' COMMENT '是否显示（0不显示，1显示）',
  `is_top` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '是否置顶（1置顶，0不置顶）',
  `is_recommend` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '是否推荐（1推荐，0不推荐）',
  `sort` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '排序',
  `create_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '添加时间',
  `update_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=16 DEFAULT CHARSET=utf8 COMMENT='品牌动态';

-- ----------------------------
-- Records of ecs_brand_dynamics
-- ----------------------------

-- ----------------------------
-- Table structure for `ecs_brand_dynamics_img`
-- ----------------------------
DROP TABLE IF EXISTS `ecs_brand_dynamics_img`;
CREATE TABLE `ecs_brand_dynamics_img` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(60) NOT NULL COMMENT '图片标题',
  `picture_path` varchar(255) NOT NULL COMMENT '图片地址',
  `url` varchar(255) NOT NULL COMMENT '连接地址',
  `target` tinyint(4) NOT NULL DEFAULT '0' COMMENT '是否新窗口打开（0否，1是）',
  `display` tinyint(3) unsigned NOT NULL DEFAULT '1' COMMENT '是否显示（0不显示，1显示）',
  `sort` int(11) NOT NULL DEFAULT '0' COMMENT '排序',
  `create_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  `update_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
  `start_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '开始时间',
  `end_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '结束时间',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=5 DEFAULT CHARSET=utf8 COMMENT='品牌动态轮番图片';

-- ----------------------------
-- Records of ecs_brand_dynamics_img
-- ----------------------------

-- ----------------------------
-- Table structure for `ecs_card`
-- ----------------------------
DROP TABLE IF EXISTS `ecs_card`;
CREATE TABLE `ecs_card` (
  `card_id` tinyint(3) unsigned NOT NULL AUTO_INCREMENT,
  `card_name` varchar(120) NOT NULL DEFAULT '',
  `card_img` varchar(255) NOT NULL DEFAULT '',
  `card_fee` decimal(6,2) unsigned NOT NULL DEFAULT '0.00',
  `free_money` decimal(6,2) unsigned NOT NULL DEFAULT '0.00',
  `card_desc` varchar(255) NOT NULL DEFAULT '',
  PRIMARY KEY (`card_id`)
) ENGINE=MyISAM AUTO_INCREMENT=2 DEFAULT CHARSET=utf8;

-- ----------------------------
-- Records of ecs_card
-- ----------------------------

-- ----------------------------
-- Table structure for `ecs_cart`
-- ----------------------------
DROP TABLE IF EXISTS `ecs_cart`;
CREATE TABLE `ecs_cart` (
  `rec_id` mediumint(8) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` mediumint(8) unsigned NOT NULL DEFAULT '0',
  `session_id` char(32) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL DEFAULT '',
  `goods_id` mediumint(8) unsigned NOT NULL DEFAULT '0',
  `goods_sn` varchar(60) NOT NULL DEFAULT '',
  `product_id` mediumint(8) unsigned NOT NULL DEFAULT '0',
  `goods_name` varchar(120) NOT NULL DEFAULT '',
  `market_price` decimal(10,2) unsigned NOT NULL DEFAULT '0.00',
  `goods_price` decimal(10,2) NOT NULL DEFAULT '0.00',
  `goods_number` smallint(5) unsigned NOT NULL DEFAULT '0',
  `goods_attr` text NOT NULL,
  `is_real` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `extension_code` varchar(30) NOT NULL DEFAULT '',
  `parent_id` mediumint(8) unsigned NOT NULL DEFAULT '0',
  `rec_type` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `is_gift` smallint(5) unsigned NOT NULL DEFAULT '0',
  `is_shipping` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `can_handsel` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `goods_attr_id` varchar(255) NOT NULL DEFAULT '',
  `addtime` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '加入时间',
  PRIMARY KEY (`rec_id`),
  KEY `session_id` (`session_id`) USING BTREE
) ENGINE=MyISAM AUTO_INCREMENT=9249 DEFAULT CHARSET=utf8;

-- ----------------------------
-- Records of ecs_cart
-- ----------------------------

-- ----------------------------
-- Table structure for `ecs_category`
-- ----------------------------
DROP TABLE IF EXISTS `ecs_category`;
CREATE TABLE `ecs_category` (
  `cat_id` int(11) NOT NULL AUTO_INCREMENT,
  `cat_name` varchar(50) NOT NULL,
  `keywords` varchar(200) NOT NULL,
  `cat_desc` varchar(200) NOT NULL,
  `parent_id` int(11) NOT NULL,
  `sort_order` tinyint(1) NOT NULL,
  `template_file` varchar(150) NOT NULL,
  `measure_unit` varchar(45) NOT NULL,
  `show_in_nav` tinyint(1) NOT NULL,
  `style` varchar(450) NOT NULL,
  `is_show` tinyint(1) NOT NULL,
  `grade` tinyint(4) NOT NULL,
  `filter_attr` varchar(255) NOT NULL,
  PRIMARY KEY (`cat_id`)
) ENGINE=MyISAM AUTO_INCREMENT=150 DEFAULT CHARSET=utf8;

-- ----------------------------
-- Records of ecs_category
-- ----------------------------

-- ----------------------------
-- Table structure for `ecs_cat_recommend`
-- ----------------------------
DROP TABLE IF EXISTS `ecs_cat_recommend`;
CREATE TABLE `ecs_cat_recommend` (
  `cat_id` smallint(5) NOT NULL,
  `recommend_type` tinyint(1) NOT NULL,
  PRIMARY KEY (`cat_id`,`recommend_type`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- ----------------------------
-- Records of ecs_cat_recommend
-- ----------------------------

-- ----------------------------
-- Table structure for `ecs_collect_goods`
-- ----------------------------
DROP TABLE IF EXISTS `ecs_collect_goods`;
CREATE TABLE `ecs_collect_goods` (
  `rec_id` mediumint(8) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` mediumint(8) unsigned NOT NULL DEFAULT '0',
  `goods_id` mediumint(8) unsigned NOT NULL DEFAULT '0',
  `add_time` int(11) unsigned NOT NULL DEFAULT '0',
  `is_attention` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`rec_id`),
  KEY `user_id` (`user_id`) USING BTREE,
  KEY `goods_id` (`goods_id`) USING BTREE,
  KEY `is_attention` (`is_attention`) USING BTREE
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- ----------------------------
-- Records of ecs_collect_goods
-- ----------------------------

-- ----------------------------
-- Table structure for `ecs_comment`
-- ----------------------------
DROP TABLE IF EXISTS `ecs_comment`;
CREATE TABLE `ecs_comment` (
  `comment_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `comment_type` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `id_value` mediumint(8) unsigned NOT NULL DEFAULT '0',
  `email` varchar(60) NOT NULL DEFAULT '',
  `user_name` varchar(60) NOT NULL DEFAULT '',
  `content` text NOT NULL,
  `comment_rank` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `add_time` int(10) unsigned NOT NULL DEFAULT '0',
  `ip_address` varchar(15) NOT NULL DEFAULT '',
  `status` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `parent_id` int(10) unsigned NOT NULL DEFAULT '0',
  `user_id` int(10) unsigned NOT NULL DEFAULT '0',
  `scontent` text NOT NULL,
  PRIMARY KEY (`comment_id`),
  KEY `parent_id` (`parent_id`) USING BTREE,
  KEY `id_value` (`id_value`) USING BTREE
) ENGINE=MyISAM AUTO_INCREMENT=2634 DEFAULT CHARSET=utf8;

-- ----------------------------
-- Records of ecs_comment
-- ----------------------------

-- ----------------------------
-- Table structure for `ecs_comments`
-- ----------------------------
DROP TABLE IF EXISTS `ecs_comments`;
CREATE TABLE `ecs_comments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_value` mediumint(8) unsigned NOT NULL COMMENT '商品id',
  `user_name` varchar(60) NOT NULL COMMENT '用户名',
  `user_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '会员ID',
  `order_sn` varchar(20) NOT NULL COMMENT '订单号',
  `time` int(10) unsigned NOT NULL COMMENT '评论时间',
  `content` text NOT NULL COMMENT '评论的内容',
  `status` tinyint(2) unsigned NOT NULL DEFAULT '0' COMMENT '0表示显示，1表示垃圾桶',
  `show_time` int(10) NOT NULL COMMENT '展示的时间',
  `show_status` tinyint(2) NOT NULL COMMENT '0表示不显示，1表示显示',
  `review_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '审核时间（0=未审核）',
  `level` int(2) NOT NULL COMMENT '级别',
  `is_client` int(2) NOT NULL COMMENT '评论类型：0：系统 1：客户',
  `tag` varchar(255) NOT NULL COMMENT '标签',
  `pic` varchar(255) NOT NULL COMMENT '图片1',
  `pic1` varchar(255) NOT NULL COMMENT '图片2',
  `like_num` mediumint(9) NOT NULL COMMENT '喜欢数，认为有用数',
  `type_id` smallint(6) DEFAULT '0' COMMENT '标签分类id',
  `z_content` text COMMENT '追加评论内容',
  `z_date` char(10) DEFAULT '' COMMENT '追加评论日期',
  `z_review_time` int(10) NOT NULL DEFAULT '0' COMMENT '追评审核时间（0=未审核）',
  PRIMARY KEY (`id`),
  KEY `order_sn_index` (`order_sn`) USING BTREE,
  KEY `z_date_index` (`z_date`) USING BTREE,
  KEY `id_value_index` (`id_value`) USING BTREE,
  KEY `status_index` (`status`) USING BTREE
) ENGINE=MyISAM AUTO_INCREMENT=40667 DEFAULT CHARSET=utf8;

-- ----------------------------
-- Records of ecs_comments
-- ----------------------------

-- ----------------------------
-- Table structure for `ecs_commentss`
-- ----------------------------
DROP TABLE IF EXISTS `ecs_commentss`;
CREATE TABLE `ecs_commentss` (
  `comment_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `comment_type` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `id_value` mediumint(8) unsigned NOT NULL DEFAULT '0',
  `email` varchar(60) NOT NULL DEFAULT '',
  `user_name` varchar(60) NOT NULL DEFAULT '',
  `content` text NOT NULL,
  `comment_rank` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `add_time` int(10) unsigned NOT NULL DEFAULT '0',
  `ip_address` varchar(15) NOT NULL DEFAULT '',
  `status` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `parent_id` int(10) unsigned NOT NULL DEFAULT '0',
  `user_id` int(10) unsigned NOT NULL DEFAULT '0',
  `scontent` text NOT NULL,
  PRIMARY KEY (`comment_id`),
  KEY `parent_id` (`parent_id`) USING BTREE,
  KEY `id_value` (`id_value`) USING BTREE
) ENGINE=MyISAM AUTO_INCREMENT=19 DEFAULT CHARSET=utf8;

-- ----------------------------
-- Records of ecs_commentss
-- ----------------------------

-- ----------------------------
-- Table structure for `ecs_comments_gallery`
-- ----------------------------
DROP TABLE IF EXISTS `ecs_comments_gallery`;
CREATE TABLE `ecs_comments_gallery` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `comment_id` int(11) NOT NULL,
  `img_url` varchar(255) NOT NULL COMMENT '原图',
  `thumb_url` varchar(255) NOT NULL COMMENT '缩略图',
  `type` tinyint(2) unsigned DEFAULT '0' COMMENT '图片类型。0：评论图，1追加评论图',
  PRIMARY KEY (`id`),
  KEY `comment_id_index` (`comment_id`)
) ENGINE=InnoDB AUTO_INCREMENT=86 DEFAULT CHARSET=utf8 COMMENT='评论相册表';

-- ----------------------------
-- Records of ecs_comments_gallery
-- ----------------------------

-- ----------------------------
-- Table structure for `ecs_comment_like`
-- ----------------------------
DROP TABLE IF EXISTS `ecs_comment_like`;
CREATE TABLE `ecs_comment_like` (
  `id` mediumint(9) NOT NULL AUTO_INCREMENT COMMENT 'id',
  `key` varchar(32) NOT NULL COMMENT '识别键',
  `ip` varchar(15) NOT NULL COMMENT 'ip',
  `creat_time` int(11) NOT NULL COMMENT '创建时间',
  `comment_id` mediumint(9) NOT NULL COMMENT '评论id',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=100 DEFAULT CHARSET=utf8;

-- ----------------------------
-- Records of ecs_comment_like
-- ----------------------------

-- ----------------------------
-- Table structure for `ecs_comment_log`
-- ----------------------------
DROP TABLE IF EXISTS `ecs_comment_log`;
CREATE TABLE `ecs_comment_log` (
  `log_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `log_time` int(10) unsigned NOT NULL DEFAULT '0',
  `user_id` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `log_info` varchar(255) NOT NULL DEFAULT '',
  `ip_address` varchar(15) NOT NULL DEFAULT '',
  PRIMARY KEY (`log_id`),
  KEY `log_time` (`log_time`) USING BTREE,
  KEY `user_id` (`user_id`) USING BTREE
) ENGINE=MyISAM AUTO_INCREMENT=359 DEFAULT CHARSET=utf8;

-- ----------------------------
-- Records of ecs_comment_log
-- ----------------------------

-- ----------------------------
-- Table structure for `ecs_comment_type`
-- ----------------------------
DROP TABLE IF EXISTS `ecs_comment_type`;
CREATE TABLE `ecs_comment_type` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `type_name` varchar(16) NOT NULL DEFAULT '' COMMENT '标签名',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=34 DEFAULT CHARSET=utf8 COMMENT='评论标签';

-- ----------------------------
-- Records of ecs_comment_type
-- ----------------------------

-- ----------------------------
-- Table structure for `ecs_crawler_black_ip`
-- ----------------------------
DROP TABLE IF EXISTS `ecs_crawler_black_ip`;
CREATE TABLE `ecs_crawler_black_ip` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `ip` varchar(15) DEFAULT NULL COMMENT 'IP地址',
  `url` text COMMENT '当前链接地址',
  `user_agent` text COMMENT '浏览器头信息',
  `http_referer` text COMMENT '来源地址',
  `addtime` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '添加时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `ip` (`ip`)
) ENGINE=InnoDB AUTO_INCREMENT=40 DEFAULT CHARSET=utf8 COMMENT='链接推广IP黑名单';

-- ----------------------------
-- Records of ecs_crawler_black_ip
-- ----------------------------

-- ----------------------------
-- Table structure for `ecs_crawler_log`
-- ----------------------------
DROP TABLE IF EXISTS `ecs_crawler_log`;
CREATE TABLE `ecs_crawler_log` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `ip` varchar(15) DEFAULT NULL COMMENT 'IP地址',
  `url` text COMMENT '当前地址',
  `user_agent` text COMMENT '头信息',
  `http_referer` text COMMENT '来源地址',
  `addtime` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '添加时间',
  PRIMARY KEY (`id`),
  KEY `ip` (`ip`),
  KEY `addtime` (`addtime`)
) ENGINE=InnoDB AUTO_INCREMENT=97 DEFAULT CHARSET=utf8 COMMENT='推广链接日记记录';

-- ----------------------------
-- Records of ecs_crawler_log
-- ----------------------------

-- ----------------------------
-- Table structure for `ecs_goods000`
-- ----------------------------
DROP TABLE IF EXISTS `ecs_goods000`;
CREATE TABLE `ecs_goods000` (
  `goods_id` int(11) NOT NULL AUTO_INCREMENT,
  `cat_id` int(11) DEFAULT NULL,
  `goods_sn` varchar(180) DEFAULT NULL,
  `goods_name` varchar(360) DEFAULT NULL,
  `skip_link` varchar(254) DEFAULT NULL COMMENT '列表跳转链接',
  `goods_name_style` varchar(180) DEFAULT NULL,
  `click_count` double DEFAULT NULL,
  `brand_id` int(11) DEFAULT NULL,
  `provider_name` varchar(300) DEFAULT NULL,
  `goods_number` int(11) DEFAULT NULL,
  `goods_weight` decimal(11,0) DEFAULT NULL,
  `market_price` decimal(11,0) DEFAULT NULL,
  `shop_price` decimal(11,0) DEFAULT NULL,
  `promote_price` decimal(11,0) DEFAULT NULL,
  `promote_start_date` double DEFAULT NULL,
  `promote_end_date` double DEFAULT NULL,
  `warn_number` tinyint(3) DEFAULT NULL,
  `keywords` varchar(765) DEFAULT NULL,
  `goods_brief` varchar(765) DEFAULT NULL,
  `goods_desc` blob,
  `goods_thumb` varchar(765) DEFAULT NULL,
  `goods_img` varchar(765) DEFAULT NULL,
  `original_img` varchar(765) DEFAULT NULL,
  `is_real` tinyint(3) DEFAULT '1',
  `extension_code` varchar(90) DEFAULT NULL,
  `is_on_sale` tinyint(1) DEFAULT NULL,
  `is_alone_sale` tinyint(1) DEFAULT NULL,
  `is_shipping` tinyint(1) DEFAULT NULL,
  `integral` double DEFAULT NULL,
  `add_time` double DEFAULT NULL,
  `sort_order` int(11) NOT NULL DEFAULT '100',
  `is_delete` tinyint(1) DEFAULT '0',
  `is_best` tinyint(1) DEFAULT NULL,
  `is_new` tinyint(1) DEFAULT NULL,
  `is_hot` tinyint(1) DEFAULT NULL,
  `is_promotion` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '是否促销商品',
  `is_package` tinyint(1) DEFAULT '0',
  `is_promote` tinyint(1) DEFAULT NULL,
  `bonus_type_id` tinyint(3) DEFAULT NULL,
  `child_goods` text COMMENT '子商品',
  `last_update` double DEFAULT NULL,
  `goods_type` int(11) DEFAULT NULL,
  `seller_note` varchar(765) DEFAULT NULL,
  `give_integral` double DEFAULT NULL,
  `rank_integral` double DEFAULT NULL,
  `suppliers_id` int(11) DEFAULT NULL,
  `is_check` tinyint(1) DEFAULT NULL,
  `mobile_goods_name` varchar(360) DEFAULT NULL,
  `mobile_goods_name_style` varchar(180) DEFAULT NULL,
  `mobile_goods_brief` varchar(765) DEFAULT NULL,
  `mobile_goods_desc` blob,
  `mobile_goods_img` varchar(765) DEFAULT NULL,
  `tobuy` int(11) NOT NULL,
  `goods_title` varchar(765) DEFAULT NULL,
  `goods_guanggao` varchar(750) DEFAULT NULL,
  `goods_fenlei` mediumint(5) DEFAULT '1',
  `is_show` tinyint(1) NOT NULL DEFAULT '1',
  `is_series` tinyint(1) NOT NULL DEFAULT '0',
  `virtual_sale` mediumint(8) NOT NULL DEFAULT '6000' COMMENT '虚拟销售数',
  PRIMARY KEY (`goods_id`)
) ENGINE=MyISAM AUTO_INCREMENT=2470 DEFAULT CHARSET=utf8;

-- ----------------------------
-- Records of ecs_goods000
-- ----------------------------

-- ----------------------------
-- Table structure for `ecs_goods_attr`
-- ----------------------------
DROP TABLE IF EXISTS `ecs_goods_attr`;
CREATE TABLE `ecs_goods_attr` (
  `goods_attr_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `goods_id` mediumint(8) unsigned NOT NULL DEFAULT '0',
  `attr_id` smallint(5) unsigned NOT NULL DEFAULT '0',
  `attr_value` text NOT NULL,
  `attr_price` varchar(255) NOT NULL DEFAULT '',
  PRIMARY KEY (`goods_attr_id`),
  KEY `goods_id` (`goods_id`) USING BTREE,
  KEY `attr_id` (`attr_id`) USING BTREE
) ENGINE=MyISAM AUTO_INCREMENT=1585 DEFAULT CHARSET=utf8;

-- ----------------------------
-- Records of ecs_goods_attr
-- ----------------------------

-- ----------------------------
-- Table structure for `ecs_goods_cat`
-- ----------------------------
DROP TABLE IF EXISTS `ecs_goods_cat`;
CREATE TABLE `ecs_goods_cat` (
  `goods_id` mediumint(8) unsigned NOT NULL DEFAULT '0',
  `cat_id` smallint(5) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`goods_id`,`cat_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- ----------------------------
-- Records of ecs_goods_cat
-- ----------------------------

-- ----------------------------
-- Table structure for `ecs_goods_gallery`
-- ----------------------------
DROP TABLE IF EXISTS `ecs_goods_gallery`;
CREATE TABLE `ecs_goods_gallery` (
  `img_id` mediumint(8) unsigned NOT NULL AUTO_INCREMENT,
  `goods_id` mediumint(8) unsigned NOT NULL DEFAULT '0',
  `img_url` varchar(255) NOT NULL DEFAULT '',
  `img_desc` varchar(255) NOT NULL DEFAULT '',
  `thumb_url` varchar(255) NOT NULL DEFAULT '',
  `img_original` varchar(255) NOT NULL DEFAULT '',
  PRIMARY KEY (`img_id`),
  KEY `goods_id` (`goods_id`) USING BTREE
) ENGINE=MyISAM AUTO_INCREMENT=33 DEFAULT CHARSET=utf8;

-- ----------------------------
-- Records of ecs_goods_gallery
-- ----------------------------

-- ----------------------------
-- Table structure for `ecs_goods_like_log`
-- ----------------------------
DROP TABLE IF EXISTS `ecs_goods_like_log`;
CREATE TABLE `ecs_goods_like_log` (
  `id` mediumint(9) NOT NULL AUTO_INCREMENT,
  `goods_id` mediumint(9) NOT NULL,
  `ip` varchar(15) NOT NULL,
  `create_time` int(11) NOT NULL,
  `key` varchar(32) NOT NULL COMMENT '用户识别键',
  `status` tinyint(4) NOT NULL,
  `update_time` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=74 DEFAULT CHARSET=utf8;

-- ----------------------------
-- Records of ecs_goods_like_log
-- ----------------------------

-- ----------------------------
-- Table structure for `ecs_goods_type`
-- ----------------------------
DROP TABLE IF EXISTS `ecs_goods_type`;
CREATE TABLE `ecs_goods_type` (
  `cat_id` smallint(5) unsigned NOT NULL AUTO_INCREMENT,
  `cat_name` varchar(60) NOT NULL DEFAULT '',
  `enabled` tinyint(1) unsigned NOT NULL DEFAULT '1',
  `attr_group` varchar(255) NOT NULL,
  PRIMARY KEY (`cat_id`)
) ENGINE=MyISAM AUTO_INCREMENT=5 DEFAULT CHARSET=utf8;

-- ----------------------------
-- Records of ecs_goods_type
-- ----------------------------

-- ----------------------------
-- Table structure for `ecs_group_goods`
-- ----------------------------
DROP TABLE IF EXISTS `ecs_group_goods`;
CREATE TABLE `ecs_group_goods` (
  `parent_id` mediumint(8) unsigned NOT NULL DEFAULT '0',
  `goods_id` mediumint(8) unsigned NOT NULL DEFAULT '0',
  `goods_price` decimal(10,2) unsigned NOT NULL DEFAULT '0.00',
  `admin_id` tinyint(3) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`parent_id`,`goods_id`,`admin_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- ----------------------------
-- Records of ecs_group_goods
-- ----------------------------

-- ----------------------------
-- Table structure for `ecs_keywords`
-- ----------------------------
DROP TABLE IF EXISTS `ecs_keywords`;
CREATE TABLE `ecs_keywords` (
  `date` date NOT NULL DEFAULT '0000-00-00',
  `searchengine` varchar(20) NOT NULL DEFAULT '',
  `keyword` varchar(90) NOT NULL DEFAULT '',
  `count` mediumint(8) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`date`,`searchengine`,`keyword`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- ----------------------------
-- Records of ecs_keywords
-- ----------------------------

-- ----------------------------
-- Table structure for `ecs_link_goods`
-- ----------------------------
DROP TABLE IF EXISTS `ecs_link_goods`;
CREATE TABLE `ecs_link_goods` (
  `goods_id` mediumint(8) unsigned NOT NULL DEFAULT '0',
  `link_goods_id` mediumint(8) unsigned NOT NULL DEFAULT '0',
  `is_double` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `admin_id` tinyint(3) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`goods_id`,`link_goods_id`,`admin_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- ----------------------------
-- Records of ecs_link_goods
-- ----------------------------

-- ----------------------------
-- Table structure for `ecs_mail_templates`
-- ----------------------------
DROP TABLE IF EXISTS `ecs_mail_templates`;
CREATE TABLE `ecs_mail_templates` (
  `template_id` tinyint(1) unsigned NOT NULL AUTO_INCREMENT,
  `template_code` varchar(30) NOT NULL DEFAULT '',
  `is_html` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `template_subject` varchar(200) NOT NULL DEFAULT '',
  `template_content` text NOT NULL,
  `last_modify` int(10) unsigned NOT NULL DEFAULT '0',
  `last_send` int(10) unsigned NOT NULL DEFAULT '0',
  `type` varchar(10) NOT NULL,
  PRIMARY KEY (`template_id`),
  UNIQUE KEY `template_code` (`template_code`) USING BTREE,
  KEY `type` (`type`) USING BTREE
) ENGINE=MyISAM AUTO_INCREMENT=15 DEFAULT CHARSET=utf8;

-- ----------------------------
-- Records of ecs_mail_templates
-- ----------------------------

-- ----------------------------
-- Table structure for `ecs_member_price`
-- ----------------------------
DROP TABLE IF EXISTS `ecs_member_price`;
CREATE TABLE `ecs_member_price` (
  `price_id` mediumint(8) unsigned NOT NULL AUTO_INCREMENT,
  `goods_id` mediumint(8) unsigned NOT NULL DEFAULT '0',
  `user_rank` tinyint(3) NOT NULL DEFAULT '0',
  `user_price` decimal(10,2) NOT NULL DEFAULT '0.00',
  PRIMARY KEY (`price_id`),
  KEY `goods_id` (`goods_id`,`user_rank`) USING BTREE
) ENGINE=MyISAM AUTO_INCREMENT=8 DEFAULT CHARSET=utf8;

-- ----------------------------
-- Records of ecs_member_price
-- ----------------------------

-- ----------------------------
-- Table structure for `ecs_mobile_info`
-- ----------------------------
DROP TABLE IF EXISTS `ecs_mobile_info`;
CREATE TABLE `ecs_mobile_info` (
  `id` mediumint(10) NOT NULL AUTO_INCREMENT,
  `user_agent` varchar(255) NOT NULL,
  `ip` varchar(20) NOT NULL,
  `time` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=121 DEFAULT CHARSET=utf8;

-- ----------------------------
-- Records of ecs_mobile_info
-- ----------------------------

-- ----------------------------
-- Table structure for `ecs_nav`
-- ----------------------------
DROP TABLE IF EXISTS `ecs_nav`;
CREATE TABLE `ecs_nav` (
  `id` mediumint(8) NOT NULL AUTO_INCREMENT,
  `ctype` varchar(10) DEFAULT NULL,
  `cid` smallint(5) unsigned DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `ifshow` tinyint(1) NOT NULL,
  `vieworder` tinyint(1) NOT NULL,
  `opennew` tinyint(1) NOT NULL,
  `url` varchar(255) NOT NULL,
  `type` varchar(10) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `type` (`type`) USING BTREE,
  KEY `ifshow` (`ifshow`) USING BTREE
) ENGINE=MyISAM AUTO_INCREMENT=52 DEFAULT CHARSET=utf8;

-- ----------------------------
-- Records of ecs_nav
-- ----------------------------

-- ----------------------------
-- Table structure for `ecs_opinion`
-- ----------------------------
DROP TABLE IF EXISTS `ecs_opinion`;
CREATE TABLE `ecs_opinion` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `display` varchar(64) NOT NULL COMMENT '关于显示',
  `load` varchar(64) NOT NULL COMMENT '关于加载',
  `pay` varchar(64) NOT NULL COMMENT '关于支付',
  `service` varchar(64) NOT NULL COMMENT '关于服务质量',
  `content` text NOT NULL COMMENT '更多问题',
  `type` varchar(12) NOT NULL COMMENT '浏览器类型',
  `science` varchar(12) NOT NULL COMMENT '网络环境',
  `ip` varchar(32) NOT NULL COMMENT '用户ip',
  `time` int(11) NOT NULL COMMENT '用户浏览的时间',
  `name` varchar(50) DEFAULT NULL COMMENT '联系人',
  `mobile` varchar(130) NOT NULL DEFAULT '' COMMENT '联系手机号',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=51 DEFAULT CHARSET=utf8;

-- ----------------------------
-- Records of ecs_opinion
-- ----------------------------

-- ----------------------------
-- Table structure for `ecs_order_action`
-- ----------------------------
DROP TABLE IF EXISTS `ecs_order_action`;
CREATE TABLE `ecs_order_action` (
  `action_id` mediumint(8) unsigned NOT NULL AUTO_INCREMENT,
  `order_id` mediumint(8) unsigned NOT NULL DEFAULT '0',
  `action_user` varchar(30) NOT NULL DEFAULT '',
  `order_status` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `shipping_status` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `pay_status` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `action_place` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `action_note` varchar(255) NOT NULL DEFAULT '',
  `log_time` int(11) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`action_id`),
  KEY `order_id` (`order_id`) USING BTREE
) ENGINE=MyISAM AUTO_INCREMENT=84 DEFAULT CHARSET=utf8;

-- ----------------------------
-- Records of ecs_order_action
-- ----------------------------

-- ----------------------------
-- Table structure for `ecs_order_goods`
-- ----------------------------
DROP TABLE IF EXISTS `ecs_order_goods`;
CREATE TABLE `ecs_order_goods` (
  `rec_id` mediumint(8) unsigned NOT NULL AUTO_INCREMENT,
  `order_id` mediumint(8) unsigned NOT NULL DEFAULT '0',
  `goods_id` mediumint(8) unsigned NOT NULL DEFAULT '0',
  `goods_name` varchar(120) NOT NULL DEFAULT '',
  `goods_sn` varchar(60) NOT NULL DEFAULT '',
  `product_id` mediumint(8) unsigned NOT NULL DEFAULT '0',
  `goods_number` smallint(5) unsigned NOT NULL DEFAULT '1',
  `market_price` decimal(10,2) NOT NULL DEFAULT '0.00',
  `goods_price` decimal(10,2) NOT NULL DEFAULT '0.00',
  `goods_attr` text NOT NULL,
  `send_number` smallint(5) unsigned NOT NULL DEFAULT '0',
  `is_real` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `extension_code` varchar(30) NOT NULL DEFAULT '',
  `parent_id` mediumint(8) unsigned NOT NULL DEFAULT '0',
  `is_gift` smallint(5) unsigned NOT NULL DEFAULT '0',
  `goods_attr_id` varchar(255) NOT NULL DEFAULT '',
  PRIMARY KEY (`rec_id`),
  KEY `order_id` (`order_id`) USING BTREE,
  KEY `goods_id` (`goods_id`) USING BTREE
) ENGINE=MyISAM AUTO_INCREMENT=357161 DEFAULT CHARSET=utf8;

-- ----------------------------
-- Records of ecs_order_goods
-- ----------------------------

-- ----------------------------
-- Table structure for `ecs_order_info`
-- ----------------------------
DROP TABLE IF EXISTS `ecs_order_info`;
CREATE TABLE `ecs_order_info` (
  `order_id` mediumint(8) unsigned NOT NULL AUTO_INCREMENT,
  `order_sn` varchar(20) NOT NULL DEFAULT '',
  `user_id` mediumint(8) unsigned NOT NULL DEFAULT '0',
  `order_status` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `shipping_status` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `shipping_num` tinyint(1) DEFAULT '0',
  `pay_status` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `settle_status` tinyint(1) DEFAULT '0',
  `consignee` varchar(60) NOT NULL DEFAULT '',
  `country` smallint(5) unsigned NOT NULL DEFAULT '0',
  `province` smallint(5) unsigned NOT NULL DEFAULT '0',
  `city` smallint(5) unsigned NOT NULL DEFAULT '0',
  `district` smallint(5) unsigned NOT NULL DEFAULT '0',
  `address` varchar(255) NOT NULL DEFAULT '',
  `zipcode` varchar(60) NOT NULL DEFAULT '',
  `tel` varchar(60) NOT NULL DEFAULT '',
  `mobile` varchar(60) NOT NULL DEFAULT '',
  `email` varchar(60) NOT NULL DEFAULT '',
  `best_time` varchar(120) NOT NULL DEFAULT '',
  `sign_building` varchar(120) NOT NULL DEFAULT '',
  `postscript` text NOT NULL,
  `shipping_id` tinyint(3) NOT NULL DEFAULT '0',
  `shipping_name` varchar(120) NOT NULL DEFAULT '',
  `pay_id` tinyint(3) NOT NULL DEFAULT '0',
  `pay_name` varchar(120) NOT NULL DEFAULT '',
  `how_oos` varchar(120) NOT NULL DEFAULT '',
  `how_surplus` varchar(120) NOT NULL DEFAULT '',
  `pack_name` varchar(120) NOT NULL DEFAULT '',
  `card_name` varchar(120) NOT NULL DEFAULT '',
  `card_message` varchar(255) NOT NULL DEFAULT '',
  `inv_payee` varchar(120) NOT NULL DEFAULT '',
  `inv_content` varchar(120) NOT NULL DEFAULT '',
  `goods_amount` decimal(10,2) NOT NULL DEFAULT '0.00',
  `shipping_fee` decimal(10,2) NOT NULL DEFAULT '0.00',
  `insure_fee` decimal(10,2) NOT NULL DEFAULT '0.00',
  `pay_fee` decimal(10,2) NOT NULL DEFAULT '0.00',
  `pack_fee` decimal(10,2) NOT NULL DEFAULT '0.00',
  `card_fee` decimal(10,2) NOT NULL DEFAULT '0.00',
  `money_paid` decimal(10,2) NOT NULL DEFAULT '0.00',
  `surplus` decimal(10,2) NOT NULL DEFAULT '0.00',
  `integral` int(10) unsigned NOT NULL DEFAULT '0',
  `integral_money` decimal(10,2) NOT NULL DEFAULT '0.00',
  `bonus` decimal(10,2) NOT NULL DEFAULT '0.00',
  `order_amount` decimal(10,2) NOT NULL DEFAULT '0.00',
  `from_ad` smallint(5) NOT NULL DEFAULT '0',
  `referer` varchar(255) NOT NULL DEFAULT '',
  `add_time` int(10) unsigned NOT NULL DEFAULT '0',
  `confirm_time` int(10) unsigned NOT NULL DEFAULT '0',
  `pay_time` int(10) unsigned NOT NULL DEFAULT '0',
  `shipping_time` int(10) unsigned NOT NULL DEFAULT '0',
  `pack_id` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `card_id` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `bonus_id` mediumint(8) unsigned NOT NULL DEFAULT '0',
  `invoice_no` varchar(255) NOT NULL DEFAULT '',
  `extension_code` varchar(30) NOT NULL DEFAULT '',
  `extension_id` mediumint(8) unsigned NOT NULL DEFAULT '0',
  `to_buyer` varchar(255) NOT NULL DEFAULT '',
  `pay_note` varchar(255) NOT NULL DEFAULT '',
  `agency_id` smallint(5) unsigned NOT NULL,
  `inv_type` varchar(60) NOT NULL,
  `tax` decimal(10,2) NOT NULL,
  `is_separate` tinyint(1) NOT NULL DEFAULT '0',
  `parent_id` mediumint(8) unsigned NOT NULL DEFAULT '0',
  `discount` decimal(10,2) NOT NULL,
  `qq` varchar(30) DEFAULT '0',
  `settle_time` int(11) DEFAULT '0',
  `receive_time` int(10) DEFAULT '0',
  `preparing_time` int(10) DEFAULT '0',
  `return_time` int(10) DEFAULT '0',
  `kefu` varchar(60) DEFAULT '0',
  `manager_name` varchar(15) DEFAULT '0',
  `gd_follow_kefu` varchar(60) DEFAULT '0',
  `gd_follow_time` int(10) DEFAULT '0',
  `real_shipping_time` int(10) DEFAULT '0',
  `is_sendsms` tinyint(1) DEFAULT '0',
  `prize` int(11) DEFAULT '0',
  `order_keyword` varchar(255) DEFAULT '0',
  `order_searchengine` varchar(20) DEFAULT '0',
  `front_fee` decimal(10,2) DEFAULT '0.00',
  `order_type` tinyint(1) DEFAULT '0',
  `client_id` int(10) DEFAULT '0',
  `from_kefu` varchar(60) DEFAULT '',
  `from_time` int(10) DEFAULT '0',
  `follow_up_num` tinyint(1) DEFAULT '0',
  `ip_address` varchar(15) DEFAULT '',
  `check_time` int(10) DEFAULT '0',
  `ip_info_text` text,
  `is_privacy` tinyint(1) DEFAULT '0',
  `s_tel` varchar(255) DEFAULT '',
  `s_mobile` varchar(255) DEFAULT '',
  `s_address` varchar(255) DEFAULT '',
  `goods_list` text,
  `divide_region` varchar(50) NOT NULL,
  `test` varchar(50) DEFAULT NULL,
  `payment_discount` decimal(10,2) DEFAULT '0.00' COMMENT '网银在线支付折扣10元',
  `town` smallint(5) NOT NULL DEFAULT '0' COMMENT '镇',
  `weixin` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`order_id`),
  UNIQUE KEY `order_sn` (`order_sn`) USING BTREE,
  KEY `user_id` (`user_id`) USING BTREE,
  KEY `order_status` (`order_status`) USING BTREE,
  KEY `shipping_status` (`shipping_status`) USING BTREE,
  KEY `pay_status` (`pay_status`) USING BTREE,
  KEY `shipping_id` (`shipping_id`) USING BTREE,
  KEY `pay_id` (`pay_id`) USING BTREE,
  KEY `extension_code` (`extension_code`,`extension_id`) USING BTREE,
  KEY `agency_id` (`agency_id`) USING BTREE
) ENGINE=MyISAM AUTO_INCREMENT=118936 DEFAULT CHARSET=utf8;

-- ----------------------------
-- Records of ecs_order_info
-- ----------------------------

-- ----------------------------
-- Table structure for `ecs_order_info_ext`
-- ----------------------------
DROP TABLE IF EXISTS `ecs_order_info_ext`;
CREATE TABLE `ecs_order_info_ext` (
  `ext_order_id` mediumint(8) NOT NULL AUTO_INCREMENT,
  `order_sn` varchar(20) DEFAULT NULL,
  `ext_sign` varchar(255) DEFAULT NULL,
  `ext_info` mediumtext,
  PRIMARY KEY (`ext_order_id`)
) ENGINE=MyISAM AUTO_INCREMENT=28 DEFAULT CHARSET=utf8;

-- ----------------------------
-- Records of ecs_order_info_ext
-- ----------------------------

-- ----------------------------
-- Table structure for `ecs_pack`
-- ----------------------------
DROP TABLE IF EXISTS `ecs_pack`;
CREATE TABLE `ecs_pack` (
  `pack_id` tinyint(3) unsigned NOT NULL AUTO_INCREMENT,
  `pack_name` varchar(120) NOT NULL DEFAULT '',
  `pack_img` varchar(255) NOT NULL DEFAULT '',
  `pack_fee` decimal(6,2) unsigned NOT NULL DEFAULT '0.00',
  `free_money` smallint(5) unsigned NOT NULL DEFAULT '0',
  `pack_desc` varchar(255) NOT NULL DEFAULT '',
  PRIMARY KEY (`pack_id`)
) ENGINE=MyISAM AUTO_INCREMENT=2 DEFAULT CHARSET=utf8;

-- ----------------------------
-- Records of ecs_pack
-- ----------------------------

-- ----------------------------
-- Table structure for `ecs_package_goods`
-- ----------------------------
DROP TABLE IF EXISTS `ecs_package_goods`;
CREATE TABLE `ecs_package_goods` (
  `package_id` mediumint(8) unsigned NOT NULL DEFAULT '0',
  `goods_id` mediumint(8) unsigned NOT NULL DEFAULT '0',
  `product_id` mediumint(8) unsigned NOT NULL DEFAULT '0',
  `goods_number` smallint(5) unsigned NOT NULL DEFAULT '1',
  `admin_id` tinyint(3) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`package_id`,`goods_id`,`admin_id`,`product_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- ----------------------------
-- Records of ecs_package_goods
-- ----------------------------

-- ----------------------------
-- Table structure for `ecs_payment`
-- ----------------------------
DROP TABLE IF EXISTS `ecs_payment`;
CREATE TABLE `ecs_payment` (
  `pay_id` tinyint(3) unsigned NOT NULL AUTO_INCREMENT,
  `pay_code` varchar(20) NOT NULL DEFAULT '',
  `pay_name` varchar(120) NOT NULL DEFAULT '',
  `pay_fee` varchar(10) NOT NULL DEFAULT '0',
  `pay_desc` text NOT NULL,
  `pay_order` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `pay_config` text NOT NULL,
  `enabled` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `is_cod` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `is_online` tinyint(1) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`pay_id`),
  UNIQUE KEY `pay_code` (`pay_code`) USING BTREE
) ENGINE=MyISAM AUTO_INCREMENT=82 DEFAULT CHARSET=utf8;

-- ----------------------------
-- Records of ecs_payment
-- ----------------------------

-- ----------------------------
-- Table structure for `ecs_pay_log`
-- ----------------------------
DROP TABLE IF EXISTS `ecs_pay_log`;
CREATE TABLE `ecs_pay_log` (
  `log_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `order_id` mediumint(8) unsigned NOT NULL DEFAULT '0',
  `order_amount` decimal(10,2) unsigned NOT NULL,
  `order_type` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `is_paid` tinyint(1) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`log_id`)
) ENGINE=MyISAM AUTO_INCREMENT=160 DEFAULT CHARSET=utf8;

-- ----------------------------
-- Records of ecs_pay_log
-- ----------------------------

-- ----------------------------
-- Table structure for `ecs_plugins`
-- ----------------------------
DROP TABLE IF EXISTS `ecs_plugins`;
CREATE TABLE `ecs_plugins` (
  `code` varchar(30) NOT NULL DEFAULT '',
  `version` varchar(10) NOT NULL DEFAULT '',
  `library` varchar(255) NOT NULL DEFAULT '',
  `assign` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `install_date` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`code`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- ----------------------------
-- Records of ecs_plugins
-- ----------------------------

-- ----------------------------
-- Table structure for `ecs_products`
-- ----------------------------
DROP TABLE IF EXISTS `ecs_products`;
CREATE TABLE `ecs_products` (
  `product_id` mediumint(8) unsigned NOT NULL AUTO_INCREMENT,
  `goods_id` mediumint(8) unsigned NOT NULL DEFAULT '0',
  `goods_attr` varchar(50) DEFAULT NULL,
  `product_sn` varchar(60) DEFAULT NULL,
  `product_number` smallint(5) unsigned DEFAULT '0',
  PRIMARY KEY (`product_id`)
) ENGINE=MyISAM AUTO_INCREMENT=15 DEFAULT CHARSET=utf8;

-- ----------------------------
-- Records of ecs_products
-- ----------------------------

-- ----------------------------
-- Table structure for `ecs_pseudo_record`
-- ----------------------------
DROP TABLE IF EXISTS `ecs_pseudo_record`;
CREATE TABLE `ecs_pseudo_record` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '主键自增id',
  `uid` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '用户id',
  `code` varchar(40) NOT NULL DEFAULT '' COMMENT '微信扫描url带来的code',
  `channel` varchar(10) NOT NULL DEFAULT '' COMMENT '瓷肌App的标示',
  `mobile` varchar(50) NOT NULL DEFAULT '' COMMENT '用户的手机',
  `token` varchar(40) NOT NULL DEFAULT '' COMMENT '用户识别号',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=7 DEFAULT CHARSET=utf8 COMMENT='记录仿伪用户信息';

-- ----------------------------
-- Records of ecs_pseudo_record
-- ----------------------------

-- ----------------------------
-- Table structure for `ecs_region`
-- ----------------------------
DROP TABLE IF EXISTS `ecs_region`;
CREATE TABLE `ecs_region` (
  `region_id` smallint(5) unsigned NOT NULL AUTO_INCREMENT COMMENT '表示该地区的id',
  `parent_id` smallint(5) unsigned NOT NULL DEFAULT '0' COMMENT '该地区的上一个节点的地区id',
  `region_name` varchar(120) NOT NULL DEFAULT '' COMMENT ' 地区的名字',
  `region_type` tinyint(1) NOT NULL DEFAULT '2' COMMENT '该地区的下一个节点的地区id',
  `agency_id` smallint(5) unsigned NOT NULL DEFAULT '0' COMMENT '办事处的id,这里有一个bug,同一个省不能有多个办事处,该字段只记录最新的那个办事处的id',
  PRIMARY KEY (`region_id`),
  KEY `parent_id` (`parent_id`),
  KEY `region_type` (`region_type`),
  KEY `agency_id` (`agency_id`)
) ENGINE=MyISAM AUTO_INCREMENT=48635 DEFAULT CHARSET=utf8 COMMENT=' 地区列表';

-- ----------------------------
-- Records of ecs_region
-- ----------------------------

-- ----------------------------
-- Table structure for `ecs_region_旧`
-- ----------------------------
DROP TABLE IF EXISTS `ecs_region_旧`;
CREATE TABLE `ecs_region_旧` (
  `region_id` smallint(5) unsigned NOT NULL AUTO_INCREMENT,
  `parent_id` smallint(5) unsigned NOT NULL DEFAULT '0',
  `region_name` varchar(120) NOT NULL DEFAULT '',
  `region_type` tinyint(1) NOT NULL DEFAULT '2',
  `agency_id` smallint(5) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`region_id`),
  KEY `parent_id` (`parent_id`) USING BTREE,
  KEY `region_type` (`region_type`) USING BTREE,
  KEY `agency_id` (`agency_id`) USING BTREE
) ENGINE=MyISAM AUTO_INCREMENT=3409 DEFAULT CHARSET=utf8 COMMENT='地区表（旧的，2016-06-27改版废弃）';

-- ----------------------------
-- Records of ecs_region_旧
-- ----------------------------

-- ----------------------------
-- Table structure for `ecs_reg_extend_info`
-- ----------------------------
DROP TABLE IF EXISTS `ecs_reg_extend_info`;
CREATE TABLE `ecs_reg_extend_info` (
  `Id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` mediumint(8) unsigned NOT NULL,
  `reg_field_id` int(10) unsigned NOT NULL,
  `content` text NOT NULL,
  PRIMARY KEY (`Id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- ----------------------------
-- Records of ecs_reg_extend_info
-- ----------------------------

-- ----------------------------
-- Table structure for `ecs_reg_fields`
-- ----------------------------
DROP TABLE IF EXISTS `ecs_reg_fields`;
CREATE TABLE `ecs_reg_fields` (
  `id` tinyint(3) unsigned NOT NULL AUTO_INCREMENT,
  `reg_field_name` varchar(60) NOT NULL,
  `dis_order` tinyint(3) unsigned NOT NULL DEFAULT '100',
  `display` tinyint(1) unsigned NOT NULL DEFAULT '1',
  `type` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `is_need` tinyint(1) unsigned NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=7 DEFAULT CHARSET=utf8;

-- ----------------------------
-- Records of ecs_reg_fields
-- ----------------------------

-- ----------------------------
-- Table structure for `ecs_reptile_log`
-- ----------------------------
DROP TABLE IF EXISTS `ecs_reptile_log`;
CREATE TABLE `ecs_reptile_log` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `ip` varchar(20) DEFAULT '' COMMENT '访问者ip',
  `view_time` int(11) DEFAULT NULL COMMENT '访问时间',
  `view_num` int(11) DEFAULT '0' COMMENT '访问次数',
  `content` text,
  `view_page` varchar(1000) DEFAULT '' COMMENT '推广的页面链接',
  `view_other` varchar(1000) DEFAULT '',
  `url_num` smallint(5) DEFAULT '0' COMMENT '推广链接编号',
  PRIMARY KEY (`id`),
  KEY `ip` (`ip`) USING BTREE,
  KEY `view_page` (`view_page`(333)) USING BTREE,
  KEY `view_other` (`view_other`(333)),
  KEY `url_num` (`url_num`)
) ENGINE=MyISAM AUTO_INCREMENT=430 DEFAULT CHARSET=utf8 COMMENT='爬虫记录表';

-- ----------------------------
-- Records of ecs_reptile_log
-- ----------------------------

-- ----------------------------
-- Table structure for `ecs_reptile_log_`
-- ----------------------------
DROP TABLE IF EXISTS `ecs_reptile_log_`;
CREATE TABLE `ecs_reptile_log_` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `ip` varchar(20) DEFAULT '' COMMENT '访问者ip',
  `view_time` int(11) DEFAULT NULL COMMENT '访问时间',
  `view_num` int(11) DEFAULT '0' COMMENT '访问次数',
  `content` text,
  PRIMARY KEY (`id`),
  KEY `ip` (`ip`) USING BTREE
) ENGINE=MyISAM AUTO_INCREMENT=265 DEFAULT CHARSET=utf8 COMMENT='爬虫记录表';

-- ----------------------------
-- Records of ecs_reptile_log_
-- ----------------------------

-- ----------------------------
-- Table structure for `ecs_role`
-- ----------------------------
DROP TABLE IF EXISTS `ecs_role`;
CREATE TABLE `ecs_role` (
  `role_id` smallint(5) unsigned NOT NULL AUTO_INCREMENT,
  `role_name` varchar(60) NOT NULL DEFAULT '',
  `action_list` text NOT NULL,
  `role_describe` text,
  PRIMARY KEY (`role_id`),
  KEY `user_name` (`role_name`) USING BTREE
) ENGINE=MyISAM AUTO_INCREMENT=2 DEFAULT CHARSET=utf8;

-- ----------------------------
-- Records of ecs_role
-- ----------------------------

-- ----------------------------
-- Table structure for `ecs_searchengine`
-- ----------------------------
DROP TABLE IF EXISTS `ecs_searchengine`;
CREATE TABLE `ecs_searchengine` (
  `date` date NOT NULL DEFAULT '0000-00-00',
  `searchengine` varchar(20) NOT NULL DEFAULT '',
  `count` mediumint(8) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`date`,`searchengine`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- ----------------------------
-- Records of ecs_searchengine
-- ----------------------------

-- ----------------------------
-- Table structure for `ecs_sessions`
-- ----------------------------
DROP TABLE IF EXISTS `ecs_sessions`;
CREATE TABLE `ecs_sessions` (
  `sesskey` char(32) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL DEFAULT '',
  `expiry` int(10) unsigned NOT NULL DEFAULT '0',
  `userid` mediumint(8) unsigned NOT NULL DEFAULT '0',
  `adminid` mediumint(8) unsigned NOT NULL DEFAULT '0',
  `ip` char(15) NOT NULL DEFAULT '',
  `user_name` varchar(60) NOT NULL,
  `user_rank` tinyint(3) NOT NULL,
  `discount` decimal(3,2) NOT NULL,
  `email` varchar(60) NOT NULL,
  `data` char(255) NOT NULL DEFAULT '',
  PRIMARY KEY (`sesskey`),
  KEY `expiry` (`expiry`) USING HASH
) ENGINE=MEMORY DEFAULT CHARSET=utf8;

-- ----------------------------
-- Records of ecs_sessions
-- ----------------------------

-- ----------------------------
-- Table structure for `ecs_sessions_data`
-- ----------------------------
DROP TABLE IF EXISTS `ecs_sessions_data`;
CREATE TABLE `ecs_sessions_data` (
  `sesskey` varchar(32) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL DEFAULT '',
  `expiry` int(10) unsigned NOT NULL DEFAULT '0',
  `data` longtext NOT NULL,
  PRIMARY KEY (`sesskey`),
  KEY `expiry` (`expiry`) USING BTREE
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- ----------------------------
-- Records of ecs_sessions_data
-- ----------------------------

-- ----------------------------
-- Table structure for `ecs_share_receive`
-- ----------------------------
DROP TABLE IF EXISTS `ecs_share_receive`;
CREATE TABLE `ecs_share_receive` (
  `coupon` char(10) DEFAULT NULL COMMENT '优惠券号码（与后台优惠券无关联）',
  `locktime` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '锁定时间',
  `usetime` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '使用时间',
  UNIQUE KEY `coupon` (`coupon`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='活动分享领取优惠券号码';

-- ----------------------------
-- Records of ecs_share_receive
-- ----------------------------

-- ----------------------------
-- Table structure for `ecs_shipping`
-- ----------------------------
DROP TABLE IF EXISTS `ecs_shipping`;
CREATE TABLE `ecs_shipping` (
  `shipping_id` tinyint(3) unsigned NOT NULL AUTO_INCREMENT,
  `shipping_code` varchar(20) NOT NULL DEFAULT '',
  `shipping_name` varchar(120) NOT NULL DEFAULT '',
  `shipping_desc` varchar(255) NOT NULL DEFAULT '',
  `insure` varchar(10) NOT NULL DEFAULT '0',
  `support_cod` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `enabled` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `shipping_print` text NOT NULL,
  `print_bg` varchar(255) DEFAULT NULL,
  `config_lable` text,
  `print_model` tinyint(1) DEFAULT '0',
  PRIMARY KEY (`shipping_id`),
  KEY `shipping_code` (`shipping_code`,`enabled`) USING BTREE
) ENGINE=MyISAM AUTO_INCREMENT=8 DEFAULT CHARSET=utf8;

-- ----------------------------
-- Records of ecs_shipping
-- ----------------------------

-- ----------------------------
-- Table structure for `ecs_shipping_area`
-- ----------------------------
DROP TABLE IF EXISTS `ecs_shipping_area`;
CREATE TABLE `ecs_shipping_area` (
  `shipping_area_id` smallint(5) unsigned NOT NULL AUTO_INCREMENT,
  `shipping_area_name` varchar(150) NOT NULL DEFAULT '',
  `shipping_id` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `configure` text NOT NULL,
  PRIMARY KEY (`shipping_area_id`),
  KEY `shipping_id` (`shipping_id`) USING BTREE
) ENGINE=MyISAM AUTO_INCREMENT=5 DEFAULT CHARSET=utf8;

-- ----------------------------
-- Records of ecs_shipping_area
-- ----------------------------

-- ----------------------------
-- Table structure for `ecs_shop_config`
-- ----------------------------
DROP TABLE IF EXISTS `ecs_shop_config`;
CREATE TABLE `ecs_shop_config` (
  `id` smallint(5) unsigned NOT NULL AUTO_INCREMENT,
  `parent_id` smallint(5) unsigned NOT NULL DEFAULT '0',
  `code` varchar(30) NOT NULL DEFAULT '',
  `type` varchar(10) NOT NULL DEFAULT '',
  `store_range` varchar(255) NOT NULL DEFAULT '',
  `store_dir` varchar(255) NOT NULL DEFAULT '',
  `value` text NOT NULL,
  `sort_order` tinyint(3) unsigned NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`) USING BTREE,
  KEY `parent_id` (`parent_id`) USING BTREE
) ENGINE=MyISAM AUTO_INCREMENT=904 DEFAULT CHARSET=utf8;

-- ----------------------------
-- Records of ecs_shop_config
-- ----------------------------

-- ----------------------------
-- Table structure for `ecs_skin_test`
-- ----------------------------
DROP TABLE IF EXISTS `ecs_skin_test`;
CREATE TABLE `ecs_skin_test` (
  `id` mediumint(9) NOT NULL AUTO_INCREMENT,
  `feature` varchar(100) NOT NULL COMMENT '皮肤特性',
  `skin_color` varchar(100) NOT NULL COMMENT '肤色',
  `skin_problem` varchar(400) NOT NULL COMMENT '皮肤问题',
  `sex` varchar(50) NOT NULL COMMENT '性别',
  `datatype` varchar(15) NOT NULL COMMENT '日期类型',
  `birthdate` date NOT NULL COMMENT '生日日期类型',
  `phone` varchar(11) NOT NULL,
  `qq` varchar(20) NOT NULL,
  `ip` varchar(15) NOT NULL,
  `session_id` varchar(32) NOT NULL,
  `create_date` int(11) NOT NULL COMMENT '创建时间',
  `username` varchar(30) NOT NULL COMMENT '用户姓名',
  `times` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=30 DEFAULT CHARSET=utf8;

-- ----------------------------
-- Records of ecs_skin_test
-- ----------------------------

-- ----------------------------
-- Table structure for `ecs_snatch_log`
-- ----------------------------
DROP TABLE IF EXISTS `ecs_snatch_log`;
CREATE TABLE `ecs_snatch_log` (
  `log_id` mediumint(8) unsigned NOT NULL AUTO_INCREMENT,
  `snatch_id` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `user_id` mediumint(8) unsigned NOT NULL DEFAULT '0',
  `bid_price` decimal(10,2) NOT NULL DEFAULT '0.00',
  `bid_time` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`log_id`),
  KEY `snatch_id` (`snatch_id`) USING BTREE
) ENGINE=MyISAM AUTO_INCREMENT=3 DEFAULT CHARSET=utf8;

-- ----------------------------
-- Records of ecs_snatch_log
-- ----------------------------

-- ----------------------------
-- Table structure for `ecs_stats`
-- ----------------------------
DROP TABLE IF EXISTS `ecs_stats`;
CREATE TABLE `ecs_stats` (
  `access_time` int(10) unsigned NOT NULL DEFAULT '0',
  `ip_address` varchar(15) NOT NULL DEFAULT '',
  `visit_times` smallint(5) unsigned NOT NULL DEFAULT '1',
  `browser` varchar(60) NOT NULL DEFAULT '',
  `system` varchar(20) NOT NULL DEFAULT '',
  `language` varchar(20) NOT NULL DEFAULT '',
  `area` varchar(30) NOT NULL DEFAULT '',
  `referer_domain` varchar(100) NOT NULL DEFAULT '',
  `referer_path` varchar(200) NOT NULL DEFAULT '',
  `access_url` varchar(255) NOT NULL DEFAULT '',
  KEY `access_time` (`access_time`) USING BTREE
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- ----------------------------
-- Records of ecs_stats
-- ----------------------------

-- ----------------------------
-- Table structure for `ecs_suppliers`
-- ----------------------------
DROP TABLE IF EXISTS `ecs_suppliers`;
CREATE TABLE `ecs_suppliers` (
  `suppliers_id` smallint(5) unsigned NOT NULL AUTO_INCREMENT,
  `suppliers_name` varchar(255) DEFAULT NULL,
  `suppliers_desc` mediumtext,
  `is_check` tinyint(1) unsigned NOT NULL DEFAULT '1',
  PRIMARY KEY (`suppliers_id`)
) ENGINE=MyISAM AUTO_INCREMENT=3 DEFAULT CHARSET=utf8;

-- ----------------------------
-- Records of ecs_suppliers
-- ----------------------------

-- ----------------------------
-- Table structure for `ecs_survey`
-- ----------------------------
DROP TABLE IF EXISTS `ecs_survey`;
CREATE TABLE `ecs_survey` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `surname` varchar(12) NOT NULL DEFAULT '' COMMENT '用户称呼',
  `phone` varchar(255) NOT NULL DEFAULT '' COMMENT '电话',
  `address` varchar(255) NOT NULL DEFAULT '' COMMENT '地址',
  `sex` char(1) NOT NULL DEFAULT '女' COMMENT '性别，默认为女',
  `age` char(8) NOT NULL DEFAULT '' COMMENT '年龄区间',
  `status` varchar(255) NOT NULL DEFAULT '' COMMENT '感情状态',
  `wenti` varchar(255) NOT NULL DEFAULT '' COMMENT '皮肤问题',
  `zhiye` varchar(20) NOT NULL DEFAULT '' COMMENT '客户职业',
  `prices` varchar(255) NOT NULL DEFAULT '' COMMENT '购买物品价格区间',
  `xiguan` char(10) NOT NULL DEFAULT '' COMMENT '使用习惯单品或套装',
  `gm_time` char(10) NOT NULL DEFAULT '' COMMENT '多长时间购买一次',
  `shops` varchar(255) NOT NULL DEFAULT '' COMMENT '购物场所',
  `favourable` varchar(255) NOT NULL DEFAULT '' COMMENT '喜欢的优惠方式',
  `brand` varchar(255) NOT NULL DEFAULT '' COMMENT '护肤品品牌',
  `add_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '添加时间',
  `session_id` varchar(255) NOT NULL DEFAULT '',
  `ip` varchar(255) NOT NULL DEFAULT '',
  `bonus_sn` varchar(20) DEFAULT '' COMMENT '优惠卷码',
  `type` tinyint(4) DEFAULT '0' COMMENT '问卷类型',
  PRIMARY KEY (`id`),
  KEY `bonus_sn` (`bonus_sn`)
) ENGINE=MyISAM AUTO_INCREMENT=203 DEFAULT CHARSET=utf8 COMMENT='调查问卷表';

-- ----------------------------
-- Records of ecs_survey
-- ----------------------------

-- ----------------------------
-- Table structure for `ecs_tag`
-- ----------------------------
DROP TABLE IF EXISTS `ecs_tag`;
CREATE TABLE `ecs_tag` (
  `tag_id` mediumint(8) NOT NULL AUTO_INCREMENT,
  `user_id` mediumint(8) unsigned NOT NULL DEFAULT '0',
  `goods_id` mediumint(8) unsigned NOT NULL DEFAULT '0',
  `tag_words` varchar(255) NOT NULL DEFAULT '',
  PRIMARY KEY (`tag_id`),
  KEY `user_id` (`user_id`) USING BTREE,
  KEY `goods_id` (`goods_id`) USING BTREE
) ENGINE=MyISAM AUTO_INCREMENT=8 DEFAULT CHARSET=utf8;

-- ----------------------------
-- Records of ecs_tag
-- ----------------------------

-- ----------------------------
-- Table structure for `ecs_template`
-- ----------------------------
DROP TABLE IF EXISTS `ecs_template`;
CREATE TABLE `ecs_template` (
  `filename` varchar(30) NOT NULL DEFAULT '',
  `region` varchar(40) NOT NULL DEFAULT '',
  `library` varchar(40) NOT NULL DEFAULT '',
  `sort_order` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `id` smallint(5) unsigned NOT NULL DEFAULT '0',
  `number` tinyint(1) unsigned NOT NULL DEFAULT '5',
  `type` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `theme` varchar(60) NOT NULL DEFAULT '',
  `remarks` varchar(30) NOT NULL DEFAULT '',
  KEY `filename` (`filename`,`region`) USING BTREE,
  KEY `theme` (`theme`) USING BTREE,
  KEY `remarks` (`remarks`) USING BTREE
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- ----------------------------
-- Records of ecs_template
-- ----------------------------

-- ----------------------------
-- Table structure for `ecs_topic`
-- ----------------------------
DROP TABLE IF EXISTS `ecs_topic`;
CREATE TABLE `ecs_topic` (
  `topic_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL DEFAULT '''''',
  `intro` text NOT NULL,
  `start_time` int(11) NOT NULL DEFAULT '0',
  `end_time` int(10) NOT NULL DEFAULT '0',
  `data` text NOT NULL,
  `template` varchar(255) NOT NULL DEFAULT '''''',
  `css` text NOT NULL,
  `topic_img` varchar(255) DEFAULT NULL,
  `title_pic` varchar(255) DEFAULT NULL,
  `base_style` char(6) DEFAULT NULL,
  `htmls` mediumtext,
  `keywords` varchar(255) DEFAULT NULL,
  `description` varchar(255) DEFAULT NULL,
  KEY `topic_id` (`topic_id`) USING BTREE
) ENGINE=MyISAM AUTO_INCREMENT=2 DEFAULT CHARSET=utf8;

-- ----------------------------
-- Records of ecs_topic
-- ----------------------------

-- ----------------------------
-- Table structure for `ecs_tryout`
-- ----------------------------
DROP TABLE IF EXISTS `ecs_tryout`;
CREATE TABLE `ecs_tryout` (
  `id` mediumint(8) NOT NULL AUTO_INCREMENT,
  `goods_name` varchar(255) NOT NULL,
  `consignee` varchar(50) NOT NULL,
  `sex` varchar(10) NOT NULL,
  `address` varchar(255) NOT NULL,
  `mobile` varchar(50) NOT NULL,
  `tel` varchar(50) NOT NULL,
  `message` text NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=10 DEFAULT CHARSET=utf8;

-- ----------------------------
-- Records of ecs_tryout
-- ----------------------------

-- ----------------------------
-- Table structure for `ecs_users`
-- ----------------------------
DROP TABLE IF EXISTS `ecs_users`;
CREATE TABLE `ecs_users` (
  `user_id` mediumint(8) unsigned NOT NULL AUTO_INCREMENT,
  `email` varchar(60) NOT NULL DEFAULT '',
  `user_name` varchar(60) NOT NULL DEFAULT '',
  `password` varchar(32) NOT NULL DEFAULT '',
  `question` varchar(255) NOT NULL DEFAULT '',
  `answer` varchar(255) NOT NULL DEFAULT '',
  `sex` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `birthday` date NOT NULL DEFAULT '0000-00-00',
  `user_money` decimal(10,2) NOT NULL DEFAULT '0.00',
  `frozen_money` decimal(10,2) NOT NULL DEFAULT '0.00',
  `pay_points` int(10) unsigned NOT NULL DEFAULT '0',
  `rank_points` int(10) unsigned NOT NULL DEFAULT '0',
  `address_id` mediumint(8) unsigned NOT NULL DEFAULT '0',
  `reg_time` int(10) unsigned NOT NULL DEFAULT '0',
  `last_login` int(11) unsigned NOT NULL DEFAULT '0',
  `last_time` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `last_ip` varchar(15) NOT NULL DEFAULT '',
  `visit_count` smallint(5) unsigned NOT NULL DEFAULT '0',
  `user_rank` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `is_special` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `salt` varchar(10) NOT NULL DEFAULT '0',
  `parent_id` mediumint(9) NOT NULL DEFAULT '0',
  `flag` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `alias` varchar(60) NOT NULL,
  `msn` varchar(60) NOT NULL,
  `qq` varchar(20) NOT NULL,
  `office_phone` varchar(20) NOT NULL,
  `home_phone` varchar(20) NOT NULL,
  `mobile_phone` varchar(20) NOT NULL,
  `is_validated` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `credit_line` decimal(10,2) unsigned NOT NULL,
  `passwd_question` varchar(50) DEFAULT NULL,
  `passwd_answer` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`user_id`),
  UNIQUE KEY `user_name` (`user_name`) USING BTREE,
  KEY `email` (`email`) USING BTREE,
  KEY `parent_id` (`parent_id`) USING BTREE,
  KEY `flag` (`flag`) USING BTREE
) ENGINE=MyISAM AUTO_INCREMENT=21 DEFAULT CHARSET=utf8;

-- ----------------------------
-- Records of ecs_users
-- ----------------------------

-- ----------------------------
-- Table structure for `ecs_user_account`
-- ----------------------------
DROP TABLE IF EXISTS `ecs_user_account`;
CREATE TABLE `ecs_user_account` (
  `id` mediumint(8) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` mediumint(8) unsigned NOT NULL DEFAULT '0',
  `admin_user` varchar(255) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `add_time` int(10) NOT NULL DEFAULT '0',
  `paid_time` int(10) NOT NULL DEFAULT '0',
  `admin_note` varchar(255) NOT NULL,
  `user_note` varchar(255) NOT NULL,
  `process_type` tinyint(1) NOT NULL DEFAULT '0',
  `payment` varchar(90) NOT NULL,
  `is_paid` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`) USING BTREE,
  KEY `is_paid` (`is_paid`) USING BTREE
) ENGINE=MyISAM AUTO_INCREMENT=2 DEFAULT CHARSET=utf8;

-- ----------------------------
-- Records of ecs_user_account
-- ----------------------------

-- ----------------------------
-- Table structure for `ecs_user_address`
-- ----------------------------
DROP TABLE IF EXISTS `ecs_user_address`;
CREATE TABLE `ecs_user_address` (
  `address_id` mediumint(8) unsigned NOT NULL AUTO_INCREMENT,
  `address_name` varchar(50) NOT NULL DEFAULT '',
  `user_id` mediumint(8) unsigned NOT NULL DEFAULT '0',
  `consignee` varchar(60) NOT NULL DEFAULT '',
  `email` varchar(60) NOT NULL DEFAULT '',
  `country` smallint(5) NOT NULL DEFAULT '0',
  `province` smallint(5) NOT NULL DEFAULT '0',
  `city` smallint(5) NOT NULL DEFAULT '0',
  `district` smallint(5) NOT NULL DEFAULT '0',
  `address` varchar(120) NOT NULL DEFAULT '',
  `zipcode` varchar(60) NOT NULL DEFAULT '',
  `tel` varchar(60) NOT NULL DEFAULT '',
  `mobile` varchar(60) NOT NULL DEFAULT '',
  `sign_building` varchar(120) NOT NULL DEFAULT '',
  `best_time` varchar(120) NOT NULL DEFAULT '',
  PRIMARY KEY (`address_id`),
  KEY `user_id` (`user_id`) USING BTREE
) ENGINE=MyISAM AUTO_INCREMENT=4 DEFAULT CHARSET=utf8;

-- ----------------------------
-- Records of ecs_user_address
-- ----------------------------

-- ----------------------------
-- Table structure for `ecs_user_bonus`
-- ----------------------------
DROP TABLE IF EXISTS `ecs_user_bonus`;
CREATE TABLE `ecs_user_bonus` (
  `bonus_id` mediumint(8) unsigned NOT NULL AUTO_INCREMENT,
  `bonus_type_id` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `bonus_sn` bigint(20) unsigned NOT NULL DEFAULT '0',
  `user_id` mediumint(8) unsigned NOT NULL DEFAULT '0',
  `mobile` varchar(50) NOT NULL,
  `used_time` int(10) unsigned NOT NULL DEFAULT '0',
  `order_id` mediumint(8) unsigned NOT NULL DEFAULT '0',
  `emailed` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `user_name` varchar(50) NOT NULL,
  PRIMARY KEY (`bonus_id`),
  KEY `user_id` (`user_id`) USING BTREE
) ENGINE=MyISAM AUTO_INCREMENT=45 DEFAULT CHARSET=utf8;

-- ----------------------------
-- Records of ecs_user_bonus
-- ----------------------------

-- ----------------------------
-- Table structure for `ecs_user_feed`
-- ----------------------------
DROP TABLE IF EXISTS `ecs_user_feed`;
CREATE TABLE `ecs_user_feed` (
  `feed_id` mediumint(8) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` mediumint(8) unsigned NOT NULL DEFAULT '0',
  `value_id` mediumint(8) unsigned NOT NULL DEFAULT '0',
  `goods_id` mediumint(8) unsigned NOT NULL DEFAULT '0',
  `feed_type` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `is_feed` tinyint(1) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`feed_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- ----------------------------
-- Records of ecs_user_feed
-- ----------------------------

-- ----------------------------
-- Table structure for `ecs_user_rank`
-- ----------------------------
DROP TABLE IF EXISTS `ecs_user_rank`;
CREATE TABLE `ecs_user_rank` (
  `rank_id` tinyint(3) unsigned NOT NULL AUTO_INCREMENT,
  `rank_name` varchar(30) NOT NULL DEFAULT '',
  `min_points` int(10) unsigned NOT NULL DEFAULT '0',
  `max_points` int(10) unsigned NOT NULL DEFAULT '0',
  `discount` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `show_price` tinyint(1) unsigned NOT NULL DEFAULT '1',
  `special_rank` tinyint(1) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`rank_id`)
) ENGINE=MyISAM AUTO_INCREMENT=4 DEFAULT CHARSET=utf8;

-- ----------------------------
-- Records of ecs_user_rank
-- ----------------------------

-- ----------------------------
-- Table structure for `ecs_user_recommend`
-- ----------------------------
DROP TABLE IF EXISTS `ecs_user_recommend`;
CREATE TABLE `ecs_user_recommend` (
  `rid` mediumint(8) NOT NULL AUTO_INCREMENT,
  `user_name` varchar(50) NOT NULL,
  `recommend_name` varchar(50) NOT NULL,
  `is_create` tinyint(1) NOT NULL DEFAULT '0',
  UNIQUE KEY `rid` (`rid`) USING BTREE
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- ----------------------------
-- Records of ecs_user_recommend
-- ----------------------------

-- ----------------------------
-- Table structure for `ecs_user_signin_log`
-- ----------------------------
DROP TABLE IF EXISTS `ecs_user_signin_log`;
CREATE TABLE `ecs_user_signin_log` (
  `id` int(10) NOT NULL AUTO_INCREMENT COMMENT '主键',
  `bind_id` int(10) DEFAULT '0' COMMENT '对于bind_user表的user_id',
  `points` int(10) DEFAULT '0' COMMENT '签到获得积分',
  `days` tinyint(4) DEFAULT '0' COMMENT '连续签到天数',
  `add_time` int(10) DEFAULT '0' COMMENT '签到时间',
  PRIMARY KEY (`id`),
  KEY `bind_id` (`bind_id`) USING BTREE
) ENGINE=MyISAM AUTO_INCREMENT=113 DEFAULT CHARSET=utf8;

-- ----------------------------
-- Records of ecs_user_signin_log
-- ----------------------------

-- ----------------------------
-- Table structure for `ecs_user_skin`
-- ----------------------------
DROP TABLE IF EXISTS `ecs_user_skin`;
CREATE TABLE `ecs_user_skin` (
  `id` mediumint(9) NOT NULL AUTO_INCREMENT,
  `pic` varchar(255) NOT NULL COMMENT '图片',
  `tel` varchar(60) NOT NULL COMMENT '电话',
  `add_time` int(10) NOT NULL COMMENT '添加时间',
  `sync_status` tinyint(4) NOT NULL COMMENT '同步标志',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=392 DEFAULT CHARSET=utf8;

-- ----------------------------
-- Records of ecs_user_skin
-- ----------------------------

-- ----------------------------
-- Table structure for `ecs_virtual_card`
-- ----------------------------
DROP TABLE IF EXISTS `ecs_virtual_card`;
CREATE TABLE `ecs_virtual_card` (
  `card_id` mediumint(8) NOT NULL AUTO_INCREMENT,
  `goods_id` mediumint(8) unsigned NOT NULL DEFAULT '0',
  `card_sn` varchar(60) NOT NULL DEFAULT '',
  `card_password` varchar(60) NOT NULL DEFAULT '',
  `add_date` int(11) NOT NULL DEFAULT '0',
  `end_date` int(11) NOT NULL DEFAULT '0',
  `is_saled` tinyint(1) NOT NULL DEFAULT '0',
  `order_sn` varchar(20) NOT NULL DEFAULT '',
  `crc32` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`card_id`),
  KEY `goods_id` (`goods_id`) USING BTREE,
  KEY `car_sn` (`card_sn`) USING BTREE,
  KEY `is_saled` (`is_saled`) USING BTREE
) ENGINE=MyISAM AUTO_INCREMENT=16 DEFAULT CHARSET=utf8;

-- ----------------------------
-- Records of ecs_virtual_card
-- ----------------------------

-- ----------------------------
-- Table structure for `ecs_volume_price`
-- ----------------------------
DROP TABLE IF EXISTS `ecs_volume_price`;
CREATE TABLE `ecs_volume_price` (
  `price_type` tinyint(1) unsigned NOT NULL,
  `goods_id` mediumint(8) unsigned NOT NULL,
  `volume_number` smallint(5) unsigned NOT NULL DEFAULT '0',
  `volume_price` decimal(10,2) NOT NULL DEFAULT '0.00',
  PRIMARY KEY (`price_type`,`goods_id`,`volume_number`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- ----------------------------
-- Records of ecs_volume_price
-- ----------------------------

-- ----------------------------
-- Table structure for `ecs_vote`
-- ----------------------------
DROP TABLE IF EXISTS `ecs_vote`;
CREATE TABLE `ecs_vote` (
  `vote_id` smallint(5) unsigned NOT NULL AUTO_INCREMENT,
  `vote_name` varchar(250) NOT NULL DEFAULT '',
  `start_time` int(11) unsigned NOT NULL DEFAULT '0',
  `end_time` int(11) unsigned NOT NULL DEFAULT '0',
  `can_multi` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `vote_count` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`vote_id`)
) ENGINE=MyISAM AUTO_INCREMENT=2 DEFAULT CHARSET=utf8;

-- ----------------------------
-- Records of ecs_vote
-- ----------------------------

-- ----------------------------
-- Table structure for `ecs_vote_log`
-- ----------------------------
DROP TABLE IF EXISTS `ecs_vote_log`;
CREATE TABLE `ecs_vote_log` (
  `log_id` mediumint(8) unsigned NOT NULL AUTO_INCREMENT,
  `vote_id` smallint(5) unsigned NOT NULL DEFAULT '0',
  `ip_address` varchar(15) NOT NULL DEFAULT '',
  `vote_time` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`log_id`),
  KEY `vote_id` (`vote_id`) USING BTREE
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- ----------------------------
-- Records of ecs_vote_log
-- ----------------------------

-- ----------------------------
-- Table structure for `ecs_vote_option`
-- ----------------------------
DROP TABLE IF EXISTS `ecs_vote_option`;
CREATE TABLE `ecs_vote_option` (
  `option_id` smallint(5) unsigned NOT NULL AUTO_INCREMENT,
  `vote_id` smallint(5) unsigned NOT NULL DEFAULT '0',
  `option_name` varchar(250) NOT NULL DEFAULT '',
  `option_count` int(8) unsigned NOT NULL DEFAULT '0',
  `option_order` tinyint(3) unsigned NOT NULL DEFAULT '100',
  PRIMARY KEY (`option_id`),
  KEY `vote_id` (`vote_id`) USING BTREE
) ENGINE=MyISAM AUTO_INCREMENT=4 DEFAULT CHARSET=utf8;

-- ----------------------------
-- Records of ecs_vote_option
-- ----------------------------

-- ----------------------------
-- Table structure for `ecs_wholesale`
-- ----------------------------
DROP TABLE IF EXISTS `ecs_wholesale`;
CREATE TABLE `ecs_wholesale` (
  `act_id` mediumint(8) unsigned NOT NULL AUTO_INCREMENT,
  `goods_id` mediumint(8) unsigned NOT NULL,
  `goods_name` varchar(255) NOT NULL,
  `rank_ids` varchar(255) NOT NULL,
  `prices` text NOT NULL,
  `enabled` tinyint(3) unsigned NOT NULL,
  PRIMARY KEY (`act_id`),
  KEY `goods_id` (`goods_id`) USING BTREE
) ENGINE=MyISAM AUTO_INCREMENT=2 DEFAULT CHARSET=utf8;

-- ----------------------------
-- Records of ecs_wholesale
-- ----------------------------

-- ----------------------------
-- Table structure for `ecs_wx_express_cache`
-- ----------------------------
DROP TABLE IF EXISTS `ecs_wx_express_cache`;
CREATE TABLE `ecs_wx_express_cache` (
  `rqsn` varchar(20) DEFAULT NULL COMMENT '队列序列号',
  `mobile` varchar(60) DEFAULT NULL COMMENT '手机号码（加密后的）',
  `invoice_no` varchar(40) DEFAULT NULL COMMENT '快递单号',
  `xml` text COMMENT '接口返回的xml内容',
  `addtime` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '添加时间',
  KEY `rqsn` (`rqsn`),
  KEY `mobile` (`mobile`),
  KEY `invoice_no` (`invoice_no`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='微信物流查询';

-- ----------------------------
-- Records of ecs_wx_express_cache
-- ----------------------------

-- ----------------------------
-- Table structure for `ecs_wx_log`
-- ----------------------------
DROP TABLE IF EXISTS `ecs_wx_log`;
CREATE TABLE `ecs_wx_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '查询id',
  `mobile` varchar(50) NOT NULL COMMENT '查询手机',
  `order_id` varchar(100) NOT NULL COMMENT '订单号',
  `invoice_no` varchar(50) NOT NULL COMMENT '快递单号',
  `msg` text NOT NULL COMMENT '推送信息',
  `update_time` int(11) NOT NULL COMMENT '更新时间',
  `add_time` int(11) NOT NULL COMMENT '添加时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `mobile` (`mobile`,`order_id`) USING BTREE,
  KEY `order_id` (`order_id`) USING BTREE
) ENGINE=MyISAM AUTO_INCREMENT=3 DEFAULT CHARSET=utf8 COMMENT='查询追踪表';

-- ----------------------------
-- Records of ecs_wx_log
-- ----------------------------

-- ----------------------------
-- Table structure for `ecs_wx_menu`
-- ----------------------------
DROP TABLE IF EXISTS `ecs_wx_menu`;
CREATE TABLE `ecs_wx_menu` (
  `menu_id` int(11) NOT NULL AUTO_INCREMENT,
  `menu_name` varchar(50) DEFAULT NULL COMMENT '菜单名称',
  `parent_id` int(11) DEFAULT NULL COMMENT '父级ID',
  `sort_order` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '排序',
  `is_close` tinyint(1) DEFAULT '0' COMMENT '是否关闭',
  `action` enum('media','click','url') DEFAULT 'url',
  `action_param` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`menu_id`)
) ENGINE=MyISAM AUTO_INCREMENT=88 DEFAULT CHARSET=utf8 COMMENT='微信自定义菜单表';

-- ----------------------------
-- Records of ecs_wx_menu
-- ----------------------------

-- ----------------------------
-- Table structure for `ecs_wx_msg`
-- ----------------------------
DROP TABLE IF EXISTS `ecs_wx_msg`;
CREATE TABLE `ecs_wx_msg` (
  `msg_id` int(11) NOT NULL AUTO_INCREMENT COMMENT '推送id',
  `open_id` varchar(255) NOT NULL COMMENT '用户openid',
  `content` text NOT NULL COMMENT '推送内容',
  `order_sn` varchar(255) NOT NULL COMMENT '订单号',
  `invoice_no` varchar(255) NOT NULL COMMENT '物流单号',
  `type` varchar(255) NOT NULL COMMENT '推送方式',
  `errcode` int(11) NOT NULL COMMENT '微信推送状态码',
  `errmsg` varchar(255) NOT NULL COMMENT '微信推送返回信息',
  `addtime` int(11) NOT NULL COMMENT '推送时间',
  PRIMARY KEY (`msg_id`),
  KEY `order_sn` (`order_sn`) USING BTREE,
  KEY `invoice_no` (`invoice_no`) USING BTREE,
  KEY `open_id` (`open_id`) USING BTREE
) ENGINE=MyISAM AUTO_INCREMENT=428 DEFAULT CHARSET=utf8 COMMENT='微信推送消息日志表';

-- ----------------------------
-- Records of ecs_wx_msg
-- ----------------------------

-- ----------------------------
-- Table structure for `rotates_temp`
-- ----------------------------
DROP TABLE IF EXISTS `rotates_temp`;
CREATE TABLE `rotates_temp` (
  `id` mediumint(8) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(60) NOT NULL COMMENT '姓名',
  `mobile` varchar(60) NOT NULL COMMENT '手机',
  `rotate_id` int(11) NOT NULL DEFAULT '0' COMMENT '活动id(表wx_rotates.id)',
  `address` varchar(255) NOT NULL COMMENT '地址',
  `prize_level` varchar(10) DEFAULT '' COMMENT '中奖级别',
  `prize_goods_id` mediumint(11) unsigned NOT NULL DEFAULT '0' COMMENT '奖品的商品ID',
  `prize_goods_name` varchar(255) NOT NULL COMMENT '商品名称',
  `prize_goods_package` text NOT NULL COMMENT '存放套装子商品，空为单品',
  `create_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '添加时间',
  `sync` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '同步标志0:未同步1同步',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=41 DEFAULT CHARSET=utf8 COMMENT='临时抽奖活动表';

-- ----------------------------
-- Records of rotates_temp
-- ----------------------------

-- ----------------------------
-- Table structure for `wx_user_bind`
-- ----------------------------
DROP TABLE IF EXISTS `wx_user_bind`;
CREATE TABLE `wx_user_bind` (
  `id` mediumint(8) unsigned NOT NULL AUTO_INCREMENT,
  `token` varchar(50) NOT NULL DEFAULT '' COMMENT '授权标识，用于对应上微信，qq等。',
  `user_id` mediumint(8) unsigned NOT NULL COMMENT '用户表id',
  `bind_type` tinyint(3) unsigned NOT NULL DEFAULT '1' COMMENT '平台标识：1=微信',
  `status` tinyint(3) unsigned DEFAULT NULL COMMENT '绑定状态：1 绑定  9 取消绑定',
  `add_time` int(10) unsigned NOT NULL DEFAULT '0',
  `mobile` varchar(13) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `taken` (`token`,`bind_type`) USING BTREE
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='绑定用户表，联合登录使用';

-- ----------------------------
-- Records of wx_user_bind
-- ----------------------------
