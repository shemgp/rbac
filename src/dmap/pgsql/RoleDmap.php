<?php
namespace PhpRbac\dmap\pgsql;

class RoleDmap extends BaseDmap {

    public function __construct($cfg, $tblName = 'roles')
    {
        parent::__construct($cfg, $tblName);
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
                 WHERE roleid = ?";

        $params = array($roleId);

        return $this->_execQuery($qry, $params);
    }


    /**
     * See if a Role has the requested permission.
     *
     * Since each is hierachichal, we need to match (roleId + any of its
     * children) with (permId + any of its parents).
     *
     * example:
     * If:
     *   The role of 'chef' has a child role of 'line cook'.
     *   The permission 'can use kitchen' has a child permission 'can use grill'.
     *   We have stored that a 'line cook' 'can use kitchen'.
     * Then:
     *   When asked whether the role of 'chef' has permission 'can use grill',
     *   we should get a value of 'true'.
     **/
    public function hasPermission($roleId, $permId)
    {
        $qry = "WITH RECURSIVE
        roles AS
        (
            SELECT  id
              FROM  {$this->pfx}roles
             WHERE  id = ?
          UNION ALL
            SELECT  child.id
              FROM  roles
              JOIN  {$this->pfx}roles child ON child.parent = roles.id
        ),
        perms (id) AS
        (
            SELECT id, parent
              FROM {$this->pfx}permissions
             WHERE id = ?
         UNION ALL
            SELECT p.id, p.parent
              FROM perms
              JOIN {$this->pfx}permissions p ON p.id = perms.parent
        ),
        role_perms AS
        (
            SELECT roles.id AS role_id, perms.id AS perm_id
              FROM roles, perms
       )
        SELECT COUNT(rp.*) AS num_found
          FROM {$this->pfx}rolepermissions rp
          JOIN role_perms
               ON (rp.roleid = role_perms.role_id
                 AND rp.permissionid = role_perms.perm_id)";


        $params = array($roleId, $permId);
        $res = $this->_fetchOne($qry, $params);

        return $res !== null && $res >= 1;
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
            $qry = "SELECT perms.id, perms.title, perms.description
                      FROM {$this->pfx}permissions AS perms
                 LEFT JOIN {$this->pfx}rolepermissions AS rp ON
                           (rp.permissionid = perms.id)
                     WHERE roleid = ?
                  ORDER BY perms.id";

            return $this->_fetchAll($qry, $params);
        }
    }


    /**
     * Use multiple queries to do same thing as hasPermissions()
     * 
     * Only kept around for reference purproses.
     **/
    protected function hasPermissionMultiQuery($roleId, $permId)
    {
        $roles = $this->descendants($roleId);
        $perms = $this->ancestors($permId);

        if (empty($roles) || empty($perms))
            return false;

        $roleIds = array_column('id', $roles);
        $permIds = array_column('id', $perms);

        $rolePlaceholders = implode(', ', array_fill(0, count($roleIds), '?'));
        $permPlaceholders = implode(', ', array_fill(0, count($permIds), '?'));

        $qry = "SELECT COUNT(*) AS num_found
                  FROM {$this->pfx}rolepermissions AS rp
                 WHERE rp.roleid IN ($rolePlaceholders)
                   AND rp.permissionid IN ($permPlaceholders)
                 LIMIT 1";

        $params = array_merge($roleIds, $permIds);

        $numFound = $this->_fetchOne($qry, $params);

        return $numFound > 0;
    }

}
