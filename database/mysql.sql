/*
 * Create Tables
 */

CREATE TABLE IF NOT EXISTS `PREFIX_permissions` (
  `id` int(11) NOT NULL auto_increment,
  `lft` int(11) NOT NULL,
  `rgt` int(11) NOT NULL,
  `title` char(64) NOT NULL,
  `description` text NOT NULL,
  PRIMARY KEY  (`id`),
  KEY `title` (`title`),
  KEY `lft` (`lft`),
  KEY `rgt` (`rgt`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin AUTO_INCREMENT=1;

CREATE TABLE IF NOT EXISTS `PREFIX_rolepermissions` (
  `roleid` int(11) NOT NULL,
  `permissionid` int(11) NOT NULL,
  `assignmentdate` int(11) NOT NULL,
  PRIMARY KEY  (`roleid`, `permissionid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

CREATE TABLE IF NOT EXISTS `PREFIX_roles` (
  `id` int(11) NOT NULL auto_increment,
  `lft` int(11) NOT NULL,
  `rgt` int(11) NOT NULL,
  `title` varchar(128) NOT NULL,
  `description` text NOT NULL,
  PRIMARY KEY  (`id`),
  KEY `title` (`title`),
  KEY `lft` (`lft`),
  KEY `rgt` (`rgt`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

CREATE TABLE IF NOT EXISTS `PREFIX_userroles` (
  `userid` int(11) NOT NULL,
  `roleid` int(11) NOT NULL,
  `assignmentdate` int(11) NOT NULL,
  PRIMARY KEY (`userid`, `roleid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

/*
 * Insert Initial Table Data
 */

INSERT INTO `PREFIX_permissions` (`id`, `lft`, `rgt`, `title`, `description`)
VALUES (1, 0, 1, 'root', 'root');

INSERT INTO `PREFIX_rolepermissions` (`roleid`, `permissionid`, `assignmentdate`)
VALUES (1, 1, UNIX_TIMESTAMP());

INSERT INTO `PREFIX_roles` (`id`, `lft`, `rgt`, `title`, `description`)
VALUES (1, 0, 1, 'root', 'root');

INSERT INTO `PREFIX_userroles` (`userid`, `roleid`, `assignmentdate`)
VALUES (1, 1, UNIX_TIMESTAMP());
