<?php
namespace PhpRbac;

/**
 * Provide NIST Level 2 Standard Role Based Access Control functionality.
 *
 * Allows maintainable function-level access control for enterprises, small
 * applications, or frameworks.
 *
 * @see http://phprbac.net/index.php
 * @see https://www.owasp.org/index.php/OWASP_PHPRBAC_Project
 **/
class Rbac
{
    private $mgr;

    public function __construct($settings)
    {
        $this->mgr = new models\RbacManager($settings);
    }

    public function assign($role, $permission)
    {
        return $this->mgr->assign($role, $permission);
    }

    public function check($permission, $user_id)
    {
        return $this->mgr->check($permission, $user_id);
    }

    public function enforce($permission, $user_id)
    {
        return $this->mgr->enforce($permission, $user_id);
    }

    public function reset($ensure = false)
    {
        return $this->mgr->reset($ensure);
    }
}
