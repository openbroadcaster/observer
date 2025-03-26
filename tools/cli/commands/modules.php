<?php

namespace ob\tools\cli;

global $argv;

if (!defined('OB_CLI')) {
    die('Command line access only.');
}

require_once('components.php');

$db = \OBFDB::get_instance();
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
