<?php

// must be run via web server.
// provides information regarding permissions and other things that can't be determined via CLI.

namespace ob\tools\cli;

if (php_sapi_name() === 'cli') {
    die('This tool must not be run via the command line.' . PHP_EOL);
}

// required to bypass components.php verify install
define('OB_CLI', true);

chdir(__DIR__ . '/../../../');
require_once('components.php');
require_once('updates/checker.php');

$system_user = exec('whoami');

$checker = new \OBFChecker();

$output = [];
$output['system_user'] = exec('whoami');
$output['directories_valid'] = $checker->directories_valid();

echo json_encode($output);
