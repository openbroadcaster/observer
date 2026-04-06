<?php

// Copyright 2012-2025 OpenBroadcaster, Inc.
// SPDX-License-Identifier: AGPL-3.0-or-later

// command line tool (alpha)

namespace OpenBroadcaster\CLI;

define('OB_CLI', true);

require_once(__DIR__ . '/includes/helpers.php');

if (php_sapi_name() !== 'cli') {
    die('This tool can only be used from the command line.');
}

if (!file_exists(__DIR__ . '/../config.php')) {
    die('Missing config.php. Please make sure the OpenBroadcaster has a valid configuration file.' . PHP_EOL);
}

if (!is_dir(__DIR__ . '/../vendor')) {
    die('Missing vendor directory in OpenBroadcaster root directory. Install composer then run "composer install" to get required dependencies.' . PHP_EOL);
}

require_once(__DIR__ . '/../vendor/autoload.php');
require_once(__DIR__ . '/../core/init.php');

// Find the most specific CLI class based on the commands provided.
$commands = array_slice($argv, 1);
$cliInstance = null;
$commandLength = count($argv);
do {
    $className = implode('', array_map(fn ($x) => ucwords($x), $commands));

    if (file_exists(OB_LOCAL . '/core/cli/' . $className . '.php')) {
        require_once(OB_LOCAL . '/core/cli/' . $className . '.php');

        $fullClassName = 'OpenBroadcaster\\CLI\\' . $className;
        $cliInstance = new $fullClassName();

        break;
    }

    $commandLength = $commandLength - 1;
} while ($commands = array_slice($commands, 0, -1));

if ($cliInstance !== null) {
    $success = $cliInstance->run(array_values(array_slice($argv, $commandLength)));
    exit($success);
} else {
    (new OBCLI())->help();
}

class OBCLI
{
    public function help()
    {
        echo 'OpenBroadcaster CLI Tool (alpha). Run ob <command>.

Commands:
';

        echo Helpers::table(spacing: 5, rows: [
            ['check install', 'check installation for errors'],
            ['check media', 'check media for errors'],
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
}
