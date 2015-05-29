<?php
namespace PhpRbac\models;

/**
 * Manage RBAC assigned Users.
 *
 * @author jamesvl
 * @author abiusx
 */
class UserManager
{
    protected $cfg;
    protected $dmap;

    public function __construct($cfg)
    {
        $this->cfg = $cfg;

        $dmapClass = "PhpRbac\\dmap\\{$cfg['dmap']}\\UserDmap";
        $this->dmap = new $dmapClass($cfg);
    }

    /**
     * Checks to see whether a user has a role or not
     *
     * @param integer|string   id, title or path of the Role
     * @param integer          UserID, not optional
     *
     * @throws \exceptions\UserNotProvidedException
     * @return boolean success
     */
    function hasRole($role, $userId = null)
    {
        if ($userId === null)
            throw new \PhpRbac\exceptions\UserNotProvidedException("\$userId is a required argument.");

        $roleId = $this->_getRoleId($role);
        $res = $this->dmap->hasRole($roleId, $userId);

        return $res;
    }

    /**
     * Assigns a role to a user
     *
     * @param mixed     Id, Path or Title of the Role
     * @param integer   UserID (use 0 for guest)
     *
     * @throws \exceptions\UserNotProvidedException
     * @return boolean inserted or existing
     */
    function assign($role, $userId = null)
    {
        if ($userId === null)
            throw new \PhpRbac\exceptions\UserNotProvidedException("\$userId is a required argument.");

        $roleId = $this->_getRoleId($role);

        return $this->dmap->assign($userId, $roleId);
    }

    /**
     * Unassigns a role from a user
     *
     * @param mixed $role
     *            Id, Title, Path
     * @param integer $userId
     *            UserID (use 0 for guest)
     *
     * @throws \exceptions\UserNotProvidedException
     * @return boolean success
     */
    function unassign($role, $userId = null)
    {
        if ($userId === null)
            throw new \PhpRbac\exceptions\UserNotProvidedException("\$userId is a required argument.");

        $roleId = $this->_getRoleId($role);

        return $this->dmap->unassign($userId, $roleId);
    }

    /**
     * Returns all roles of a user
     *
     * @param integer $userId
     *            Not optional
     *
     * @throws \exceptions\UserNotProvidedException
     * @return array null
     *
     */
    function allRoles($userId = null)
    {
        if ($userId === null)
            throw new \PhpRbac\exceptions\UserNotProvidedException("\$userId is a required argument.");


        return $this->dmap->allRoles($userId);
    }

    /**
     * Return count of roles assigned to a user
     *
     * @param integer $userId
     *
     * @throws \exceptions\UserNotProvidedException
     * @return integer Count of Roles assigned to a User
     */
    function roleCount($userId = null)
    {
        if ($userId === null)
            throw new \PhpRbac\exceptions\UserNotProvidedException("\$userId is a required argument.");

        return $this->dmap->roleCount($userId);
    }

    /**
     * Remove all role-user relations.  Mostly used for testing.
     *
     * Side effects: clears the user roles table, and adds back in the 'root'
     * role.
     *
     * @param boolean   Must set to true or throws an Exception.
     * @return number of deleted relations
     */
    function resetAssignments($Ensure = false)
    {
        if ($Ensure !== true) {
            throw new \Exception("You must pass true to this function, otherwise it won't work.");
            return;
        }

        $numDeleted = $this->dmap->resetAssignments();
        $this->assign('root', 1);

        return $numDeleted;
    }

    public function check($userId, $permissionId)
    {
        return $this->dmap->check($userId, $permissionId);
    }


    /**
     * Get the primary-key id of a Role, even if it is a path or Title.
     *
     * If passed an integer, then function assumes that is already the role id.
     *
     * Paths _must_ start with the slash ('/') character, or they will be
     * interpreted as a title.
     *
     * @param string|integer   Role to identify by its id
     * @param integer          PK id of the Role
     **/
    protected function _getRoleId($role)
    {
        if (is_numeric($role)) {
            $roleId = $role;
        }
        else {
            $roleMgr = new RoleManager($this->cfg);
            $roleId = $roleMgr->returnId($role);
        }

        return $roleId;
    }


    /**
     * @deprecated
     **/
    public function tablePrefix()
    {
        return $this->cfg['pfx'];
    }
}
