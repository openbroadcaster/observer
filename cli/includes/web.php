<?php

// must be run via web server.
// provides information regarding permissions and other things that can't be determined via CLI.

namespace OpenBroadcaster\CLI;

if (php_sapi_name() === 'cli') {
    die('This tool must not be run via the command line.' . PHP_EOL);
}

// required to bypass core/init.php verify install
define('OB_CLI', true);

require_once(__DIR__ . '/../../core/init.php');
require_once(__DIR__ . '/../../public/updates/checker.php');

$system_user = exec('whoami');

$checker = new \OBFChecker();

$output = [];
$output['system_user'] = exec('whoami');
$output['directories_valid'] = $checker->directories_valid();

echo json_encode($output);
