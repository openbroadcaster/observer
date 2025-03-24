<?php

namespace ob\tools\cli;

global $argv;

if (!defined('OB_CLI')) {
    die('Command line access only.');
}

require_once('components.php');

$db = \OBFDB::get_instance();

switch ($argv[2]) {
    case 'list':
        // Get all module directories and some metadata.
        // TODO

        // Get all installed modules to compare against what's available.
        $modules = $db->get('modules');
        $installed = [];
        foreach ($modules as $module) {
            $installed[] = [
                $module['id'],
                $module['directory']
            ];
        }

        // List all modules and their status.
        $rows = $installed; // TODO
        echo Helpers::table(spacing: 5, rows: $rows);
        break;
    case 'install':
        // TODO
        break;
    case 'uninstall':
        // TODO
        break;
    case 'purge':
        // TODO
        // TODO: Remember to run uninstall first as well.
        break;
}
