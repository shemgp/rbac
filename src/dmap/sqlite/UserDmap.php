<?php
namespace PhpRbac\dmap\sqlite;

class UserDmap extends \PhpRbac\dmap\mysql\UserDmap {

    public function check($userId, $permId)
    {
        // this works for sqlite
        $LastPart="AS temp ON (tr.id = temp.roleid)
                WHERE turel.userid = ?
                  AND temp.id = ?";

        $qry = "SELECT COUNT(*) AS result
                  FROM {$this->pfx}userroles AS turel
                  JOIN {$this->pfx}roles AS trdirect ON (trdirect.id = turel.roleid)
                  JOIN {$this->pfx}roles AS TR ON (tr.lft BETWEEN trdirect.lft AND trdirect.rgt)
                  JOIN
                  (        {$this->pfx}permissions AS tpdirect
                      JOIN {$this->pfx}permissions AS tp ON (tpdirect.lft BETWEEN tp.lft AND tp.rgt)
                      JOIN {$this->pfx}rolepermissions AS trel ON (tp.id = trel.permissionid)
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
