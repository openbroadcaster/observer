<?php

namespace OpenBroadcaster\CLI;

use OpenBroadcaster\Base\CLI;

class Modules extends CLI
{
    protected array $help = [
        ['modules list', 'list all modules and their status'],
        ['modules install <name>', 'install module'],
        ['modules uninstall <name>', 'uninstall module'],
        ['modules purge <name>', 'uninstall module and delete all data'],
    ];

    public function run(array $args): bool
    {
        $root = OB_LOCAL;

        if (count($args) < 1) {
            (new OBCLI())->help();
            return false;
        }

        switch ($args[0]) {
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
                $installed = $this->db->get('modules');
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
                if (count($args) < 2) {
                    (new OBCLI())->help();
                    return false;
                }
                $module = $args[1];

                // Check if module exists.
                if (! is_dir($root . '/modules/' . $module)) {
                    echo "Module not found." . PHP_EOL;
                    return false;
                }

                // Check if module already installed first.
                $this->db->where('directory', $module);
                $installed = $this->db->get('modules');
                if ($installed) {
                    echo "Module already installed." . PHP_EOL;
                    return false;
                }

                // Attempt to install module.
                $success = $this->models->modules('install', $module);
                if ($success) {
                    echo "Module successfully installed." . PHP_EOL;
                } else {
                    echo "An error occurred while attempting to install this module." . PHP_EOL;
                    return false;
                }

                break;
            case 'uninstall':
                if (count($args) < 2) {
                    (new OBCLI())->help();
                    return false;
                }
                $module = $args[1];

                // Check if module actually installed.
                $this->db->where('directory', $module);
                $installed = $this->db->get('modules');
                if (! $installed) {
                    echo "Module not installed or couldn't be found." . PHP_EOL;
                    return false;
                }

                // Attempt to uninstall module.
                $success = $this->models->modules('uninstall', $module);
                if ($success) {
                    echo "Module successfully uninstalled." . PHP_EOL;
                } else {
                    echo "An error occurred while attempting to uninstall this module." . PHP_EOL;
                    return false;
                }

                break;
            case 'purge':
                if (count($args) < 2) {
                    (new OBCLI())->help();
                    return false;
                }
                $module = $args[1];

                // Check if module exists.
                if (! is_dir($root . '/modules/' . $module)) {
                    echo "Module not found." . PHP_EOL;
                    return false;
                }

                // Run purge on module. Note that the model is responsible for ensuring that
                // the module is uninstalled first.
                $success = $this->models->modules('purge', $module);
                if ($success) {
                    echo "Module data successfully purged." . PHP_EOL;
                } else {
                    echo "An error occurred while attempting to purge the module data." . PHP_EOL;
                    return false;
                }
                break;
            default:
                (new OBCLI())->help();
                return false;
                break;
        }

        return true;
    }
}
