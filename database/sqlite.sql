/*
 * Create Tables
 */

CREATE TABLE `PREFIX_permissions` (
  `id` INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT,
  `lft` INTEGER NOT NULL,
  `rgt` INTEGER NOT NULL,
  `title` char(64) NOT NULL,
  `description` text
);

CREATE UNIQUE INDEX perms_nst_ndx
   ON PREFIX_permissions (lft, rgt);
CREATE INDEX perms_title_ndx
   ON PREFIX_permissions (title);

CREATE TABLE `PREFIX_rolepermissions` (
  `roleid` INTEGER NOT NULL,
  `permissionid` INTEGER NOT NULL,
  `assignmentdate` INTEGER NOT NULL,
  PRIMARY KEY (`roleid`, `permissionid`)
);

CREATE TABLE `PREFIX_roles` (
  `id` INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT,
  `lft` INTEGER NOT NULL,
  `rgt` INTEGER NOT NULL,
  `title` varchar(128) NOT NULL,
  `description` text
);

CREATE UNIQUE INDEX role_nst_ndx ON PREFIX_roles (lft, rgt);
CREATE INDEX role_title_ndx ON PREFIX_roles (title);

CREATE TABLE `PREFIX_userroles` (
  `userid` INTEGER NOT NULL,
  `roleid` INTEGER NOT NULL,
  `assignmentdate` INTEGER NOT NULL,
  PRIMARY KEY (`userid`, `roleid`)
);

/*
 * Insert Initial Table Data
 */
INSERT INTO `PREFIX_permissions` (`id`, `lft`, `rgt`, `title`, `description`)
VALUES (1, 0, 1, 'root', 'root');

INSERT INTO `PREFIX_rolepermissions` (`roleid`, `permissionid`, `assignmentdate`)
VALUES (1, 1, strftime('%s', 'now'));

INSERT INTO `PREFIX_roles` (`id`, `lft`, `rgt`, `title`, `description`)
VALUES (1, 0, 1, 'root', 'root');

INSERT INTO `PREFIX_userroles` (`userid`, `roleid`, `assignmentdate`)
VALUES (1, 1, strftime('%s', 'now'));
