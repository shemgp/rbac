<?php
namespace PhpRbac\dmap\sqlite;

class UserDmap extends \PhpRbac\dmap\mysql\UserDmap {

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
