<?php

namespace ob\tools\cli;

if (!defined('OB_CLI')) {
    die('Command line access only.');
}

// only run update if check passes
Helpers::requireValid();

switch ($argv[3]) {
    case 'all':
        echo 'Updating OB Core' . PHP_EOL;
        updateCore();
        echo PHP_EOL . 'Updating OB Modules' . PHP_EOL;
        updateModule();
        break;
    case 'core':
        updateCore();
        break;
    case 'module':
        updateModule($argv[4]);
        break;
    default:
        throw new Exception('Unreachable switch block; update requires either all, core, or module.');
}

exit(0);

function updateCore()
{
    require_once('updates/updates.php');
    $list = $u->updates();
    foreach ($list as $update) {
        if ($update->needed) {
            if (!$u->run($update)) {
                echo 'Update failed, exiting.' . PHP_EOL;
                exit(1);
            }
            echo 'Update ' . $update->version . ' installed.' . PHP_EOL;
        }
    }
}

function updateModule($module = null)
{
    if ($module === null) {
        // TODO: update all modules
    } else {
        // TODO: update specified module
    }
}
