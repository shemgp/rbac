<?php
require_once __DIR__ . '/database.config';

global $TEST_CFG;

// global settings from phpunit_*.xml files
if ($GLOBALS['DB_ADAPTER'] == 'pdo_sqlite')
   $TEST_CFG = $cfg['sqlite'];
elseif ($GLOBALS['DB_ADAPTER'] == 'pdo_mysql')
   $TEST_CFG = $cfg['mysql'];
elseif ($GLOBALS['DB_ADAPTER'] == 'pdo_pgsql')
   $TEST_CFG = $cfg['pgsql'];

// turn on all errors
error_reporting(E_ALL);

// use Composer autoloader
$autoloader = require(__DIR__ . '/../../../autoload.php');
$autoloader->addPsr4('PhpRbac\\tests\\', __DIR__ . '/src');
