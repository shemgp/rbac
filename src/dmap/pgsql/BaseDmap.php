<?php
namespace PhpRbac\dmap\pgsql;

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

        $res = $this->_execQuery($qry, $params);
        return $res['success'];
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
     * Returns status array.
     **/
    public function newFirstChild($parentId, $title, $descrip)
    {
        // null titles are not allowed
        if ($title === null || trim($title) === '')
            return 0;

        $toSave = array(
            'title' => $title,
            'description' => $descrip
        );

        $qry = "INSERT INTO {$this->tblName}
            (parent, title, description)
            VALUES (?, ?, ?)
            RETURNING id";

        $params = array($parentId, $title, $descrip);
        $res = $this->_execQuery($qry, $params, 'id');

        return $res['output'];
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
        $parts = explode('/', $path);
        $depth = count($parts);

        $qry = "WITH RECURSIVE
            paths AS
            (
                SELECT id, parent, '' AS title, description, ARRAY[id] AS path
                  FROM {$this->tblName}
                 WHERE id = 1
            UNION ALL
                SELECT c.id, c.parent, paths.title || '/' || c.title,
                       c.description, paths.path || c.id
                  FROM paths
                  JOIN {$this->tblName} c ON (c.parent = paths.id)
                 WHERE array_upper(path, 1) <= ?
            )
            SELECT id, parent, title, description, path
              FROM paths
             WHERE array_length(path, 1) = ?
               AND title = ?
          ORDER BY path";

        $params = array($depth, $depth, $path);

        $res = $this->_fetchRow($qry, $params);

        if ($res)
            return $res['id'];
        else
            return null;
    }

    /**
     * Get the id of a node from its title.
     *
     * This assumes all titles of the node are unique. This is not enforced
     * by the database in any way.
     *
     * If titles are not unique, the id returned is valid but there are no
     * guarantees of its ordering in the tree.
     **/
    public function idFromTitle($title)
    {
        $qry = "SELECT id
                  FROM {$this->tblName}
                 WHERE title = ?";
        $params = array($title);

        return $this->_fetchOne($qry, $params);
    }


    public function getTitleFromId($id)
    {
        $qry = "SELECT title
                  FROM {$this->tblName}
                 WHERE id = ?";
        $params = array($id);

        return $this->_fetchOne($qry, $params);
    }

    public function getPathForId($id)
    {
        $qry = "WITH RECURSIVE
        ancestors AS
        (
            SELECT  id, parent, title, description, 1 AS level, title AS path
              FROM  {$this->tblName}
             WHERE  id = ?
        UNION ALL
            SELECT  r.id, r.parent,  r.title, r.description, level + 1,
                    r.title || '/' || ancestors.path AS path
              FROM  ancestors
              JOIN  {$this->tblName} r ON r.id = ancestors.parent
        )
        SELECT id, parent, title, description, level, path
          FROM ancestors
      ORDER BY level DESC
          LIMIT 1";

        $params = array($id);

        $row = $this->_fetchRow($qry, $params);
        if ($row !== null) {
            // strip leading 'root'
            $out = substr($row['path'], 4);
        }
        else
            $out = null;

        return $out;
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

        $qry = "SELECT id, parent, title, description
                  FROM {$this->tblName}
                 WHERE parent = ?
              ORDER BY id";

        $params = array($id);
        $res = $this->_fetchAll($qry, $params);

        return $res;
    }

    /**
     * Get all descendants of a parent node. *Does* include the parent node.
     *
     * Nodes are not returned in any particular order relative to the root.
     *
     * @param integer   PK id of the node.
     * @return array    id, title, description, and depth of each of its descendants
     **/
    public function descendants($id, $discardParent = true)
    {
        $qry = "WITH RECURSIVE
        descendants AS
        (
            SELECT  id, title, description, 0 AS depth
              FROM  {$this->tblName}
             WHERE  id = ?
        UNION ALL
            SELECT  child.id, child.title, child.description, depth + 1
              FROM  descendants
              JOIN  {$this->tblName} child ON child.parent = descendants.id
        )
        SELECT  *
          FROM  descendants
      ORDER BY id";

        $params = array($id);

        $res = $this->_fetchAll($qry, $params);
        $out = array();

        if (is_array($res)) {
            if ($discardParent)
                array_shift($res); // discard the parent node

            foreach ($res as $v) {
                $out[$v['title']] = $v;
            }
        }

        return $out;
    }

    /**
     * Get all ancestor nodes of the given $id.
     **/
    protected function ancestors($id)
    {
        $qry = "WITH RECURSIVE
        ancestors AS
        (
            SELECT *, 0 AS depth
              FROM {$this->tblName}
             WHERE id = ?
       UNION ALL
            SELECT p.*, depth + 1
              FROM ancestors
              JOIN {$this->tblName} p ON p.id = ancestors.parent
        )
        SELECT id, parent, title, description, depth
          FROM ancestors
      ORDER BY depth DESC";

        $params = array($id);

        return $this->_fetchAll($qry, $params);
    }

    public function depthOfId($id)
    {
        $qry = "WITH RECURSIVE
        ancestors AS
        (
            SELECT  id, parent, title, description, 0 AS depth
              FROM  {$this->tblName}
             WHERE  id = ?
        UNION ALL
            SELECT  r.id, r.parent,  r.title, r.description, depth + 1 AS depth
              FROM  ancestors
              JOIN  {$this->tblName} r ON r.id = ancestors.parent
        )
        SELECT id, parent, title, description, depth
          FROM ancestors
      ORDER BY depth DESC
          LIMIT 1";

       $params = array($id);
       $res = $this->_fetchRow($qry, $params);

       return $res['depth'];
    }

    public function parentNodeOfId($id)
    {
        $qry = "SELECT id, parent, title, description
                  FROM {$this->tblName}
                 WHERE id = (
                       SELECT parent
                         FROM {$this->tblName}
                        WHERE id = ?
                       )";

        $params = array($id);
        return $this->_fetchRow($qry, $params);
    }

    public function reset()
    {
        $qry = "TRUNCATE TABLE {$this->tblName} RESTART IDENTITY";
        $res = $this->_execQuery($qry);

        $qry = "INSERT INTO {$this->tblName}
                       (title, description, parent)
                VALUES (?, ?, ?)
             RETURNING id";

        $params = array('root', 'root', null);
        $res = $this->_execQuery($qry, $params, 'id');

        return $res;
    }

    public function deleteConditional($cond, $id)
    {
        echo "\n$cond\n";
        echo "\n$id\n";
        die('nyi - BaseDmap deleteConditional');
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
        $qry = "SELECT *
                  FROM {$this->tblName}
                 WHERE id = ?";
        $params = array($permId);

        $toDel = $this->_fetchRow($qry, $params);

        if (empty($toDel))
            return false;

        $updQry = "UPDATE {$this->tblName}
                     SET parent = ?
                   WHERE parent = ?";
        $updParams = array($toDel['parent'], $toDel['id']);

        $this->_execQuery($updQry, $updParams);

        $delQry = "DELETE FROM {$this->tblName}
                    WHERE id = ?";
        $delParams = array($permId);

        $delRes = $this->_execQuery($delQry, $delParams);

        return $delRes['success'];
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
        $descendants = $this->descendants($permId, false);

        if ($descendants === null)
            return;

        $descIds = array_column($descendants, 'id');

        $placeholders = implode(', ', array_fill(0, count($descIds), '?'));

        $qry = "DELETE FROM {$this->tblName}
                 WHERE id IN ($placeholders)";

        $this->_execQuery($qry, $descIds);
    }


    public function deleteSubtreeConditional($cond, $id)
    {
        die('nyi - BaseDmap deleteSubtreeConditional');
    }

    protected function dbNow()
    {
        return "EXTRACT(EPOCH FROM now())::INT";
    }
}
