<?php
namespace PhpRbac\models;

/**
 * Manage RBAC Roles.
 *
 * @author jamesvl
 * @author abiusx
 */
class RoleManager extends BaseRbac
{
    public function __construct($cfg)
    {
        parent::__construct($cfg);

        $dmapClass = "PhpRbac\\dmap\\{$cfg['dmap']}\\RoleDmap";
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
        $perms = new PermissionManager($this->cfg);

        $roleId = $this->returnId($Role);
        $permId = $perms->returnId($Permission);

        if ($roleId === null || $permId === null) {
            return false;
        }

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
        $perms = new PermissionManager($this->cfg);

        $roleId = $this->returnId($Role);
        $permId = $perms->returnId($Permission);

        $res = $this->dmap->unassign($roleId, $permId);

        return $res['success'];
    }


    /**
     * Unassigns all permissions belonging to a role
     *
     * @param integer   PK id of the Role to remove from use in Permissions
     * @return integer  Number of assigned roles deleted
     */
    public function unassignPermissions($ID)
    {
        $res = $this->dmap->unassignPermissionsFromRole($ID);

        return (int)$res['output'];
    }

    protected function _unassign($id)
    {
        $this->unassignPermissions($id);
        $this->unassignUsers($id);
    }

    /**
     * Unassign all users that have a certain role
     *
     * @param integer   PK id of the Role to remove from use by Users
     * @return integer  Number of User Roles deleted.
     */
    public function unassignUsers($ID)
    {
        $res = $this->dmap->unassignUsersFromRole($ID);

        return $res['output'];
    }


    /**
     * Checks to see if a role has a permission or not
     *
     * @param integer ID of the Role
     * @param integer ID of the Permission
     * @return boolean
     *
     * @todo: If we pass a Role that doesn't exist the method just returns
     *        false. We may want to check for a valid Role.
     */
    public function hasPermission($Role, $Permission)
    {
        $res = $this->dmap->hasPermission($Role, $Permission);

        return $res >= 1;
    }

    /**
     * Returns all permissions assigned to a role.
     *
     * @param integer|string   Role id or title or path
     * @param boolean          If true, result would be a list of IDs
     *
     * @return array|null  Either just a list of Permission id's, or a list
     *        where each contains the id, title and description of permissions,
     *        or null if none are found.
     */
    public function permissions($Role, $OnlyIDs = true)
    {
        if (!is_numeric($Role))
            $Role = $this->returnId($Role);

        if ($Role === null)
            return null;

        return $this->dmap->permissionsForRole($Role, $OnlyIDs);
    }
}
