/*
 * Create Tables for PostgreSQL
 *
 * Default prefix is 'rbac_'. To change the prefix, you must do a 
 * search & replace before running this script.
 *
 * To change the default schema, (depends on your account, but usually things
 * will default to the 'public' schema, you'll have to search & replace
 * 'rbac_' to be something like 'myschema.myprefix_' and add the line:
 *
 * CREATE SCHEMA myschema;
 */

CREATE TABLE rbac_permissions (
  id SERIAL,
  lft INTEGER NOT NULL,
  rght INTEGER NOT NULL CHECK (Rght > Lft),
  title CHARACTER VARYING(64) NOT NULL,
  description text NOT NULL,
  PRIMARY KEY (id)
);
CREATE INDEX perms_title_ndx ON rbac_permissions (title);
CREATE INDEX perms_lft_ndx ON rbac_permissions  (lft);
CREATE INDEX perms_rgt_ndx ON rbac_permissions  (rght);

CREATE TABLE IF NOT EXISTS rbac_roles (
  id SERIAL,
  lft INTEGER NOT NULL,
  rght INTEGER NOT NULL CHECK (rght > lft),
  title TEXT NOT NULL,
  description TEXT NOT NULL,
  PRIMARY KEY (ID)
);
CREATE INDEX roles_title_ndx ON rbac_roles (title);
CREATE INDEX roles_lft_ndx ON rbac_roles (lft);
CREATE INDEX roles_rft_ndx ON rbac_roles (rght);

CREATE TABLE IF NOT EXISTS rbac_rolepermissions (
  roleid INTEGER NOT NULL REFERENCES rbac_roles(id),
  permissionid INTEGER NOT NULL REFERENCES rbac_permissions(id),
  assignmentdate TIMESTAMP NOT NULL,
  primarY KEY (roleid, permissionid)
);

CREATE TABLE IF NOT EXISTS rbac_userroles (
  userid INTEGER NOT NULL,
  roleid INTEGER NOT NULL REFERENCES rbac_roles(id),
  assignmentdate TIMESTAMP NOT NULL,
  PRIMARY KEY (UserID, RoleID)
);

/*
 * Insert Initial Table Data
 */

INSERT INTO rbac_permissions (id, lft, rght, title, description)
VALUES (DEFAULT, 0, 1, 'root', 'root');

INSERT INTO rbac_roles (id, lft, rght, title, description)
VALUES (DEFAULT, 0, 1, 'root', 'root');

INSERT INTO rbac_rolepermissions (roleid, permissionid, assignmentdate)
VALUES (1, 1, 'NOW');

INSERT INTO rbac_userroles (userid, roleid, assignmentdate)
VALUES (1, 1, 'NOW');
