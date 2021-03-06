<?php
namespace PhpRbac\tests;

/**
 * @file
 * Unit Tests for PhpRbac PSR Wrapper
 *
 * @defgroup phprbac_unit_test_wrapper_user_manager Unit Tests for RbacUserManager Functionality
 * @ingroup phprbac_unit_tests
 * @{
 * Documentation for all Unit Tests regarding RbacUserManager functionality.
 */

class RbacUsersTest extends RbacSetup
{
    /*
     * Test for proper object instantiation
     */

    public function testUsersInstance() {
        $this->assertInstanceOf('PhpRbac\models\UserManager', self::$rbac->Users);
    }

    /*
     * Tests for self::$rbac->Users->assign()
     */

    public function testUsersAssignWithId()
    {
        $role_id = self::$rbac->Roles->add('roles_1', 'roles Description 1');

        self::$rbac->Users->assign($role_id, 5);

        $dataSet = $this->getConnection()->createDataSet();

        $filterDataSet = new \PHPUnit_Extensions_Database_DataSet_DataSetFilter($dataSet);
        $filterDataSet->addIncludeTables(array(
            self::$rbac->Users->tablePrefix() . 'userroles',
        ));

        $filterDataSet->setExcludeColumnsForTable(
            self::$rbac->Users->tablePrefix() . 'userroles',
            array('assignmentdate')
        );

        $expectedDataSet = $this->_dataSet('/users/expected_assign_with_id');

        $this->assertDataSetsEqual($expectedDataSet, $filterDataSet);
    }

    public function testUsersAssignWithPath()
    {
        self::$rbac->Roles->addPath('/roles_1/roles_2/roles_3');
        $role_id = self::$rbac->Roles->pathId('/roles_1/roles_2/roles_3');

        self::$rbac->Users->assign('/roles_1/roles_2', 5);

        $dataSet = $this->getConnection()->createDataSet();

        $filterDataSet = new \PHPUnit_Extensions_Database_DataSet_DataSetFilter($dataSet);
        $filterDataSet->addIncludeTables(array(
            self::$rbac->Users->tablePrefix() . 'userroles',
        ));

        $filterDataSet->setExcludeColumnsForTable(
            self::$rbac->Users->tablePrefix() . 'userroles',
            array('assignmentdate')
        );

        $expectedDataSet = $this->_dataSet('/users/expected_assign_with_path');

        $this->assertDataSetsEqual($expectedDataSet, $filterDataSet);
    }

    /**
     * @expectedException PhpRbac\exceptions\UserNotProvidedException
     */
    public function testUsersAssignNoUserID()
    {
        $result = self::$rbac->Users->assign(5);

        $this->assertFalse($result);
    }

    /**
     * @expectedException PHPUnit_Framework_Error
     */
    public function testUsersAssignPassNothing()
    {
        $result = self::$rbac->Users->assign();
    }

    /*
     * Tests for self::$rbac->Users->hasRole()
     */
    public function testUsersHasRoleId()
    {
        $role_id = self::$rbac->Roles->add('roles_1', 'roles Description 1');

        self::$rbac->Users->assign($role_id, 5);

        $result = self::$rbac->Users->hasRole($role_id, 5);

        $this->assertTrue($result);
    }

    public function testUsersHasRoleTitle()
    {
        $role_id = self::$rbac->Roles->add('roles_1', 'roles Description 1');

        self::$rbac->Users->assign($role_id, 5);

        $result = self::$rbac->Users->hasRole('roles_1', 5);

        $this->assertTrue($result);
    }

    public function testUsersHasRolePath()
    {
        self::$rbac->Roles->addPath('/roles_1/roles_2/roles_3');
        $role_id = self::$rbac->Roles->pathId('/roles_1/roles_2/roles_3');

        self::$rbac->Users->assign($role_id, 5);

        $result = self::$rbac->Users->hasRole('/roles_1/roles_2/roles_3', 5);

        $this->assertTrue($result);
    }

    public function testUsersHasRoleDoesNotHaveRole()
    {
        $role_id = self::$rbac->Roles->add('roles_1', 'roles Description 1');

        self::$rbac->Users->assign($role_id, 5);

        $result = self::$rbac->Users->hasRole(1, 5);

        $this->assertFalse($result);
    }

    public function testUsersHasRoleNullRole()
    {
        $role_id = self::$rbac->Roles->add('roles_1', 'roles Description 1');

        self::$rbac->Users->assign($role_id, 5);

        $result = self::$rbac->Users->hasRole(null, 5);

        $this->assertFalse($result);
    }

    /**
     * @expectedException PhpRbac\exceptions\UserNotProvidedException
     */
    public function testUsersHasRoleNoUserId()
    {
        $result = self::$rbac->Users->hasRole(5);
    }

    /**
     * @expectedException PHPUnit_Framework_Error
     */
    public function testUsersHasRolePassNothing()
    {
        $result = self::$rbac->Users->hasRole();
    }

    /*
     * Tests for self::$rbac->Users->allRoles()
     */
    public function testUsersAllRoles()
    {
        $role_id_1 = self::$rbac->Roles->add('roles_1', 'roles Description 1');
        $role_id_2 = self::$rbac->Roles->add('roles_2', 'roles Description 2');
        $role_id_3 = self::$rbac->Roles->add('roles_3', 'roles Description 3');

        self::$rbac->Users->assign($role_id_1, 5);
        self::$rbac->Users->assign($role_id_2, 5);
        self::$rbac->Users->assign($role_id_3, 5);

        $result = self::$rbac->Users->allRoles(5);

        $expected = array(
        	array(
                'id' => '2',
        	    'lft' => '1',
        	    'rgt' => '2',
        	    'title' => 'roles_1',
        	    'description' => 'roles Description 1',
            ),
        	array(
                'id' => '3',
        	    'lft' => '3',
        	    'rgt' => '4',
        	    'title' => 'roles_2',
        	    'description' => 'roles Description 2',
            ),
        	array(
                'id' => '4',
        	    'lft' => '5',
        	    'rgt' => '6',
        	    'title' => 'roles_3',
        	    'description' => 'roles Description 3',
            ),
        );

        if ($GLOBALS['DB_ADAPTER'] === 'pdo_pgsql') {
            $this->convertIntKeys($expected);
        }

        $this->assertSame($expected, $result);
    }

    public function testUsersAllRolesBadRoleNull()
    {
        $result = self::$rbac->Users->allRoles(10);

        $this->assertNull($result);
    }

    /**
     * @expectedException PhpRbac\exceptions\UserNotProvidedException
     */
    public function testUsersAllRolesNoRolesEmpty()
    {
        $result = self::$rbac->Users->allRoles();
    }

    /*
     * Tests for self::$rbac->Users->roleCount()
     */
    public function testUsersRoleCount()
    {
        $role_id_1 = self::$rbac->Roles->add('roles_1', 'roles Description 1');
        $role_id_2 = self::$rbac->Roles->add('roles_2', 'roles Description 2');
        $role_id_3 = self::$rbac->Roles->add('roles_3', 'roles Description 3');

        self::$rbac->Users->assign($role_id_1, 5);
        self::$rbac->Users->assign($role_id_2, 5);
        self::$rbac->Users->assign($role_id_3, 5);

        $result = self::$rbac->Users->roleCount(5);

        $this->assertSame(3, $result);
    }

    public function testUsersRoleCountNoRoles()
    {
        $result = self::$rbac->Users->roleCount(10);

        $this->assertSame(0, $result);
    }

    /**
     * @expectedException PhpRbac\exceptions\UserNotProvidedException
     */
    public function testUsersRoleCountNoRolesEmpty()
    {
        $result = self::$rbac->Users->roleCount();
    }

    /*
     * Tests for self::$rbac->Users->unassign()
     */
    public function testUsersUnassignId()
    {
        $role_id_1 = self::$rbac->Roles->add('roles_1', 'roles Description 1');
        $role_id_2 = self::$rbac->Roles->add('roles_2', 'roles Description 2');
        $role_id_3 = self::$rbac->Roles->add('roles_3', 'roles Description 3');

        self::$rbac->Users->assign($role_id_1, 5);
        self::$rbac->Users->assign($role_id_2, 5);
        self::$rbac->Users->assign($role_id_3, 5);

        self::$rbac->Users->unassign($role_id_2, 5);

        $dataSet = $this->getConnection()->createDataSet();

        $filterDataSet = new \PHPUnit_Extensions_Database_DataSet_DataSetFilter($dataSet);
        $filterDataSet->addIncludeTables(array(
            self::$rbac->Users->tablePrefix() . 'userroles',
        ));

        $filterDataSet->setExcludeColumnsForTable(
            self::$rbac->Users->tablePrefix() . 'userroles',
            array('assignmentdate')
        );

        $expectedDataSet = $this->_dataSet('/users/expected_unassign');

        $this->assertDataSetsEqual($expectedDataSet, $filterDataSet);
    }

    public function testUsersUnassignTitle()
    {
        $role_id_1 = self::$rbac->Roles->add('roles_1', 'roles Description 1');
        $role_id_2 = self::$rbac->Roles->add('roles_2', 'roles Description 2');
        $role_id_3 = self::$rbac->Roles->add('roles_3', 'roles Description 3');

        self::$rbac->Users->assign($role_id_1, 5);
        self::$rbac->Users->assign($role_id_2, 5);
        self::$rbac->Users->assign($role_id_3, 5);

        self::$rbac->Users->unassign('roles_2', 5);

        $dataSet = $this->getConnection()->createDataSet();

        $filterDataSet = new \PHPUnit_Extensions_Database_DataSet_DataSetFilter($dataSet);
        $filterDataSet->addIncludeTables(array(
            self::$rbac->Users->tablePrefix() . 'userroles',
        ));

        $filterDataSet->setExcludeColumnsForTable(
            self::$rbac->Users->tablePrefix() . 'userroles',
            array('assignmentdate')
        );

        $expectedDataSet = $this->_dataSet('/users/expected_unassign');

        $this->assertDataSetsEqual($expectedDataSet, $filterDataSet);
    }

    public function testUsersUnassignPath()
    {
        $role_id_1 = self::$rbac->Roles->add('roles_1', 'roles Description 1');
        $role_id_2 = self::$rbac->Roles->add('roles_2', 'roles Description 2');
        $role_id_3 = self::$rbac->Roles->add('roles_3', 'roles Description 3');

        self::$rbac->Users->assign($role_id_1, 5);
        self::$rbac->Users->assign($role_id_2, 5);
        self::$rbac->Users->assign($role_id_3, 5);

        self::$rbac->Users->unassign('/roles_2', 5);

        $dataSet = $this->getConnection()->createDataSet();

        $filterDataSet = new \PHPUnit_Extensions_Database_DataSet_DataSetFilter($dataSet);
        $filterDataSet->addIncludeTables(array(
            self::$rbac->Users->tablePrefix() . 'userroles',
        ));

        $filterDataSet->setExcludeColumnsForTable(
            self::$rbac->Users->tablePrefix() . 'userroles',
            array('assignmentdate')
        );

        $expectedDataSet = $this->_dataSet('/users/expected_unassign');

        $this->assertDataSetsEqual($expectedDataSet, $filterDataSet);
    }

    /**
     * @expectedException PhpRbac\exceptions\UserNotProvidedException
     */
    public function testUsersUnassignNoUserIdException()
    {
        $result = self::$rbac->Users->unassign(5);
    }

    /**
     * @expectedException PHPUnit_Framework_Error
     */
    public function testUsersUnassignNoRolesException()
    {
        $result = self::$rbac->Users->unassign();
    }

    /*
     * Tests for self::$rbac->Users->resetAssignments()
     */

    public function testUsersResetAssignments()
    {
        $role_id_1 = self::$rbac->Roles->add('roles_1', 'roles Description 1');
        $role_id_2 = self::$rbac->Roles->add('roles_2', 'roles Description 2');
        $role_id_3 = self::$rbac->Roles->add('roles_3', 'roles Description 3');

        self::$rbac->Users->assign($role_id_1, 5);
        self::$rbac->Users->assign($role_id_2, 5);
        self::$rbac->Users->assign($role_id_3, 5);

        self::$rbac->Users->resetAssignments(true);

        $dataSet = $this->getConnection()->createDataSet();

        $filterDataSet = new \PHPUnit_Extensions_Database_DataSet_DataSetFilter($dataSet);
        $filterDataSet->addIncludeTables(array(
            self::$rbac->Users->tablePrefix() . 'userroles',
        ));

        $filterDataSet->setExcludeColumnsForTable(
            self::$rbac->Users->tablePrefix() . 'userroles',
            array('assignmentdate')
        );

        $expectedDataSet = $this->_dataSet('/users/expected_reset_assignments');

        $this->assertDataSetsEqual($expectedDataSet, $filterDataSet);
    }

    /**
     * @expectedException Exception
     */
    public function testUsersResetAssignmentsException()
    {
        self::$rbac->Users->resetAssignments();
    }
}

/** @} */ // End group phprbac_unit_test_wrapper_user_manager */
