/*
Navicat MySQL Data Transfer

Source Server         : localhost
Source Server Version : 50553
Source Host           : localhost:3306
Source Database       : mb

Target Server Type    : MYSQL
Target Server Version : 50553
File Encoding         : 65001

Date: 2018-01-12 15:35:16
*/

SET FOREIGN_KEY_CHECKS=0;

-- ----------------------------
-- Table structure for `py_admin`
-- ----------------------------
DROP TABLE IF EXISTS `py_admin`;
CREATE TABLE `py_admin` (
  `user_id` int(11) NOT NULL AUTO_INCREMENT COMMENT '管理员ID',
  `user_name` varchar(50) NOT NULL COMMENT '登录名',
  `real_name` varchar(200) NOT NULL COMMENT '管理员真实名称',
  `password` char(32) NOT NULL COMMENT '登录密码',
  `sex` tinyint(1) NOT NULL DEFAULT '1' COMMENT '性别',
  `session_key` char(32) NOT NULL COMMENT '登录密码串',
  `locked` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否锁定',
  `group_id` int(11) NOT NULL COMMENT '组织架构',
  `role_id` int(11) NOT NULL COMMENT '角色',
  `menu_id` text NOT NULL COMMENT '功能ID集合，逗号隔开',
  `is_open` tinyint(1) DEFAULT '0' COMMENT '开放权限',
  `create_time` int(11) DEFAULT NULL COMMENT '插入时间',
  `update_time` int(11) DEFAULT NULL COMMENT '最后更新时间',
  `last_login_time` int(11) DEFAULT NULL COMMENT '上次登录时间',
  `now_login_time` int(11) DEFAULT NULL COMMENT '本次登录时间',
  `last_login_ip` varchar(50) DEFAULT NULL COMMENT '上次登录密码',
  `now_login_ip` varchar(50) DEFAULT NULL COMMENT '本次登录IP',
  PRIMARY KEY (`user_id`),
  KEY `index_user_name` (`user_name`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8 COMMENT='管理员表';

-- ----------------------------
-- Records of py_admin
-- ----------------------------
INSERT INTO `py_admin` VALUES ('1', '800001', '超级管理员', '758894b3e908de2074e26216e973e677', '1', '1mr2rerrinve3pcbk59funhji5', '0', '1', '1', '', '0', '1435324617', '1515724530', '1512378183', '1512378224', '127.0.0.1', '127.0.0.1');

-- ----------------------------
-- Table structure for `py_admin_log`
-- ----------------------------
DROP TABLE IF EXISTS `py_admin_log`;
CREATE TABLE `py_admin_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL DEFAULT '0',
  `module_name` varchar(255) NOT NULL,
  `controller_name` varchar(255) NOT NULL,
  `action_name` varchar(255) NOT NULL,
  `note` varchar(255) NOT NULL,
  `params` text COMMENT '参数',
  `create_time` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `index_note` (`note`)
) ENGINE=InnoDB AUTO_INCREMENT=122 DEFAULT CHARSET=utf8 COMMENT='登录日志表';

-- ----------------------------
-- Records of py_admin_log
-- ----------------------------

-- ----------------------------
-- Table structure for `py_config`
-- ----------------------------
DROP TABLE IF EXISTS `py_config`;
CREATE TABLE `py_config` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '配置ID',
  `name` varchar(30) NOT NULL DEFAULT '' COMMENT '配置名称',
  `type` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '配置类型',
  `title` varchar(50) NOT NULL DEFAULT '' COMMENT '配置说明',
  `group` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '配置分组',
  `extra` varchar(255) NOT NULL DEFAULT '' COMMENT '配置项',
  `remark` varchar(100) NOT NULL COMMENT '配置说明',
  `create_time` int(11) DEFAULT NULL COMMENT '创建时间',
  `update_time` int(11) DEFAULT NULL COMMENT '更新时间',
  `value` text NOT NULL COMMENT '配置值',
  `orderby` smallint(3) unsigned NOT NULL DEFAULT '0' COMMENT '排序',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_name` (`name`),
  KEY `type` (`type`),
  KEY `group` (`group`)
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8 COMMENT='系统配置表';

-- ----------------------------
-- Records of py_config
-- ----------------------------
INSERT INTO `py_config` VALUES ('1', 'SITE_TITLE', '1', '网站标题', '1', '', '网站标题前台显示标题', '1435324617', '1490864693', '凤鸣集团网站后台管理平台', '50');
INSERT INTO `py_config` VALUES ('2', 'SITE_DESCRIPTION', '1', '网站描述', '1', '', '网站搜索引擎描述', '1435324617', '1490601795', '凤鸣集团网站后台管理平台', '50');
INSERT INTO `py_config` VALUES ('3', 'SITE_KEYWORD', '1', '网站关键字', '1', '', '网站搜索引擎关键字', '1435324617', '1438265293', '凤鸣集团网站后台管理平台', '50');
INSERT INTO `py_config` VALUES ('4', 'SITE_CLOSE', '5', '开启站点', '1', '0:关闭,1:开启', '站点关闭后其他用户不能访问，管理员可以正常访问', '1435324617', '1439647872', '0', '50');
INSERT INTO `py_config` VALUES ('5', 'OPEN_REGISTER', '5', '会员注册', '3', '0:关闭,1:开启', '关闭后所有访客都不可进行注册', '1435324617', '1493790596', '1', '50');
INSERT INTO `py_config` VALUES ('11', 'CONFIG_GROUP_LIST', '4', '配置分组', '0', '', '', '1435324617', '1490781408', '1:基本,2:内容,3:用户,4:系统', '50');
INSERT INTO `py_config` VALUES ('12', 'CONFIG_TYPE_LIST', '4', '配置类型', '0', '', '', '1435324617', null, '1:数字,2:字符,3:文本,4:数组,5:枚举', '50');
INSERT INTO `py_config` VALUES ('13', 'VERIFY_CLOSE', '5', '开启验证码', '1', '0:关闭,1:开启', '验证码关闭后无需使用验证码', '1461200474', '1515666899', '0', '50');

-- ----------------------------
-- Table structure for `py_group`
-- ----------------------------
DROP TABLE IF EXISTS `py_group`;
CREATE TABLE `py_group` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `text` varchar(255) NOT NULL COMMENT '组别名称',
  `pid` int(11) NOT NULL DEFAULT '0' COMMENT '上级组别',
  `remark` varchar(255) NOT NULL COMMENT '描述',
  `locked` tinyint(1) NOT NULL DEFAULT '0' COMMENT '禁用',
  `orderby` smallint(5) NOT NULL DEFAULT '0' COMMENT '排序',
  `create_time` int(11) DEFAULT NULL COMMENT '创建时间',
  `update_time` int(11) DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`),
  KEY `index_locked` (`locked`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8 COMMENT='角色表';

-- ----------------------------
-- Records of py_group
-- ----------------------------
INSERT INTO `py_group` VALUES ('1', '超级管理员', '0', '', '0', '50', '1435324617', '1435324617');
INSERT INTO `py_group` VALUES ('3', '管理员', '0', '', '0', '50', '1503539517', '1515659477');

-- ----------------------------
-- Table structure for `py_menu`
-- ----------------------------
DROP TABLE IF EXISTS `py_menu`;
CREATE TABLE `py_menu` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `text` varchar(255) DEFAULT NULL COMMENT '菜单名',
  `pid` int(11) NOT NULL DEFAULT '0' COMMENT '父级ID',
  `module` varchar(50) DEFAULT NULL COMMENT '模块名',
  `controller` varchar(50) DEFAULT NULL COMMENT '控制器名称',
  `method` varchar(50) DEFAULT NULL COMMENT '方法名称',
  `other_method` varchar(255) DEFAULT NULL COMMENT '关联权限方法',
  `display` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否显示到菜单',
  `icon` varchar(50) DEFAULT NULL COMMENT '图标',
  `orderby` smallint(5) NOT NULL DEFAULT '0' COMMENT '排序',
  `create_time` int(10) unsigned NOT NULL DEFAULT '0',
  `update_time` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8 COMMENT='模块表';

-- ----------------------------
-- Records of py_menu
-- ----------------------------
INSERT INTO `py_menu` VALUES ('1', '权限', '0', 'admin', 'master', '*', '', '1', 'fa-power-off', '60', '1435324617', '1487318419');
INSERT INTO `py_menu` VALUES ('2', '系统', '0', 'admin', 'setting', '*', '', '1', 'fa-dashboard', '100', '1435324617', '1479806145');
INSERT INTO `py_menu` VALUES ('3', '系统设置', '2', 'admin', 'config', 'setting', '', '1', 'fa-plane', '7', '1435324617', '1448952211');
INSERT INTO `py_menu` VALUES ('5', '管理人员', '1', 'admin', 'admin', '*', '', '1', 'fa-user-md', '50', '1435324617', '1448677843');
INSERT INTO `py_menu` VALUES ('6', '角色管理', '1', 'admin', 'role', '*', '', '1', 'fa-user-secret', '50', '1435324617', '1447831484');
INSERT INTO `py_menu` VALUES ('7', '组织架构', '1', 'admin', 'group', '*', 'save', '1', 'fa-sitemap', '50', '1435324617', '1448589479');
INSERT INTO `py_menu` VALUES ('8', '功能管理', '1', 'admin', 'menu', '*', '', '1', 'fa-cogs', '50', '1435324617', '1447831850');

-- ----------------------------
-- Table structure for `py_role`
-- ----------------------------
DROP TABLE IF EXISTS `py_role`;
CREATE TABLE `py_role` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '角色ID',
  `text` varchar(255) DEFAULT NULL COMMENT '角色名称',
  `pid` int(11) NOT NULL DEFAULT '0' COMMENT '父级ID',
  `remark` varchar(255) DEFAULT NULL COMMENT '描述',
  `locked` tinyint(1) NOT NULL DEFAULT '0' COMMENT '禁用',
  `orderby` smallint(5) NOT NULL DEFAULT '0' COMMENT '排序',
  `menu_id` text COMMENT '功能ID集合，逗号隔开',
  `create_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  `update_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
  PRIMARY KEY (`id`),
  KEY `index_locked` (`locked`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8 COMMENT='角色表';

-- ----------------------------
-- Records of py_role
-- ----------------------------
INSERT INTO `py_role` VALUES ('1', '超级管理员', '0', '', '0', '50', '1', '1435324617', '1490600242');
INSERT INTO `py_role` VALUES ('2', '管理员', '0', '', '0', '50', '84,86,92,87,88,93,94,89,95,96', '1503539475', '1515655050');
