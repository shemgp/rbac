<?php
require_once('./database/database.confg');

global $TEST_CFG;
$TEST_CFG = $cfg;

// turn on all errors
error_reporting(E_ALL);

// autoloader
require dirname(__DIR__) . '/../../../autoload.php';
