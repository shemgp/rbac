/*
 * Create Tables for PostgreSQL
 *
 * Default prefix is 'phprbac_'. To change the prefix, you must do a 
 * search & replace before running this script.
 *
 * To change the default schema, (depends on your account, but usually things
 * will default to the 'public' schema, you'll have to search & replace
 * 'phprbac_' to be something like 'myschema.myprefix_' and add the line:
 *
 * CREATE SCHEMA myschema;
 */

CREATE TABLE phprbac_permissions (
  id SERIAL,
  parent INTEGER,
  title TEXT NOT NULL,
  description text DEFAULT NULL,
  PRIMARY KEY (id)
);
CREATE INDEX perms_title_ndx ON phprbac_permissions (title);
CREATE INDEX perms_parent_ndx ON phprbac_permissions  (parent);

CREATE TABLE IF NOT EXISTS phprbac_roles (
  id SERIAL,
  parent INTEGER,
  title TEXT NOT NULL,
  description TEXT DEFAULT NULL,
  PRIMARY KEY (ID)
);
CREATE INDEX roles_title_ndx ON phprbac_roles (title);
CREATE INDEX roles_parent_ndx ON phprbac_roles (parent);

CREATE TABLE IF NOT EXISTS phprbac_rolepermissions (
  roleid INTEGER NOT NULL,
  permissionid INTEGER NOT NULL,
  assignmentdate INTEGER NOT NULL,
  PRIMARY KEY (roleid, permissionid)
);

CREATE TABLE IF NOT EXISTS phprbac_userroles (
  userid INTEGER NOT NULL,
  roleid INTEGER NOT NULL,
  assignmentdate INTEGER NOT NULL,
  PRIMARY KEY (UserID, RoleID)
);

/*
 * Insert Initial Table Data
 */
INSERT INTO phprbac_permissions (id, parent, title, description)
VALUES (DEFAULT, null, 'root', 'root');

INSERT INTO phprbac_roles (id, parent, title, description)
VALUES (DEFAULT, null, 'root', 'root');

INSERT INTO phprbac_rolepermissions (roleid, permissionid, assignmentdate)
VALUES (1, 1, extract(epoch from now()));

INSERT INTO phprbac_userroles (userid, roleid, assignmentdate)
VALUES (1, 1, extract(epoch from now()));
