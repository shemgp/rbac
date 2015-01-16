<?php
namespace PhpRbac\dmap\pgsql;

class PermissionDmap extends BaseDmap {

    public function __construct($cfg, $tblName = 'permissions')
    {
        parent::__construct($cfg, $tblName);
    }

    public function unassignRoles($permId)
    {
        $qry = "DELETE FROM {$this->pfx}rolepermissions
                 WHERE permissionid = ?";
        $params = array($permId);

        $res = $this->_execQuery($qry, $params);

        return $res;
    }

    /**
     * Delete a node and shift its children up a level.
     *
     * Return false is nothing to do.
     *
     * @param integer   PK id of the node to delete.
     **/
    public function moveChildrenUp($permId)
    {
        $qry = "SELECT *
                  FROM {$this->tblName}
                 WHERE id = ?";
        $params = array($permId);

        $toDel = $this->_fetchRow($qry, $params);

        if (empty($toDel))
            return false;

        $updQry = "UPDATE {$this->tblName}
                     SET parent = ?
                   WHERE parent = ?";
        $updParams = array($toDel['parent'], $toDel['id']);

        $this->_execQuery($updQry, $updParams);

        $delQry = "DELETE FROM {$this->tblName}
                    WHERE id = ?";
        $delParams = array($permId);

        $delRes = $this->_execQuery($delQry, $delParams);

        return $delRes['success'];
    }

    /**
     * Delete a node and all of its descendants.
     *
     * Return false is nothing to do.
     *
     * @param integer   PK id of the node to delete.
     **/
    public function removeChildren($permId)
    {
        $descendants = $this->descendants($permId);

        $descIds = array_column($descenants, 'id');

        $placeholders = implode(', ', array_fill(0, count($descIds), '?'));

        $qry = "DELETE FROM {$this->tblName}
                 WHERE id IN ($placeholders)";

        $this->_execQuery($qry, $descIds);
    }

    /**
     * Return all Roles assigned to a Permission.
     *
     * Will return 'null' if no Roles are found for the given Permission id.
     *
     * @param integer   PK id of the Permission to retrieve Roles for.
     * @param boolean   Whether to return _only_ the Role ids.
     **/
    public function rolesForPermission($permId, $onlyIds = true)
    {
        $params = array($permId);

        if ($onlyIds) {
            $qry = "SELECT roleid AS id
                      FROM {$this->pfx}rolepermissions
                     WHERE permissionid = ?
                  ORDER BY roleid";

            return $this->_fetchCol($qry, $params);
        }
        else {
            $qry = "SELECT roles.id, roles.title, roles.description
                      FROM {$this->pfx}roles AS roles
                 LEFT JOIN {$this->pfx}rolepermissions AS rp ON
                           (rp.roleid = roles.id)
                     WHERE permissionid = ?
                  ORDER BY roles.id";

            return $this->_fetchAll($qry, $params);
        }
    }

}
