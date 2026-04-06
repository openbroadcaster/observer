<?php

namespace OpenBroadcaster\CLI;

use OpenBroadcaster\Base\CLI;

class UpdatesRun extends CLI
{
    protected array $help = [
        ['updates run all', 'run all available updates'],
        ['updates run core', 'run core ob updates'],
        ['updates run module <name>', 'run updates for specified module'],
    ];

    public function run(array $args): bool
    {
        // only run update if check passes
        if (! Helpers::requireValid()) {
            return false;
        }

        if (count($args) < 1) {
            (new OBCLI())->help();
            return false;
        }

        switch ($args[0]) {
            case 'all':
                echo "\033[94;1mUpdating OB Core\033[0m" . PHP_EOL;
                $this->runUpdates('core');
                echo PHP_EOL . "\033[94;1mUpdating OB Modules\033[0m" . PHP_EOL;
                $this->runUpdates('module');
                break;
            case 'core':
                $this->runUpdates('core');
                break;
            case 'module':
                $this->runUpdates('module', $args[1] ?? null);
                break;
            default:
                (new OBCLI())->help();
                return false;
        }

        return true;
    }

    function runUpdates($type = 'core', $module = null)
    {
        require_once(__DIR__ . '/../../public/updates/updates.php');

        if ($type === 'core') {
            // Run all core updates.
            $list = $u->updates();
        } elseif ($module !== null) {
            $this->db->where('directory', $module);
            $installed = $this->db->get_one('modules');
            if (! $installed) {
                echo "Module {$module} is not installed." . PHP_EOL;
                return false;
            }

            // Run specified module updates.
            $u = new \OBFUpdates($module);
            $list = $u->updates();
        } else {
            // Run all module updates.
            $modules = array_filter(scandir(__DIR__ . '/../../modules/'), fn($f) => $f[0] !== '.');
            foreach ($modules as $module) {
                $this->db->where('directory', $module);
                $installed = $this->db->get_one('modules');
                if (! $installed) {
                    continue;
                }

                echo "Running updates for module {$module}..." . PHP_EOL;
                $this->runUpdates('module', $module);
            }

            return false;
        }

        foreach ($list as $update) {
            if ($update->needed) {
                if (!$u->run($update)) {
                    echo ucwords($type) . ': Update failed, exiting.' . PHP_EOL;
                    exit(1);
                }

                $prefix = "\033[94mCore:\033[0m ";
                if ($module !== null) {
                    $prefix = "\033[94m" . implode('', array_map(fn($x) => ucwords($x), explode('_', $module))) . ":\033[0m ";
                }
                echo $prefix . 'Update ' . $update->version . ' installed.' . PHP_EOL;
            }
        }
    }
}
