<?php

namespace ob\tools\cli;

global $argv;

if (!defined('OB_CLI')) {
    die('Command line access only.');
}

require_once('components.php');

$db = \OBFDB::get_instance();
$models = \OBFModels::get_instance();
$root = OB_LOCAL;

switch ($argv[2]) {
    case 'list':
        $modules = [];

        // Get all module directories and some metadata.
        $directories = array_filter(scandir($root . '/modules/'), fn ($f) => $f[0] !== '.');
        foreach ($directories as $moduleDir) {
            $modules[$moduleDir] = [
                'installed' => false,
            ];
        }

        // Get all installed modules to compare against what's available.
        $installed = $db->get('modules');
        foreach ($installed as $installedModule) {
            $modules[$installedModule['directory']]['installed'] = true;
        }

        // Sort modules by installed status.
        uasort($modules, fn ($a, $b) => $b['installed'] <=> $a['installed']);

        // List all modules and their status.
        foreach ($modules as $module => $data) {
            echo Helpers::bold($module) . PHP_EOL;
            echo "  Installed: " . ($data['installed'] ? 'yes' : 'no') . PHP_EOL;
        }
        break;
    case 'install':
        if (count($argv) < 4) {
            (new OBCLI())->help();
            exit(1);
        }
        $module = $argv[3];

        // Check if module exists.
        if (! is_dir($root . '/modules/' . $module)) {
            echo "Module not found." . PHP_EOL;
            exit(1);
        }

        // Check if module already installed first.
        $db->where('directory', $module);
        $installed = $db->get('modules');
        if ($installed) {
            echo "Module already installed." . PHP_EOL;
            exit(1);
        }

        // Attempt to install module.
        $success = $models->modules('install', $module);
        if ($success) {
            echo "Module installed." . PHP_EOL;
        } else {
            echo "An error occurred while attempting to install this module." . PHP_EOL;
            exit(1);
        }

        break;
    case 'uninstall':
        // TODO
        break;
    case 'purge':
        // TODO
        // TODO: Remember to run uninstall first as well.
        break;
}
