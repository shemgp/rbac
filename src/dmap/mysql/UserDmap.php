<?php
namespace PhpRbac\dmap\mysql;

class UserDmap extends \PhpRbac\utils\PdoDataMapper {

    protected $pfx;

    public function __construct($cfg, $tblName = 'userroles')
    {
        $this->pfx = $cfg['pfx'];
        parent::__construct($cfg);

        $this->tblName = $this->pfx . $tblName;
    }

    public function hasRole($RoleID, $UserID)
    {
        $qry = "SELECT tur.userid
                  FROM {$this->pfx}userroles AS tur
                  JOIN {$this->pfx}roles trdirect ON
                       (trdirect.id = tur.roleid)
                  JOIN {$this->pfx}roles tr ON
                       (tr.lft BETWEEN trdirect.lft AND trdirect.rght)
                 WHERE tur.userid = ?
                   AND tr.id = ?";
        $params = array($UserID, $RoleID);

        return $this->_fetchOne($qry, $params);
    }

    public function assign($UserID, $RoleID)
    {
        $qry = "INSERT INTO {$this->pfx}userroles
                (userid, roleid, assignmentdate)
                VALUES (?, ?, UNIX_TIMESTAMP())";

        $params = array($UserID, $RoleID);
        $res = $this->_execQuery($qry, $params);

        return $res['success'];
    }

    public function unassign($UserID, $RoleID)
    {
        $qry = "DELETE FROM {$this->pfx}userroles
                 WHERE userid = ?
                   AND roleid = ?";

        $params = array($UserID, $RoleID);
        $res = $this->_execQuery($qry, $params);

        return $res['success'];
    }

    public function allRoles($UserID)
    {
        $qry = "SELECT tr.*
                  FROM {$this->pfx}userroles AS ur
                  JOIN {$this->pfx}roles AS tr ON
                       (ur.roleid = tr.id)
                 WHERE ur.userid = ?";

        $params = array($UserID);

        $rows = $this->_fetchAll($qry, $params);

        if (empty($rows))
            return null;
        else
            return $rows;
    }

    public function roleCount($UserID)
    {
        $qry = "SELECT COUNT(userid) AS result
                  FROM {$this->pfx}userroles
                 WHERE userid = ?";

        $params = array($UserID);

        return (int) $this->_fetchOne($qry, $params);
    }

    public function resetAssignments()
    {
        $qry = "TRUNCATE TABLE {$this->pfx}userroles";
        $res = $this->_execQuery($qry);

        return $res['output'];

        // sqlite:
        // "delete from sqlite_sequence where name=? ", $this->tablePrefix () . "_userroles"
    }

    public function check($userId, $permId)
    {
        $LastPart = "ON (TR.ID = TRel.RoleID)
                WHERE TUrel.UserID = ?
                  AND TPdirect.ID = ?";

        $qry = "SELECT COUNT(*) AS Result
                  FROM {$this->pfx}userroles AS TUrel
                  JOIN {$this->pfx}roles AS TRdirect ON (TRdirect.ID=TUrel.RoleID)
                  JOIN {$this->pfx}roles AS TR ON ( TR.Lft BETWEEN TRdirect.Lft AND TRdirect.Rght)
                  JOIN
                  (        {$this->pfx}permissions AS TPdirect
                      JOIN {$this->pfx}permissions AS TP ON ( TPdirect.Lft BETWEEN TP.Lft AND TP.Rght)
                      JOIN {$this->pfx}rolepermissions AS TRel ON (TP.ID=TRel.PermissionID)
                  ) $LastPart";

        $params = array($userId, $permId);

        $res = $this->_fetchOne($qry, $params);

        return $res !== null && $res >= 1;
    }

}
