<?php

namespace OpenBroadcaster\CLI;

use OpenBroadcaster\Base\CLI;

class UpdatesList extends CLI
{
    protected array|string $help = [
        ['all', 'list all available updates'],
        ['core', 'list core ob updates'],
        ['module <name>', 'list updates for specified module'],
    ];

    public function run(array $args): bool
    {
        // only show update list if check passes
        if (! Helpers::requireValid()) {
            return false;
        }

        if (count($args) < 1) {
            (new OBCLI())->help();
            return false;
        }

        switch ($args[0]) {
            case 'all':
                echo "\033[94;1mOB Core Updates\033[0m" . PHP_EOL;
                $this->listUpdates('core');
                echo PHP_EOL . "\033[94;1mOB Module Updates\033[0m" . PHP_EOL;
                $this->listUpdates('module');
                break;
            case 'core':
                $this->listUpdates('core');
                break;
            case 'module':
                $this->listUpdates('module', $args[1] ?? null);
                break;
            default:
                (new OBCLI())->help();
                return false;
        }
        return true;
    }

    private function listUpdates($type = 'core', $module = null)
    {
        if ($type === 'core') {
            // List all core updates.
            $list = (new \OpenBroadcaster\Support\Updates())->updates();
        } elseif ($module !== null) {
            // List specified module updates.
            $list = (new \OpenBroadcaster\Support\Updates($module))->updates();
        } else {
            // List all module updates.
            $modules = array_filter(scandir(__DIR__ . '/../../modules/'), fn($f) => $f[0] !== '.');
            foreach ($modules as $module) {
                $this->db->where('directory', $module);
                $installed = $this->db->get_one('modules');
                if (! $installed) {
                    continue;
                }

                $moduleClass = implode('', array_map(fn($x) => ucwords($x), explode('_', $module)));
                echo "\033[94mModule:\033[0m " . $moduleClass . PHP_EOL;
                $this->listUpdates('module', $module);
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
}
