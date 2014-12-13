<?php
namespace \PhpRbac\dmap\mysql;

class RoleDmap extends BaseDmap {

    public function __construct($settings)
    {
        $this->pfx = $settings['tbl_prefix'];
    }

    public function unassignPermissionsFromRole($roleId)
    {
        $qry = "DELETE FROM {$this->pfx}rolepermissions
                 WHERE roleid = ?";
        $params = array($roleId);

        return $this->_execQuery($qry, $params);
    }

    public function unassignUsersFromRole($roleId)
    {
        $qry = "DELETE FROM {$this->pfx}userroles
i                WHERE roleid = ?";

        $params = array($roleId);

        return $this->_execQuery($qry, $params);
    }

    public function hasPermission($roleId, $permId)
    {
        /**
         * First part of main WHERE clause ('BETWEEN') is for any row that is
         * a descendant of our role: if descendant roles have some permission,
         * then our role has it too
         *
         * Second part returns all the parents of (the path to) our
         * permission, so if one of our role or its descendants has an
         * assignment to any of them, we're good.
         * */
        $qry = "SELECT COUNT(*) AS Result
                  FROM {$this->pfx}rolepermissions AS trel
                  JOIN {$this->pfx}permissions AS tp ON
                       (tp.id = trel.permissionid)
                  JOIN {$this->pfx}roles AS tr ON
                       (tr.id = trel.roleid)
                 WHERE tr.lft BETWEEN
                           (SELECT lft
                              FROM {$this->pfx}roles
                              WHERE id = ?) AND
                           (SELECT rght
                              FROM {$this->pfx}roles
                             WHERE id = ?)

                    AND tp.id IN (
                        SELECT parent.id
                          FROM {$this->pfx}permissions AS node,
                               {$this->pfx}permissions AS parent
                         WHERE node.lft BETWEEN parent.lft AND parent.rght
                           AND (node.id = ?)
                      ORDER BY parent.lft
                    )";

        $params = array($roleId, $roleId, $permId);

        return $this->_fetchOne($qry, $params);
    }

    public function permissionsForRole($roleId, $onlyIds = true)
    {
        $params = array($roleId);

        if ($onlyIds) {
            $qry = "SELECT permissionid AS id
                      FROM {$this->pfx}rolepermissions
                     WHERE roleid = ?
                  ORDER BY permissionid";

            return $this->_fetchCol($qry, $params);
        }
        else {
            $qry = "SELECT tp.id, tp.title, tp.description
                      FROM {$this->pfx}permissions AS tp
                 LEFT JOIN {$this->pfx}rolepermissions AS tr ON
                           (tr.permissionid = tp.id)
                     WHERE roleid = ?
                  ORDER BY tp.id";

            return $this->_fetchAll($qry, $params);
        }
    }

}
