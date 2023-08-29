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
        runUpdates('core');
        echo PHP_EOL . 'Updating OB Modules' . PHP_EOL;
        runUpdates('module');
        break;
    case 'core':
        runUpdates('core');
        break;
    case 'module':
        runUpdates('module', $argv[4]);
        break;
    default:
        throw new Exception('Unreachable switch block; update requires either all, core, or module.');
}

exit(0);

function runUpdates($type = 'core', $module = null)
{
    require_once('updates/updates.php');

    if ($type === 'core') {
        // Run all core updates.
        $list = $u->updates();
    } elseif ($module !== null) {
        // Run specified module updates.
        $u = new \OBFUpdates($module);
        $list = $u->updates();
    } else {
        // Run all module updates.
        $modules = array_filter(scandir('./modules/'), fn($f) => $f[0] !== '.');
        foreach ($modules as $module) {
            runUpdates('module', $module);
        }
        return false;
    }

    foreach ($list as $update) {
        if ($update->needed) {
            if (!$u->run($update)) {
                echo ucwords($type) . ': Update failed, exiting.' . PHP_EOL;
                exit(1);
            }

            $prefix = 'Core: ';
            if ($module !== null) {
                $prefix = implode('', array_map(fn($x) => ucwords($x), explode('_', $module))) . ': ';
            }
            echo $prefix . 'Update ' . $update->version . ' installed.' . PHP_EOL;
        }
    }
}
