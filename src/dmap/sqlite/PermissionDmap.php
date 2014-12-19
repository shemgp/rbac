<?php
namespace PhpRbac\dmap\sqlite;

class PermissionDmap extends \PhpRbac\dmap\mysql\PermissionDmap {
    public function reset()
    {
        $qry = "DELETE FROM {$this->tblName}";
        $res = $this->_execQuery($qry);

        $qry =  "DELETE FROM sqlite_sequence
                 WHERE name = ?";
        $res = $this->_execQuery($qry, array($this->tblName));

        $qry = "INSERT INTO {$this->tblName}
                       (title, description, lft, rght)
                VALUES (?, ?, ?, ?)";

        $params = array('root', 'root', 0, 1);
        $res = $this->_execQuery($qry, $params);

        return $res;
    }
}
