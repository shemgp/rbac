<?php
namespace PhpRbac;

/**
 * Provide NIST Level 2 Standard Role Based Access Control functionality.
 *
 * Allows maintainable function-level access control for enterprises, small
 * applications, or frameworks.
 *
 * Has three members, Roles, Users and Permissions for specific operations.
 *
 * @author jamesvl
 * @author abiusx
 *
 * @see http://phprbac.net/index.php
 * @see https://www.owasp.org/index.php/OWASP_PHPRBAC_Project
 */
class Rbac
{
    /** @var \models\PermissionManager */
    public $Permissions;
    /** @var \models\RoleManager */
    public $Roles;
    /** @var \models\UserManager */
    public $Users;

    public $Config;

    /**
     * Create this class and configure Manager classes with proper backend.
     *
     * You _must_ pass in a map of options with the following parameters:
     *
     * $Config is a map with the following keys:
     *   - dbType   - DSN prefix for PDO; e.g. 'mysql', 'pgsql', or 'sqlite'
     *
     *   - host     - DB host to connect to
     *   - port     - optional, port to connect to if not the default port
     *   OR
     *   - socket   - unix socket used to connect to database
     *   OR
     *   - filePath - absolute path to sqlite DB file
     *
     *   - dbName   - name of database to connect to, optional for sqlite
     *   - user     - username to connect with, optional for sqlite
     *   - pass     - the password to connect with, optional for sqlite
     *
     *   - appName  - optional, Postgres only
     *   - persist  - whether to use persistent DB connection; default is false
     *
     *   - pfx      - prefix for all table names, default is 'rbac_'
     *
     **/
    function __construct($Config)
    {
        $defaultCfg = array(
            'pfx' => 'rbac_'
        );

        $this->Config = array_merge($defaultCfg, $Config);

        $this->Permissions = new models\PermissionManager($this->Config);
        $this->Roles = new models\RoleManager($this->Config);
        $this->Users = new models\UserManager($this->Config);
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
        return $this->Roles->assign($Role, $Permission);
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
        return $this->Roles->unassign($Role, $Permission);
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
            throw new \Exception("\$UserID is a required argument.");
            //throw new exceptions\UserNotProvidedException("\$UserID is a required argument.");
        // model class will throw the specific exception commented out above,
        // but tests expect the general \Excpetion class, so leaving as-is

        $PermissionID = $this->Permissions->returnId($Permission);

        // if invalid, throw exception
        if ($PermissionID === null)
            throw new exceptions\PermissionNotFoundException("The permission '{$Permission}' not found.");

        return $this->Users->check($UserID, $PermissionID);
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
            throw new exceptions\UserNotProvidedException("\$UserID is a required argument.");

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

    /*
     * Present only because test cases expect this method.
     *
     * No need for Rbac class to actually have knowledge about table internals.
     *
     * @deprecated
     **/
    public function tablePrefix()
    {
        return $this->Roles->tablePrefix();
    }
}
