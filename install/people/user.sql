
CREATE TABLE `py_user` (
  `user_id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `user_name` varchar(50) NOT NULL COMMENT '登录名',
  `real_name` varchar(200) NOT NULL COMMENT '真实名称',
  `password` char(32) NOT NULL COMMENT '登录密码',
  `session_key` char(32) NOT NULL COMMENT '登录密码串',
  `locked` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否锁定',
  `email` varchar(200) NOT NULL COMMENT '邮箱',
  `phone` varchar(200) NOT NULL COMMENT '手机',
  `birthday` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '出生日期',
  `sex` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '性别：0未设置，1男，2女',
  `create_time` int(11) DEFAULT NULL COMMENT '插入时间',
  `update_time` int(11) DEFAULT NULL COMMENT '最后更新时间',
  `last_login_time` int(11) DEFAULT NULL COMMENT '上次登录时间',
  `now_login_time` int(11) DEFAULT NULL COMMENT '本次登录时间',
  `last_login_ip` varchar(50) DEFAULT NULL COMMENT '上次登录密码',
  `now_login_ip` varchar(50) DEFAULT NULL COMMENT '本次登录IP',
  PRIMARY KEY (`user_id`),
  KEY `index_user_name` (`user_name`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8 COMMENT='用户表';

CREATE TABLE `py_user_address` (
  `address_id` mediumint(8) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '用户ID',
  `consignee` varchar(60) NOT NULL DEFAULT '' COMMENT '收货人',
  `province` smallint(5) unsigned NOT NULL DEFAULT '0' COMMENT '省',
  `city` smallint(5) unsigned NOT NULL DEFAULT '0' COMMENT '市',
  `district` smallint(5) unsigned NOT NULL DEFAULT '0' COMMENT '区',
  `town` smallint(5) unsigned NOT NULL COMMENT '街道/镇',
  `address` varchar(120) NOT NULL DEFAULT '' COMMENT '地址',
  `zipcode` varchar(60) NOT NULL COMMENT '邮编',
  `tel` varchar(60) NOT NULL DEFAULT '' COMMENT '固话',
  `mobile` varchar(60) NOT NULL DEFAULT '' COMMENT '手机',
  `email` varchar(60) NOT NULL COMMENT '邮箱',
  `create_by` mediumint(8) unsigned NOT NULL DEFAULT '0' COMMENT '创建者',
  `create_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  `update_by` mediumint(8) unsigned NOT NULL DEFAULT '0' COMMENT '更新者',
  `update_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
  `default` tinyint(1) NOT NULL DEFAULT '0' COMMENT '默认地址1:Y 0:N',
  PRIMARY KEY (`address_id`),
  KEY `user_id` (`user_id`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=59 DEFAULT CHARSET=utf8 COMMENT='用户地址表';
