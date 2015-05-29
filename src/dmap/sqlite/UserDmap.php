<?php
namespace PhpRbac\dmap\sqlite;

class UserDmap extends \PhpRbac\dmap\mysql\UserDmap {

    public function check($userId, $permId)
    {
        // this works for sqlite
        $LastPart="AS Temp ON ( TR.ID = Temp.RoleID)
                WHERE TUrel.UserID = ?
                  AND Temp.ID = ?";

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

    public function resetAssignments()
    {
        $qry = "DELETE FROM {$this->pfx}userroles";
        $res = $this->_execQuery($qry);

        $qry =  "DELETE FROM sqlite_sequence
                 WHERE name = ?";
        $res = $this->_execQuery($qry, array($this->pfx . 'userroles'));

        return $res['output'];
    }

    protected function dbNow()
    {
        return "'now'";
    }
}
