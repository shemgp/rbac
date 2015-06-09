/*
 * Create Tables for PostgreSQL
 *
 * Default prefix is 'phprbac_'. To change the prefix, you must do a 
 * search & replace before running this script.
 *
 * To change the default schema (depends on your account, but usually things
 * will default to the 'public' schema), you'll have to search & replace
 * 'phprbac_' to be something like 'myschema.myprefix_' and add the line:
 *
 * CREATE SCHEMA myschema;
 */

CREATE TABLE phprbac_permissions (
  id SERIAL,
  lft INTEGER NOT NULL,
  rgt INTEGER NOT NULL CHECK (rgt > Lft),
  title TEXT NOT NULL,
  description text NULL DEFAULT NULL,
  PRIMARY KEY (id)
);
CREATE INDEX perms_title_ndx ON phprbac_permissions (title);
CREATE INDEX perms_lft_ndx ON phprbac_permissions  (lft);
CREATE INDEX perms_rgt_ndx ON phprbac_permissions  (rgt);

CREATE TABLE IF NOT EXISTS phprbac_roles (
  id SERIAL,
  lft INTEGER NOT NULL,
  rgt INTEGER NOT NULL CHECK (rgt > lft),
  title TEXT NOT NULL,
  description TEXT NULL DEFAULT NULL,
  PRIMARY KEY (id)
);
CREATE INDEX roles_title_ndx ON phprbac_roles (title);
CREATE INDEX roles_lft_ndx ON phprbac_roles (lft);
CREATE INDEX roles_rft_ndx ON phprbac_roles (rgt);

CREATE TABLE IF NOT EXISTS phprbac_rolepermissions (
  roleid INTEGER NOT NULL,
  permissionid INTEGER NOT NULL,
  assignmentdate INTEGER NOT NULL,
  primarY KEY (roleid, permissionid)
);

CREATE TABLE IF NOT EXISTS phprbac_userroles (
  userid INTEGER NOT NULL,
  roleid INTEGER NOT NULL,
  assignmentdate INTEGER NOT NULL,
  PRIMARY KEY (userid, roleid)
);

/*
 * Insert Initial Table Data
 */

INSERT INTO phprbac_permissions (id, lft, rgt, title, description)
VALUES (DEFAULT, 0, 1, 'root', 'root');

INSERT INTO phprbac_roles (id, lft, rgt, title, description)
VALUES (DEFAULT, 0, 1, 'root', 'root');

INSERT INTO phprbac_rolepermissions (roleid, permissionid, assignmentdate)
VALUES (1, 1, extract(epoch from now()));

INSERT INTO phprbac_userroles (userid, roleid, assignmentdate)
VALUES (1, 1, extract(epoch from now()));
