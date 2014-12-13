<?php
namespace models;

/**
 * RBAC Manager - has the actual RBAC business logic.
 *
 * Documentation regarding Rbac Manager functionality.
 *
 * Rbac Manager: Provides NIST Level 2 Standard Hierarchical Role Based Access Control
 *
 * Has three members, Roles, Users and Permissions for specific operations
 *
 * @author jamesvl
 * @author abiusx
 * @version 2.0
 */
class Rbac
{
    /** @var \models\PermissionManager */
    public $Permissions;
    /** @var \models\RoleManager */
    public $Roles;
    /** @var \models\UserManager */
    public $Users;

    /**
     * Create this class and configure Manager classes with proper backend.
     *
     * You _must_ pass in a map of options with the following parameters:
     *
     * dmap - one of 'mysql', 'sqlite', or 'postgres', defined in /dmap
     * db   - itself a map containing
     *        - dsn: the full data source name to connect to the DB
     *        OR
     *        - server
     *        - schema (if applicable, for Postgres only)
     *        - port
     * user - username to connect to the db
     * pass - password used to db connection; may be null for sqlite
     * prefix - the table prefix to help namespace tables
     **/
    function __construct($opts)
    {
        $this->Permissions = new PermissionManager();
        $this->Roles = new RoleManager();
        $this->Users = new UserManager();
    }

    /**
     * Assign a role to a permission.
     *
     * @param string|integer Id, Title or Path of the Role
     * @param string|integer Id, Title or Path of the Permission
     * @return boolean       Indicates result was successful or not
     */
    function assign($Role, $Permission)
    {
        if (is_numeric($Role)) {
            $RoleID = $Role;
        }
        else {
            if (substr($Role, 0, 1) == "/")
                $RoleID = $this->Roles->pathId($Role);
            else
                $RoleID = $this->Roles->titleId($Role);
        }

        if (is_numeric($Permission)) {
            $PermissionID = $Permission;
        }
        else {
            if (substr($Permission, 0, 1) == "/")
                $PermissionID = $this->Permissions->pathId($Permission);
            else
                $PermissionID = $this->Permissions->titleId($Permission);
        }

        return $this->Roles->assign($RoleID, $PermissionID);
    }

    /**
     * Unassign a Role from a Permission.
     *
     * @param string|integer Id, Title or Path of the Role
     * @param string|integer Id, Title or Path of the Permission
     * @return boolean       Indicates result was successful or not
     **/
    function unassign($Role, $Permission)
    {
        if (is_numeric($Role)) {
            $RoleID = $Role;
        }
        else {
            if (substr($Role, 0, 1) == "/")
                $RoleID = $this->Roles->pathId($Role);
            else
                $RoleID = $this->Roles->titleId($Role);
        }

        if (is_numeric($Permission)) {
            $PermissionID = $Permission;
        }
        else {
            if (substr($Permission, 0, 1) == "/")
                $PermissionID = $this->Permissions->pathId($Permission);
            else
                $PermissionID = $this->Permissions->titleId($Permission);
        }

        return $this->Roles->unassign($RoleID, $PermissionID);
    }

    /**
     * Checks whether a user has a permission or not.
     *
     * @param string|integer $Permission
     *            You can provide a path like /some/permission, a title, or the
     *            permission ID.
     *            In case of ID, don't forget to provide integer (not a string
     *            containing a number).
     * @param string|integer   User ID of a user
     *
     * @throws exceptions\PermissionNotFoundException
     * @throws exceptions\UserNotProvidedException
     * @return boolean
     */
    function check($Permission, $UserID = null)
    {
        if ($UserID === null)
            throw new \exceptions\UserNotProvidedException("\$UserID is a required argument.");

        // convert permission to ID
        if (is_numeric ($Permission)) {
            $PermissionID = $Permission;
        }
        else {
            if (substr($Permission, 0, 1) == "/")
                $PermissionID = $this->Permissions->pathId($Permission);
            else
                $PermissionID = $this->Permissions->titleId($Permission);
        }

        // if invalid, throw exception
        if ($PermissionID === null)
            throw new exceptions\PermissionNotFoundException("The permission '{$Permission}' not found.");

        return $this->User->check($UserID, $PermissionID);
    }

    /**
     * Enforce a permission on a user.
     *
     * Side effects: sends a 403 Forbidden header and instantly dies().
     *
     * This will abort any code called by your framework, to use with caution!
    *
    * @param string|integer   path or title or ID of permission
    * @param integer          User id.
    *
     * @throws exceptions\UserNotProvidedException
    */
    function enforce($Permission, $UserID = null)
    {
        if ($UserID === null)
            throw new \exceptions\UserNotProvidedException("\$UserID is a required argument.");

        if (!$this->check($Permission, $UserID)) {
            header('HTTP/1.1 403 Forbidden');
            die("<strong>Forbidden</strong>: You do not have permission to access this resource.");
        }

        return true;
    }

    /**
    * Remove all roles, permissions and assignments in your database.
    *
    * Used for testing.
    *
    * @param boolean   Must set to true or throws error.
    * @return boolean
    */
    function reset($Ensure = false)
    {
        if ($Ensure !== true) {
            throw new \Exception("You must pass true to this function, otherwise it won't work.");
            return;
        }

        $res = true;
        $res = $res and $this->Roles->resetAssignments(true);
        $res = $res and $this->Roles->reset(true);
        $res = $res and $this->Permissions->reset(true);
        $res = $res and $this->Users->resetAssignments(true);

        return $res;
    }
}
