<?php

// Copyright 2012-2025 OpenBroadcaster, Inc.
// SPDX-License-Identifier: AGPL-3.0-or-later

// command line tool (alpha)

namespace ob\tools\cli;

define('OB_CLI', true);
chdir(__DIR__ . '/../../');

require('tools/cli/includes/helpers.php');

if (php_sapi_name() !== 'cli') {
    die('This tool can only be used from the command line.');
}

if (!file_exists('config.php')) {
    die('Missing config.php. Please run from the OpenBroadcaster root directory.' . PHP_EOL);
}

if (!is_dir('vendor')) {
    die('Missing vendor directory (required for CLI tool). Install composer then run "composer install" to get required dependencies.' . PHP_EOL);
}

require('vendor/autoload.php');

$command = $argv[1] ?? '';
$subcommand = $argv[2] ?? '';

$obcli = new OBCLI();

if (method_exists($obcli, $command)) {
    $obcli->$command();
} else {
    $obcli->help();
}

class OBCLI
{
    public function help()
    {
        echo 'OpenBroadcaster CLI Tool (alpha). Run ob <command>.

Commands:
';

        echo Helpers::table(spacing: 5, rows: [
            ['check', 'check installation for errors'],
            ['cron run', 'run scheduled tasks once'],
            ['cron run <module> <task> [now]', 'run scheduled task for module'],
            ['cron monitor', 'monitor and run cron tasks as needed'],
            ['modules list', 'list all modules and their status'],
            ['modules install <name>', 'install module'],
            ['modules uninstall <name>', 'uninstall module'],
            ['modules purge <name>', 'uninstall module and delete all data'],
            ['updates list all', 'list all available updates'],
            ['updates list core', 'list core ob updates'],
            ['updates list module <name>', 'list updates for specified module'],
            ['updates run all', 'run all available updates'],
            ['updates run core', 'run core ob updates'],
            ['updates run module <name>', 'run updates for specified module'],
            ['passwd <username>', 'change password for user']
        ]);
    }

    public function check()
    {
        require(__DIR__ . '/commands/check.php');
    }

    public function cron()
    {
        global $subcommand;
        if ($subcommand == 'run' || $subcommand == 'monitor') {
            require(__DIR__ . '/commands/cron.php');
        } else {
            $this->help();
        }
    }

    public function modules()
    {
        global $subcommand;
        if (in_array($subcommand, ['list', 'install', 'uninstall', 'purge'])) {
            require(__DIR__ . '/commands/modules.php');
        } else {
            $this->help();
        }
    }

    public function updates()
    {
        global $argv;
        if (count($argv) < 4 || ! (in_array($argv[3], ['all', 'core', 'module']))) {
            $this->help();
            return false;
        }

        if ($argv[3] === 'module') {
            if (count($argv) < 5) {
                $this->help();
                return false;
            }

            $modules = array_filter(scandir(__DIR__ . '/../../modules/'), fn($f) => $f[0] !== '.');
            if (! in_array($argv[4], $modules)) {
                $this->moduleNotFound($argv[4]);
                return false;
            }
        }

        if ($argv[2] == 'run') {
            require(__DIR__ . '/commands/updates_run.php');
        } elseif ($argv[2] == 'list') {
            require(__DIR__ . '/commands/updates_list.php');
        } else {
            $this->help();
        }
    }

    public function passwd()
    {
        global $subcommand;
        if ($subcommand) {
            require(__DIR__ . '/commands/passwd.php');
        } else {
            $this->help();
        }
    }

    private function moduleNotFound($module)
    {
        echo "Module {$module} could not be found in the OpenBroadcaster installation.";
    }
}
