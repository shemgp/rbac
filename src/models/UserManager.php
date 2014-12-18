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

        $dmapClass = "PhpRbac\\dmap\\{$cfg['dbType']}\\UserDmap";
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
    function hasRole($Role, $UserID = null)
    {
        if ($UserID === null)
            throw new \PhpRbac\exceptions\UserNotProvidedException("\$UserID is a required argument.");

        $RoleID = $this->_getRoleId($Role);
        $res = $this->dmap->hasRole($RoleID, $UserID);

        return $res !== null;
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
    function assign($Role, $UserID = null)
    {
        if ($UserID === null)
            throw new \PhpRbac\exceptions\UserNotProvidedException("\$UserID is a required argument.");

        $RoleID = $this->_getRoleId($Role);

        return $this->dmap->assign($UserID, $RoleID);
    }

    /**
     * Unassigns a role from a user
     *
     * @param mixed $Role
     *            Id, Title, Path
     * @param integer $UserID
     *            UserID (use 0 for guest)
     *
     * @throws \exceptions\UserNotProvidedException
     * @return boolean success
     */
    function unassign($Role, $UserID = null)
    {
        if ($UserID === null)
            throw new \PhpRbac\exceptions\UserNotProvidedException("\$UserID is a required argument.");

        $RoleID = $this->_getRoleId($Role);

        return $this->dmap->unassign($UserID, $RoleID);
    }

    /**
     * Returns all roles of a user
     *
     * @param integer $UserID
     *            Not optional
     *
     * @throws \exceptions\UserNotProvidedException
     * @return array null
     *
     */
    function allRoles($UserID = null)
    {
        if ($UserID === null)
            throw new \PhpRbac\exceptions\UserNotProvidedException("\$UserID is a required argument.");


        return $this->dmap->allRoles($UserID);
    }

    /**
     * Return count of roles assigned to a user
     *
     * @param integer $UserID
     *
     * @throws \exceptions\UserNotProvidedException
     * @return integer Count of Roles assigned to a User
     */
    function roleCount($UserID = null)
    {
        if ($UserID === null)
            throw new \PhpRbac\exceptions\UserNotProvidedException("\$UserID is a required argument.");

        return $this->dmap->roleCount($UserID);
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

    public function check($UserID, $PermissionID)
    {
        return $this->dmap->check($UserID, $PermissionID);
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
    protected function _getRoleId($Role)
    {
        if (is_numeric($Role)) {
            $RoleID = $Role;
        }
        else {
            $roleMgr = new RoleManager($this->cfg);
            $RoleID = $roleMgr->returnId($Role);
        }

        return $RoleID;
    }


    /**
     * @deprecated
     **/
    public function tablePrefix()
    {
        return $this->cfg['pfx'];
    }
}
