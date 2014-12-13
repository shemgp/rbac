<?php
namespace \PhpRbac\dmap\mysql;

/**
 * Base data mapper for Permissions and Roles, since they are so similar.
 *
 * @author jamesvl
 * @author abiusx
 */
class RoleDmap extends dmap\mysql\RoleDmap {

    protected $tblName;

    public function __construct($settings)
    {
        $this->type = $settings['type'];
        $this->pfx = $settings['tbl_prefix'];

        $this->tblName = $this->pfx . $this->type();
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

        // this is the only difference from the MySQL RoleDmap...
        $GroupConcat = 'GROUP_CONCAT(parent.Title,'/')';

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

}
