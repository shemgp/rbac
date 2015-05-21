<?php
require_once __DIR__ . '/database.config';

global $TEST_CFG;

// turn on all errors
error_reporting(E_ALL);

// global settings from phpunit_*.xml files
$TEST_CFG = $cfg[$GLOBALS['CFG_KEY']];

// use Composer autoloader
//$autoloader = require(__DIR__ . '/../../../autoload.php');
$autoloader = require(__DIR__ . '/../vendor/autoload.php');
$autoloader->setPsr4('PhpRbac\\tests\\', __DIR__ . '/src');
