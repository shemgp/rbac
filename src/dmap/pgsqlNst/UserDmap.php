<?php
namespace PhpRbac\dmap\pgsqlNst;

class UserDmap extends \PhpRbac\utils\PdoWrapper {

    protected $pfx;

    public function __construct($cfg, $tblName = 'userroles')
    {
        $this->pfx = $cfg['pfx'];
        parent::__construct($cfg);

        $this->tblName = $this->pfx . $tblName;
    }

    public function hasRole($roleId, $userId)
    {
        $qry = "SELECT tur.userid
                  FROM {$this->pfx}userroles AS tur
                  JOIN {$this->pfx}roles trdirect ON
                       (trdirect.id = tur.roleid)
                  JOIN {$this->pfx}roles tr ON
                       (tr.lft BETWEEN trdirect.lft AND trdirect.rgt)
                 WHERE tur.userid = ?
                   AND tr.id = ?";
        $params = array($userId, $roleId);

        $roleId = $this->_fetchOne($qry, $params);

        return $roleId !== null;
    }

    public function assign($userId, $roleId)
    {
        $qry = "INSERT INTO {$this->pfx}userroles
                (userid, roleid, assignmentdate)
                VALUES (?, ?, {$this->dbNow()})";

        $params = array($userId, $roleId);
        $res = $this->_execQuery($qry, $params);

        return $res['success'];
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
        //$qry = "TRUNCATE TABLE {$this->pfx}userroles";
        $qry = "DELETE FROM {$this->pfx}userroles";
        $res = $this->_execQuery($qry);

        return $res['output'];
    }

    /**
     * Check of a user id has a given permission id.
     *
     * Checks: a) all assigned roles of the user,
     * b) all descendants of those assigned roles,
     * c) all permissions assigned to a) or b)
     * and then return true if the permission id is listed in c)
     **/
    public function check($userId, $permId)
    {
        $qry = "
            WITH assigned_roles AS
             (
             SELECT roleid
               FROM phprbac_userroles
              WHERE userid = ?
            ),

            roles AS
             (
             SELECT DISTINCT(roles.id)
             FROM phprbac_roles roles,
               (
                  SELECT r.*
                  FROM phprbac_roles r, assigned_roles
                  WHERE r.id IN (assigned_roles.roleid)
               ) AS r
             WHERE roles.lft >= r.lft AND roles.rgt <= r.rgt
             ),

            assigned_perms AS
              (
              SELECT permissionid
              FROM phprbac_rolepermissions rp, roles
              WHERE roleid IN (roles.id)
              )

            SELECT permissionid
            FROM assigned_perms
            WHERE permissionid = ?";


        $params = array($userId, $permId);

        $res = $this->_fetchOne($qry, $params);

        return $res !== null;
    }

    protected function dbNow()
    {
        return "EXTRACT(EPOCH FROM now())::INT";
    }
}
