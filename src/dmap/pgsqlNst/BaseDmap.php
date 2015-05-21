<?php
namespace PhpRbac\dmap\pgsqlNst;

/**
 * Base data mapper for Permissions and Roles, since they are so similar.
 *
 * @author jamesvl
 * @author abiusx
 */
class BaseDmap extends \PhpRbac\utils\PdoWrapper {

    protected $pfx;
    protected $nst;

    public function __construct($cfg, $tblName)
    {
        parent::__construct($cfg);

        $this->pfx = $cfg['pfx'];
        $this->tblName = $this->pfx . $tblName;

        $this->nst = new \PhpRbac\utils\FullNestedSet($this->tblName, 'id', 'lft', 'rgt');

        // legacy: MySQL data mappers user the Jf utility class to run queries
        // That (singleton) static class needs configuration:
        \PhpRbac\utils\Jf::setTablePrefix($this->pfx);
        \PhpRbac\utils\Jf::$Db = $this->getDBH();
    }


    /**
     * Assign a role and permission to each other.
     *
     **/
    public function assign($roleId, $permId)
    {
        if ($roleId === null || $permId === null) {
            return array(
                'success' => false,
                'reason' => 'Must be valid role or permission ids',
                'output' => null
            );
        }

        $qry = "INSERT INTO {$this->pfx}rolepermissions
                       (roleid, permissionid, assignmentdate)
                       VALUES (?, ?, {$this->dbNow()})";

        $params = array($roleId, $permId);

        return $this->_execQuery($qry, $params);
    }

    public function unassign($roleId, $permId)
    {
        $qry = "DELETE FROM {$this->pfx}rolepermissions
                 WHERE roleid = ?
                   AND permissionid = ?";

        $params = array($roleId, $permId);

        return $this->_execQuery($qry, $params);
    }

    public function resetAssignments()
    {
        $qry ="TRUNCATE TABLE {$this->pfx}rolepermissions";
        $res = $this->_execQuery($qry);
    }


    /**
     * Add a new child with the given Title and Description to $parentId.
     *
     * Child may be a Role or Permission, depending on sub-class.
     *
     * Returns id of newly inserted record, or null if it failed.
     **/
    public function newFirstChild($parentId, $title, $descrip)
    {
        $toSave = array(
            'title' => $title,
            'description' => $descrip
        );

        $dbh = $this->getDBH();
        $dbh->beginTransaction();

        $qry = "SELECT lft, rgt
            FROM {$this->tblName}
            WHERE id = ?";
        $params = array($parentId);

        $parent = $this->_fetchRow($qry, $params);

        if ($parent === null) {
            $dbh->rollBack();
            return null;
            //die('Invalid parentId in newFirstChild');
        }

        $rgtParam = array($parent['rgt']);
        $qry = "UPDATE {$this->tblName}
            SET rgt = rgt + 2
            WHERE rgt >= ?";
        $this->_execQuery($qry, $rgtParam);

        $qry = "UPDATE {$this->tblName}
            SET lft = lft + 2
            WHERE lft > ?";
        $this->_execQuery($qry, $rgtParam);

        $insQry = "INSERT INTO {$this->tblName}
                          (lft, rgt, title, description)
                   VALUES (?, ?, ?, ?)
                   RETURNING id";
        $insParams = array($parent['rgt'], $parent['rgt'] + 1, $title, $descrip);

        $res = $this->_execQuery($insQry, $insParams);


        if ($res['success']) {
            $dbh->commit();
            return $res['output'];
        }
        else {
            $dbh->rollBack();
            return null;
        }
    }

    public function count()
    {
        $qry = "SELECT COUNT(*)
                FROM {$this->tblName}";

        return $this->_fetchOne($qry);
    }


    /**
     * Get the id of a [Role|Permission] given just its string Path.
     *
     * Expects $path in the form of /element_1/element_2
     * No trailing slash, does have leading slash, and no leading 'root'.
     *
     * @param string
     **/
    public function idFromPath($path)
    {
        $path = 'root' . $path;
        $parts = explode( "/", $path );

        $qry = "SELECT node.id, STRING_AGG(parent.title, '/' ORDER BY parent.lft) AS path
                  FROM {$this->tblName} AS node,
                       {$this->tblName} AS parent
                 WHERE node.lft BETWEEN parent.lft AND parent.rgt
                   AND node.title = ?
                 GROUP BY node.id
                 HAVING STRING_AGG(parent.title, '/' ORDER BY parent.lft) = ?";

        $params = array($parts[count($parts) - 1], $path);

        $res = $this->_fetchRow($qry, $params);

        if ($res !== null)
            return $res['id'];
        else {
            return null;
        }
    }

    public function idFromTitle($title)
    {
        $qry = "SELECT id
                  FROM {$this->tblName}
                 WHERE Title = ?";
        $params = array($title);

        return $this->_fetchOne($qry, $params);
    }


    public function getTitleFromId($id)
    {
        $qry = "SELECT Title
                  FROM {$this->tblName}
                 WHERE ID = ?";
        $params = array($id);

        return $this->_fetchOne($qry, $params);
    }

    public function getPathForId($id)
    {
        $qry = "SELECT parent.id AS id, parent.title AS title
                  FROM {$this->tblName} AS node,
                       {$this->tblName} AS parent
                 WHERE node.lft BETWEEN parent.lft AND parent.rgt
                   AND (node.id = ?)
               ORDER BY parent.lft";

        $params = array($id);

        $rows = $this->_fetchAll($qry, $params);

        if ($rows === null) {
            return null;
        }
        else {
            if (count($rows) == '1')
                return '/';

            array_shift($rows);
            $titles = array_column($rows, 'title');

            return '/' . implode('/', $titles);
        }
    }

    public function getDescriptionFromId($id)
    {
        $qry = "SELECT description
                  FROM {$this->tblName}
                 WHERE id = ?";
        $params = array($id);

        return $this->_fetchOne($qry, $params);
    }


    public function update($id, $title = null, $descrip = null)
    {
        $toChange = '';
        $params = array();

        if ($title !== null) {
            $toChange = 'title = ?';
            $params[] = $title;
        }

        if ($descrip !== null) {
            if ($toChange !== '')
                $toChange .= ', ';

            $toChange .= 'description = ?';

            // set description to null in database by sending empty string
            if (trim($descrip) == '')
                $descrip = null;
            else
                $descrip = trim($descrip);

            $params[] = $descrip;
        }

        if ($toChange === '')
            return true;

        $qry = "UPDATE {$this->tblName}
                   SET $toChange
                 WHERE id = ?";

        $params[] = $id;

        $res = $this->_execQuery($qry, $params);

        return $res['output'] == 1;
    }

    /**
     *
     * was: childrenCondtional in FullNestedSet
     **/
    public function getChildrenOfId($id)
    {
        // or:
        // return $this->nst->childrenConditional('id = ?', $id);



        $qry = "SELECT  c.id, c.lft, c.rgt, c.title, c.description
                  FROM  {$this->tblName} p
                  JOIN  {$this->tblName} c
                    ON  c.lft BETWEEN p.lft AND p.rgt
                 WHERE   p.id = ?
                   AND (
                        SELECT  COUNT(*)
                        FROM    {$this->tblName} n
                        WHERE   c.lft BETWEEN n.lft AND n.rgt
                                AND n.lft BETWEEN p.lft AND p.rgt
                        ) <= 2
                   AND c.id != ?
                        ";

        $params = array($id, $id);
        $res = $this->_fetchAll($qry, $params);

        return $res;
    }

    /**
     * Nested set get descendants with depth.
     **/
    public function descendants($id)
    {
        // descendantsConditions
        $qry = "SELECT node.*, (COUNT(parent.id) - 1 - sub_tree.innerdepth) AS depth
            FROM {$this->tblName} AS node,
                {$this->tblName} AS parent,
                {$this->tblName} AS sub_parent,
                (
                    SELECT node.id, COUNT(parent.id - 1) AS innerdepth
                    FROM {$this->tblName} AS node,
                         {$this->tblName} AS parent
                   WHERE node.lft > parent.lft
                     AND node.rgt < parent.rgt
                     AND (node.id = ?)
                   GROUP BY node.id
                   ORDER BY node.lft
                ) AS sub_tree
               WHERE node.lft BETWEEN parent.lft AND parent.rgt
                 AND node.lft > sub_parent.lft
                 AND node.rgt < sub_parent.rgt
                 AND sub_parent.id = sub_tree.id
            GROUP BY node.id, sub_tree.innerdepth
              HAVING (COUNT(parent.id)-1) > 0
            ORDER BY node.id";

        $params = array($id);
        $res = $this->_fetchAll($qry, $params);

        $out = [];
        if (is_array($res)) {
            foreach ($res as $v) {
                $out[$v['title']] = $v;
            }
        }

        return $out;
    }

    public function depthOfId($id)
    {
       return $this->nst->depthConditional("id = ?", $id);
    }

    public function parentNodeOfId($id)
    {
        return $this->nst->parentNodeConditional("id = ?", $id);
    }

    public function reset()
    {
        $qry = "TRUNCATE TABLE {$this->tblName} RESTART IDENTITY";
        $res = $this->_execQuery($qry);

        $qry = "INSERT INTO {$this->tblName}
                       (title, description, lft, rgt)
                VALUES (?, ?, ?, ?)
             RETURNING id";

        $params = array('root', 'root', 0, 1);
        $res = $this->_execQuery($qry, $params);

        return $res;
    }


    /**
     * Delete a node and shift its children up a level.
     *
     * Return false is nothing to do.
     *
     * @param integer   PK id of the node to delete.
     **/
    public function moveChildrenUp($permId)
    {
        //return $this->nst->deleteConditional('id = ?', $permId);

        $dbh = $this->getDBH();
        $dbh->beginTransaction();

        $qry = "SELECT lft, rgt
            FROM {$this->tblName}
            WHERE id = ?";

        $parent = $this->_fetchRow($qry, array($permId));

        if ($parent === null) {
            $dbh->commit();
            return false;
        }

        $qry = "DELETE FROM {$this->tblName}
            WHERE id = ?";
        $delRes = $this->_execQuery($qry, array($permId));

        if ($delRes['output'] !== 1) {
            echo "\nerror in move children up";
            $dbh->rollBack();
            return false;
        }

        $qry = "UPDATE {$this->tblName}
            SET rgt = rgt - 1, lft = lft - 1
            WHERE lft BETWEEN ? AND ?";
        $this->_execQuery($qry, array($parent['lft'], $parent['rgt']));

        $qry = "UPDATE {$this->tblName}
            SET lft = lft - 2
            WHERE lft > ?";
        $this->_execQuery($qry, array($parent['rgt']));

        $qry = "UPDATE {$this->tblName}
            SET rgt = rgt - 2
            WHERE rgt > ?";
        $this->_execQuery($qry, array($parent['rgt']));

        $dbh->commit();

        return $delRes['output'] == 1;
    }

    /**
     * Delete a node and all of its descendants.
     *
     * Return false is nothing to do.
     *
     * @param integer   PK id of the node to delete.
     **/
    public function removeChildren($permId)
    {
        // return $this->nst->deleteSubtreeConditional('id = ?', $permId);

        $dbh = $this->getDBH();
        $dbh->beginTransaction();

        $qry = "SELECT lft, rgt, rgt - lft + 1 AS width
            FROM {$this->tblName}
            WHERE id = ?";

        $parent = $this->_fetchRow($qry, array($permId));

        if ($parent === null) {
            $dbh->commit();
            return false;
        }

        $qry = "DELETE FROM {$this->tblName}
            WHERE lft BETWEEN ? AnD ?";
        $delRes = $this->_execQuery($qry, array($parent['lft'], $parent['rgt']));

        $qry = "UPDATE {$this->tblName}
            SET lft = lft - ?
            WHERE lft > ?";
        $this->_execQuery($qry, array($parent['width'], $parent['rgt']));

        $qry = "UPDATE {$this->tblName}
            SET rgt = rgt - ?
            WHERE rgt > ?";
        $this->_execQuery($qry, array($parent['width'], $parent['rgt']));

        $dbh->commit();

        return $delRes['output'] >= 1;
    }

    protected function dbNow()
    {
        return "EXTRACT(EPOCH FROM now())::INT";
    }
}
