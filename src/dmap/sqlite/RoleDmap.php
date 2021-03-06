<?php
namespace PhpRbac\dmap\sqlite;

class RoleDmap extends \PhpRbac\dmap\mysql\RoleDmap {
    /**
     * Get the id of a [Role|Permission] given just its string Path.
     **/
    public function idFromPath($path)
    {
        $path = 'root' . $path;
        $Parts = explode( "/", $path );

        $qry = "SELECT node.id AS id, GROUP_CONCAT(parent.title, '/') AS path
                  FROM {$this->tblName} AS node,
                       {$this->tblName} AS parent
                 WHERE node.lft BETWEEN parent.lft AND parent.rgt
                   AND node.title = ?
                 GROUP BY node.id
                HAVING path = ?
                ORDER BY parent.lft";

        $params = array($Parts[count($Parts) - 1], $path);

        $res = $this->_fetchRow($qry, $params);

        if ($res !== null)
            return (int)$res['id'];
        else
            return null;
    }

    public function resetAssignments()
    {
        $qry ="DELETE FROM {$this->pfx}rolepermissions";
        $res = $this->_execQuery($qry);

        $qry ="DELETE FROM sqlite_sequence
                WHERE name = ?";
        $res = $this->_execQuery($qry, array("{$this->pfx}rolepermissions"));
    }

    public function reset()
    {
        $qry = "DELETE FROM {$this->tblName}";
        $res = $this->_execQuery($qry);

        $qry =  "DELETE FROM sqlite_sequence
                 WHERE name = ?";
        $res = $this->_execQuery($qry, array($this->tblName));

        $qry = "INSERT INTO {$this->tblName}
                       (title, description, lft, rgt)
                VALUES (?, ?, ?, ?)";

        $params = array('root', 'root', 0, 1);
        $res = $this->_execQuery($qry, $params);

        return $res;
    }

    protected function dbNow()
    {
        return "'now'";
    }
}
