<?php
namespace PhpRbac\tests;

use \PhpRbac\Rbac;

/**
 * @file
 * Unit Tests for PhpRbac PSR Wrapper
 *
 * @defgroup phprbac_unit_test_wrapper_setup Unit Tests for Rbac Functionality
 * @ingroup phprbac_unit_tests
 * @{
 * Documentation for all Unit Tests regarding RbacSetup functionality.
 */

class RbacSetup extends Generic_Tests_DatabaseTestCase
{
    /*
     * Test Setup and Fixture
     */

	public static $rbac;

    public static function setUpBeforeClass()
    {
        global $TEST_CFG;
    	self::$rbac = new Rbac($TEST_CFG);

    	if ((string) $GLOBALS['DB_ADAPTER'] === 'pdo_sqlite') {
    	    self::$rbac->reset(true);
    	}
    }

    protected function setup() {
        self::$rbac->reset(true);
        parent::setup();
    }

    protected function tearDown()
    {
        if ((string) $GLOBALS['DB_ADAPTER'] === 'pdo_sqlite') {
            self::$rbac->reset(true);
        }
    }

    public function getDataSet()
    {
        return $this->_dataSet('database-seed', false);
    }

    protected function _dataSet($fileName, $flatXml = true)
    {
        $fileLoc = dirname(__FILE__) . $GLOBALS['DATASET_PATH'] . $fileName . '.' . $GLOBALS['DATASET_EXT'];

        if ($GLOBALS['DATASET_EXT'] === 'yml') {
            return new \PHPUnit_Extensions_Database_DataSet_YamlDataSet($fileLoc);
        }
        else {
            if ($flatXml)
                return $this->createFlatXMLDataSet($fileLoc);
            else
                return $this->createXMLDataSet($fileLoc);
        }
    }

    /*
     * Tests for proper object instantiation
     */

    public function testRbacInstance() {
        $this->assertInstanceOf('PhpRbac\Rbac', self::$rbac);
    }
}

/** @} */ // End group phprbac_unit_test_wrapper_setup */
