<?php

namespace ob\tools\cli;

if (!defined('OB_CLI')) {
    die('Command line access only.');
}

// only show update list if check passes
Helpers::requireValid();

switch ($argv[3]) {
    case 'all':
        echo "OB Core Updates" . PHP_EOL;
        listUpdates('core');
        echo PHP_EOL . "OB Module Updates" . PHP_EOL;
        listUpdates('module');
        break;
    case 'core':
        listUpdates('core');
        break;
    case 'module':
        listUpdates('module', $argv[4]);
        break;
    default:
        throw new Exception('Unreachable switch block; update requires either all, core, or module.');
}

function listUpdates($type = 'core', $module = null)
{
    require_once('updates/updates.php');

    if ($type === 'core') {
        // List all core updates.
        $list = $u->updates();
    } elseif ($module !== null) {
        // List specified module updates.
        $list = (new \OBFUpdates($module))->updates();
    } else {
        // List all module updates.
        $modules = array_filter(scandir('./modules/'), fn($f) => $f[0] !== '.');
        foreach ($modules as $module) {
            $moduleClass = implode('', array_map(fn($x) => ucwords($x), explode('_', $module)));
            echo "Module: " . $moduleClass . PHP_EOL;
            listUpdates('module', $module);
        }
        return false;
    }

    $rows = [];

    $installed = 0;
    $pending = 0;

    foreach ($list as $update) {
        if ($update->needed) {
            $pending++;
            $formatting = "\033[33m";
        } else {
            $installed++;
            $formatting = "\033[32m";
        }
        $rows[] = [[$formatting, $update->version], [$formatting, implode(' ', $update->items())]];
    }

    echo Helpers::table(spacing: 3, rows: $rows);

    echo PHP_EOL .
    "\033[32m" . str_pad($installed, 2, ' ', STR_PAD_LEFT) . " installed\033[0m    " .
    "\033[33m" . str_pad($pending, 2, ' ', STR_PAD_LEFT) . " pending\033[0m" . PHP_EOL;
}
