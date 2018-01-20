INSERT INTO `py_menu` VALUES ('16', '微信管理', '0', 'admin', 'wechat', 'index', '', '1', 'fa-weixin', '50', '1435324617', '1487318419');
INSERT INTO `py_menu` VALUES ('', '帐号管理', '16', 'admin', 'wechatAccount', 'index', '', '1', 'fa-wechat', '50', '1435324617', '1479806145');
INSERT INTO `py_menu` VALUES ('', '微信用户', '16', 'admin', 'wechatUser', 'index', '', '1', 'fa-users', '50', '1435324617', '1479806145');
INSERT INTO `py_menu` VALUES ('', '微信菜单', '16', 'admin', 'wechatMenu', 'index', '', '1', 'fa-weixin', '50', '1435324617', '1479806145');


CREATE TABLE `py_wechat_menu` (
  `id` smallint(5) unsigned NOT NULL AUTO_INCREMENT,
  `account_id` smallint(5) unsigned NOT NULL DEFAULT '0' COMMENT '对应公众号ID',
  `text` varchar(100) NOT NULL COMMENT '菜单名称',
  `pid` smallint(5) unsigned NOT NULL DEFAULT '0' COMMENT '父级ID',
  `orderby` smallint(5) NOT NULL DEFAULT '50' COMMENT '排序',
  `locked` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否开启:1.启动0.禁用',
  `action` enum('click','url') DEFAULT 'url' COMMENT '动作',
  `action_param` varchar(255) DEFAULT NULL COMMENT '参数',
  `create_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '添加时间',
  `update_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '修改时间',
  `level` tinyint(3) NOT NULL DEFAULT '1' COMMENT '菜单级别',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COMMENT='微信菜单表';


CREATE TABLE `py_wechat_account` (
  `id` smallint(5) unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `text` varchar(120) NOT NULL COMMENT '名称',
  `token` varchar(110) NOT NULL COMMENT '令牌',
  `app_id` varchar(110) NOT NULL COMMENT '应用ID',
  `app_secret` varchar(110) NOT NULL COMMENT '应用的密钥',
  `machine_id` varchar(15) DEFAULT NULL COMMENT '商务号',
  `pay_key` varchar(40) DEFAULT NULL COMMENT '支付密钥',
  `crypted` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '加密方式：0明文模式，1兼容模式，2安全模式',
  `encoding_aes_key` varchar(110) NOT NULL COMMENT '加密密钥',
  `locked` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '锁定：0否，1是',
  `create_time` int(10) NOT NULL DEFAULT '0' COMMENT '创建时间',
  `update_time` int(10) NOT NULL DEFAULT '0' COMMENT '更新时间',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COMMENT='微信账户管理';


CREATE TABLE `py_wechat_user` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `wechat_account_id` smallint(5) unsigned NOT NULL DEFAULT '0' COMMENT '公众号ID',
  `openid` varchar(32) NOT NULL,
  `unionid` varchar(32) NOT NULL DEFAULT '',
  `nickname` varchar(100) NOT NULL DEFAULT '' COMMENT '用户的微信昵称',
  `sex` tinyint(1) unsigned NOT NULL DEFAULT '1' COMMENT '性别1：男，2：女',
  `language` char(10) NOT NULL DEFAULT '' COMMENT '语言',
  `city` varchar(50) NOT NULL DEFAULT '' COMMENT '城市',
  `province` varchar(50) NOT NULL DEFAULT '' COMMENT '省份',
  `country` varchar(50) NOT NULL DEFAULT '' COMMENT '国家',
  `headimgurl` varchar(500) NOT NULL DEFAULT '' COMMENT '头像链接',
  `remark` varchar(100) NOT NULL DEFAULT '' COMMENT '备注',
  `subscribe` tinyint(1) unsigned NOT NULL DEFAULT '1' COMMENT '关注状态，1为关注，0为取消关注',
  `groupid` int(11) NOT NULL DEFAULT '0' COMMENT '用户所在的分组ID',
  `tagid_list` varchar(255) NOT NULL DEFAULT '' COMMENT '用户被打上的标签ID列表',
  `create_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间(首次关注时间)',
  `update_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
  `subscribe_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '最近关注时间（可能是第二次以上关注）',
  `cancel_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '取消关注时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `openid` (`openid`,`wechat_account_id`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COMMENT='微信用户';
