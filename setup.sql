-- DROP TABLE IF EXISTS `ddns_token`;
CREATE TABLE `ddns_token` (
    `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
    `sys_userid` int(11) unsigned NOT NULL DEFAULT 0,
    `sys_groupid` int(11) unsigned NOT NULL DEFAULT 0,
    `sys_perm_user` varchar(5) NOT NULL DEFAULT '',
    `sys_perm_group` varchar(5) NOT NULL DEFAULT '',
    `sys_perm_other` varchar(5) NOT NULL DEFAULT '',
    `token` varchar(48) NOT NULL DEFAULT '',
    `allowed_zones` varchar(500) DEFAULT NULL,
    `allowed_record_types` varchar(255) NOT NULL DEFAULT '',
    `limit_records` varchar(255) DEFAULT NULL,
    `active` enum('N','Y') NOT NULL DEFAULT 'N',
    PRIMARY KEY (`id`),
    UNIQUE KEY `token` (`token`),
    KEY `active` (`active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
