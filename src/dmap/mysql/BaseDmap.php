<?php
namespace PhpRbac\dmap\mysql;



/**
 * Base data mapper for Permissions and Roles, since they are so similar.
 *
 * @author jamesvl
 * @author abiusx
 */
class BaseDmap extends \PhpRbac\utils\PdoDataMapper {

    protected $pfx;
    protected $nst;

    public function __construct($cfg, $tblName)
    {
        parent::__construct($cfg);

        $this->pfx = $cfg['pfx'];
        $this->tblName = $this->pfx . $tblName;

        $this->nst = new \PhpRbac\utils\FullNestedSet($this->tblName, 'id', 'lft', 'rght');

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
                       VALUES (?, ?, UNIX_TIMESTAMP())";

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

        // $qry ="DELETE FROM sqlite_sequence WHERE name = {$this->pfx}rolepermissions";
    }


    /**
     * Add a new child with the given Title and Description to $parentId.
     *
     * Child may be a Role or Permission, depending on sub-class.
     *
     * Returns status array.
     **/
    public function newFirstChild($parentId, $title, $descrip)
    {
        $toSave = array(
            'title' => $title,
            'description' => $descrip
        );

        return $this->nst->insertChildData($toSave, 'id = ?', $parentId);
    }

    public function count()
    {
        $qry = "SELECT COUNT(*)
                FROM {$this->tblName}";

        return $this->_fetchOne($qry);
    }


    /**
     * Get the id of a [Role|Permission] given just its string Path.
     **/
    public function idFromPath($path)
    {
        $Parts = explode( "/", $path );

        $GroupConcat = "GROUP_CONCAT(parent.Title ORDER BY parent.Lft SEPARATOR '/')";

        $sql = "SELECT node.id, $GroupConcat AS path
                  FROM {$this->tblName} AS node,
                       {$this->tblName} AS parent
                 WHERE node.lft BETWEEN parent.lft AND parent.rght
                   AND node.title = ?
                 GROUP BY node.id
                HAVING path = ?";

        $params = array($Parts[count($Parts) - 1], $path);

        $res = $this->_fetchRow($sql, $params);

        if ($res)
            return $res['id'];
        else
            return null;
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
        $qry = "SELECT parent.*
                  FROM {$this->tblName} AS node,
                       {$this->tblName} AS parent
                 WHERE node.lft BETWEEN parent.lft AND parent.rgth
                   AND (node.id = ?)
               ORDER BY parent.lft";

        $params = array($id);

        return $this->_fetchAll($qry, $params);
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

        $qry = "SELECT node.*,
                      (COUNT(parent.id) - 1 - (sub_tree.innerDepth )) AS Depth
                  FROM {$this->tblName} AS node,
                       {$this->tblName} AS parent,
                       {$this->tblName} AS sub_parent,
                   (
                       SELECT node.id,
                              (COUNT(parent.id) - 1) AS innerDepth
                         FROM {$this->tblName} AS node,
                              {$this->tblName} AS parent
                        WHERE node.lft BETWEEN parent.lft AND parent.rght
                              AND node.id = ?
                     GROUP BY node.id
                     ORDER BY node.lft
                   ) AS sub_tree

                 WHERE node.lft BETWEEN parent.lft AND parent.rght
                   AND node.lft BETWEEN sub_parent.lft AND sub_parent.rght
                   AND sub_parent.id = sub_tree.id
              GROUP BY node.id
                HAVING depth = 1
              ORDER BY node.lft";

        $params = array($id);
        $res = $this->_fetchAll($qry, $params);

        foreach ($res as &$v)
            unset($v['depth']);

        return $res;
    }

    public function descendants($id)
    {
        return $this->nst->descendantsConditional(false, 'id = ?', $id);
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
        $qry = "TRUNCATE TABLE {$this->tblName}";
        $res = $this->_execQuery($qry);

        /*
        $qry = "ALTER TABLE {$this->tblName} DROP COLUMN id;";
        $res = $this->_execQuery($qry);
        $qry = "ALTER TABLE {$this->tblName} ADD COLUMN `ID` INT UNSIGNED NOT NULL AUTO_INCREMENT FIRST,
                ADD PRIMARY KEY (`ID`);";
        $res = $this->_execQuery($qry);
         */

        // for sqlite:
        // $qry =  "DELETE FROM sqlite_sequence WHERE name = {$this->tblName}";

        $qry = "INSERT INTO {$this->tblName}
                       (title, description, lft, rght)
                VALUES (?, ?, ?, ?)";

        $params = array('root', 'root', 0, 1);
        $res = $this->_execQuery($qry, $params);

        return $res;
    }

    public function deleteConditional($cond, $id)
    {
        $this->nst->deleteConditional($cond, $id);
    }

    public function deleteSubtreeConditional($cond, $id)
    {
        $this->nst->deleteSubtreeConditional($cond, $id);
    }
}
