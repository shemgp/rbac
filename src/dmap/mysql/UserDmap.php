<?php
namespace PhpRbac\dmap\mysql;

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
        $qry = "TRUNCATE TABLE {$this->pfx}userroles";
        $res = $this->_execQuery($qry);

        return $res['output'];
    }

    public function check($userId, $permId)
    {
        // this works on MySQL only
        $LastPart = "ON (tr.id = trel.roleid)
                WHERE turel.userid = ?
                  AND tpdirect.id = ?";

        $qry = "SELECT COUNT(*) AS result
                  FROM {$this->pfx}userroles AS turel
                  JOIN {$this->pfx}roles AS trdirect ON (trdirect.id = turel.roleid)
                  JOIN {$this->pfx}roles AS tr ON (tr.lft BETWEEN trdirect.lft AND trdirect.rgt)
                  JOIN
                  (        {$this->pfx}permissions AS tpdirect
                      JOIN {$this->pfx}permissions AS tp ON (tpdirect.lft BETWEEN tp.lft AND tp.rgt)
                      JOIN {$this->pfx}rolepermissions AS trel ON (tp.id = trel.permissionid)
                  ) $LastPart";

        $params = array($userId, $permId);
        $res = $this->_fetchOne($qry, $params);

        return $res !== null && $res >= 1;
    }

    protected function dbNow()
    {
        return 'UNIX_TIMESTAMP()';
    }
}
