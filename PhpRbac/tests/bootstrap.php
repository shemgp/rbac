<?php
require_once('./database/database.config');

global $TEST_CFG;

// global settings from phpunit_*.xml files
if ($GLOBALS['DB_ADAPTER'] == 'pdo_sqlite')
   $TEST_CFG = $cfg['sqlite'];
elseif ($GLOBALS['DB_ADAPTER'] == 'pdo_mysql')
   $TEST_CFG = $cfg['mysql'];

// turn on all errors
error_reporting(E_ALL);

// use Composer autoloader
require dirname(__DIR__) . '/../../../autoload.php';
