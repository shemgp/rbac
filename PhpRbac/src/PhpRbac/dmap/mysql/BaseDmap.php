<?php
namespace \PhpRbac\dmap\mysql;

/**
 * Base data mapper for Permissions and Roles, since they are so similar.
 *
 * @author jamesvl
 * @author abiusx
 */
class BaseDmap extends utils\DbRepo {

    protected $type;
    protected $pfx;
    protected $nst;

    public function __construct($settings)
    {
        $this->type = $settings['type'];
        $this->pfx = $settings['tbl_prefix'];

        $this->tblName = $this->pfx . $this->type();

        $this->nst = new \utils\FullNestedSet($this->tblName, 'id', 'lft', 'rght');
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
        $Parts = explode ( "/", $path );

        $GroupConcat = "GROUP_CONCAT(parent.Title ORDER BY parent.Lft SEPARATOR '/')";

        $sql = "SELECT node.ID, $GroupConcat AS Path
                  FROM {$this->tblName)} AS node,
                       {$this->tblName)} AS parent
                 WHERE node.Lft BETWEEN parent.Lft AND parent.Rght
                   AND node.Title = ?
                 GROUP BY node.ID
                HAVING Path = ?";

        $res = Jf::sql($sql, $Parts[count($Parts) - 1], $path);

        if ($res)
            return $res[0]['ID'];
        else
            return null;
    }

    public function getIdFromTitle($title)
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
        $params = array($id);

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
            return array('success' => true, 'reason' => 'Nothing to change');

        $qry = "UPDATE {$this->tblName}
            SET $rows
            WHERE id = ?";

        return $this->_execQuery($qry, $params);
    }

    // was: childrenCondtional in FullNestedSet
    public function getChildrenOfId($id)
    {
        $qry = "SELECT node.*,
                      (COUNT(parent.id) - 1 - (sub_tree.innerDepth )) AS Depth
                  FROM {$this->tblName} AS node,
                       {$this->tblName} AS parent,
                       {$this->tblName} AS sub_parent,
                   (
                       SELECT node.id,
                              (COUNT(parent.id) - 1) AS innerDepth
                         FROM {$this->tblName} AS node,
                              {$this->tblName()} AS parent
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

        $params = array(id);
        $res = $this->_fetchAll($qry, $params);

        if ($res['success']) {
            foreach ($res as &$v)
                unset($v['depth']);
        }

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
        $qry = "DELETE FROM {$this->tblName}";
        $res = $this->_execQuery($qry);

        $qry = "ALTER TABLE {$this->tblName} AUTO_INCREMENT = 1");
        $res = $this->_execQuery($qry);

        // for sqlite:
        // $qry =  "DELETE FROM sqlite_sequence WHERE name = {$this->tblName}";

        $qry = "INSERT INTO {$this->tblName)}
                       (Title, Description, Lft, Rght)
                VALUES (?, ?, ?, ?)";

        $params = array('root', 'root', 0, 1);
        $res = $this->_execQuery($qry, $params);

        return $res;
    }

    public function assign($roleId, $permId)
    {
        $qry = "INSERT INTO {$this->pfx}rolepermissions
                       (roleid, permissionid, assignmentdate)
                       VALUES (?, ?, NOW())";

        $params = array($roleId, $permissionId);

        return $this->_execQuery($qry, $params);
    }

    public function unassign($roleId, $permid)
    {
        $qry = "DELETE FROM {$this->pfx}rolepermissions
                 WHERE role = ?
                   AND permissionid = ?";

        $params = array($roleId, $permissionId);

        return $this->_execQuery($qry, $params);
    }

    public function resetAssignments()
    {
        $qry ="DELETE FROM {$this->pfx}rolepermissions";
        $res = $this->_execQuery($qry);

        $qry = "ALTER TABLE {$this->pfx}rolepermissions AUTO_INCREMENT = 1";
        $res = $this->_execQuery($qry);

        // $qry ="DELETE FROM sqlite_sequence WHERE name = {$this->pfx}rolepermissions";

    }

}
