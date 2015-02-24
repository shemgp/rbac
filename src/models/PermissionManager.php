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
        $dmapClass = "PhpRbac\\dmap\\{$cfg['dmap']}\\PermissionDmap";
        $this->dmap = new $dmapClass($cfg);
    }

    protected function type()
    {
        return get_class($this);
    }

    /**
     * Assigns a role to a permission (or vice-verse).
     *
     * Will report failure if the permission already exists in the table.
     *
     * @param mixed   Id, Title, or Path of the Role
     * @param mixed   Id, Title, or Path of the Permission
     * @return boolean   True if inserted okay. False if existing or failure.
     *
     * @todo: Check for valid permissions/roles
     * @todo: Implement custom error handler
     */
    public function assign($Role, $Permission)
    {
        $roles = new RoleManager($this->cfg);

        $roleId = $roles->returnId($Role);
        $permId = $this->returnId($Permission);

        $res = $this->dmap->assign($roleId, $permId);

        return $res['success'];
    }

    /**
     * Unassigns a role-permission relation
     *
     * @param integer  Id of the Role
     * @param integer  Id of the Permission
     * @return boolean
     */
    public function unassign($Role, $Permission)
    {
        $roles = new RoleManager($this->cfg);

        $roleId = $roles->returnId($Role);
        $permId = $this->returnId($Permission);

        $res = $this->dmap->unassign($roleId, $permId);

        return $res['success'];
    }

    /**
     * Remove permissions from system
     *
     * @param integer $id
     *            permission id
     * @param boolean $recursive
     *            delete all descendants
     *
     */
    public function remove($id, $recursive = false)
    {
        $this->unassignRoles($id);

        if (!$recursive)
            return $this->dmap->moveChildrenUp($id);
        else
            return $this->dmap->removeChildren($id);
    }

    /**
     * Unassigns all roles of this permission, and returns their number
     *
     * @param integer $ID
     *      Permission Id
     * @return integer
     */
    public function unassignRoles($ID)
    {

        $res = $this->dmap->unassignRoles($ID);
        return (int) $res['output'];
    }

    protected function _unassign($id)
    {
        return $this->unassignRoles($id);
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
