<?php
namespace PhpRbac\dmap\pgsql;

class UserDmap extends \PhpRbac\utils\PdoWrapper {

    protected $pfx;

    public function __construct($cfg, $tblName = 'userroles')
    {
        $this->pfx = $cfg['pfx'];
        parent::__construct($cfg);

        $this->tblName = $this->pfx . $tblName;
    }

    /**
     * See if a User has a particular Role.
     *
     * Roles are inherited, so if the User is assigned to any role that is also
     * a parent of $roleId, the answer is yes.
     *
     * @param integer   Id of the Role
     * @param integer   Id of the User
     * @return boolean  Whether the User has the role, or a parent of the role.
     **/
    public function hasRole($roleId, $userId)
    {
        // the 'ancestors()' query from BaseDmap, with minor modification
        $qry = "WITH RECURSIVE
        roles AS
        (
            SELECT  id, parent, title, description, 0 AS depth
              FROM  {$this->pfx}roles
             WHERE  id = ?
        UNION ALL
            SELECT  par.id, par.parent, par.title, par.description, depth + 1
              FROM  roles
              JOIN  {$this->pfx}roles par ON par.id = roles.parent
        )
        SELECT  r.*
          FROM  roles r
          JOIN {$this->tblName} ur ON (ur.roleid = r.id)
         WHERE ur.userid = ?";
        $params = array($roleId, $userId);

        $res = $this->_fetchRow($qry, $params);

        return !empty($res);
    }

    public function assign($userId, $roleId)
    {
        $qry = "INSERT INTO {$this->pfx}userroles
                       (userid, roleid, assignmentdate)
                VALUES (?, ?, {$this->dbNow()})";

        $params = array($userId, $roleId);
        $res = $this->_execQuery($qry, $params);

        return $res['output'];
    }

    public function unassign($userId, $roleId)
    {
        $qry = "DELETE FROM {$this->pfx}userroles
                 WHERE userid = ?
                   AND roleid = ?";

        $params = array($userId, $roleId);
        $res = $this->_execQuery($qry, $params);

        return $res['success'];
    }

    /**
     * List all roles directly assigned to a User.
     *
     * Does *not* include the full list of all child roles.
     *
     * @param integer   User id to list roles of.
     **/
    public function allRoles($userId)
    {
        $qry = "SELECT tr.*
                  FROM {$this->pfx}userroles AS ur
                  JOIN {$this->pfx}roles AS tr ON
                       (ur.roleid = tr.id)
                 WHERE ur.userid = ?";

        $params = array($userId);

        $rows = $this->_fetchAll($qry, $params);

        if (empty($rows))
            return null;
        else
            return $rows;
    }

    public function roleCount($userId)
    {
        $qry = "SELECT COUNT(userid) AS result
                  FROM {$this->pfx}userroles
                 WHERE userid = ?";

        $params = array($userId);

        return (int) $this->_fetchOne($qry, $params);
    }

    public function resetAssignments()
    {
        $qry = "TRUNCATE TABLE {$this->pfx}userroles";
        $res = $this->_execQuery($qry);

        return $res['output'];
    }

    /**
     * See if a User has the requested Permission.
     **/
    public function check($userId, $permId)
    {
        $qry = "WITH RECURSIVE
        roles AS
        (
            SELECT  id
              FROM  {$this->pfx}roles
              WHERE  id IN (SELECT roleid
                              FROM {$this->pfx}userroles ur
                             WHERE ur.userid = ?)
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


        $params = array($userId, $permId);
        $res = $this->_fetchOne($qry, $params);

        return $res !== null && $res >= 1;
    }

    protected function dbNow()
    {
        return "EXTRACT(EPOCH FROM now())::INT";
    }
}
