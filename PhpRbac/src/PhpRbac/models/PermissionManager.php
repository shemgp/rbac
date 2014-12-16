<?php
namespace PhpRbac\models;

/**
 * Manage RBAC Permissions
 *
 * @author jamesvl
 * @author abiusx
 */
class PermissionManager extends BaseRbac
{
    public function __construct($cfg)
    {
        parent::__construct($cfg);
        $dmapClass = "PhpRbac\\dmap\\{$cfg['dbType']}\\PermissionDmap";
        $this->dmap = new $dmapClass($cfg);
    }

    protected function type()
    {
        return get_class($this);
    }

    /**
     * Remove permissions from system
     *
     * @param integer $ID
     *            permission id
     * @param boolean $Recursive
     *            delete all descendants
     *
     */
    public function remove($ID, $Recursive = false)
    {
        $this->unassignRoles($ID);

        if (!$Recursive)
            return $this->dmap->moveChildrenUp($ID);
        else
            return $this->dmap->removeChildren($ID);
    }

    /**
     * Unassignes all roles of this permission, and returns their number
     *
     * @param integer $ID
     *      Permission Id
     * @return integer
     */
    protected function unassignRoles($ID)
    {

        $res = $this->dmap(unassignRoles($ID));
        return (int) $res['output'];
    }

    /**
     * Returns all roles assigned to a permission
     *
     * @param mixed $Permission
     *            Id, Title, Path
     * @param boolean $OnlyIDs
     *            if true, result will be a 1D array of IDs
     * @return Array 2D or 1D or null
     */
    public function roles($Permission, $OnlyIDs = true)
    {
        if (!is_numeric($Permission))
            $Permission = $this->returnId($Permission);

        return $this->dmap->rolesForPermission($Permission, $OnlyIDs);
    }
}
