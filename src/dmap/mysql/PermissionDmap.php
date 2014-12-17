<?php
namespace PhpRbac\dmap\mysql;

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
     * @param integer   PK id of the node to delete.
     **/
    public function moveChildrenUp($permId)
    {
        $this->nst->deleteConditional('id = ?', $permId);
    }

    /**
     * Delete a node and all of its descendants.
     *
     * @param integer   PK id of the node to delete.
     **/
    public function removeChildren($permId)
    {
        $this->nst->deleteSubtreeConditional('id = ?', $permId);
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
        $params = array($permid);

        if ($onlyIds) {
            $qry = "SELECT roleid AS id
                FROM {$this->pfx}rolepermissions
                WHERE permissionid = ?
                ORDER BY roleid";

            return $this->_fetchCol($qry, $params);
        }
        else {
            $qry = "SELECT tp.id, tp.title, tp.description
                      FROM {$this->pfx}roles AS tp
                 LEFT JOIN {$this->pfx}rolepermissions AS tr ON
                           (tr.roleid = tp.id)
                     WHERE permissionid = ?
                  ORDER BY tp.id";

            return $this->_fetchAll($qry, $params);
        }
    }

}
