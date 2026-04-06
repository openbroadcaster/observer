<?php

// Copyright 2012-2025 OpenBroadcaster, Inc.
// SPDX-License-Identifier: AGPL-3.0-or-later

// command line tool (alpha)

namespace OpenBroadcaster\CLI;

class OBCLI
{
    private array $argv;

    public function __construct($argv = null)
    {
        $this->argv = $argv ?? [];
    }

    public function run()
    {
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
        $commands = array_slice($this->argv, 1);
        $cliInstance = null;
        $commandLength = count($this->argv);
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
            $success = $cliInstance->run(array_values(array_slice($this->argv, $commandLength)));
            exit($success);
        } else {
            $this->help();
        }
    }

    public function help()
    {
        echo 'OpenBroadcaster CLI Tool (alpha). Run ob <command>.

Commands:
';

        $rows = [];
        $cliList = array_filter(scandir(OB_LOCAL . '/core/cli/'), fn($f) => $f[0] !== '.');
        foreach($cliList as $cliPath) {
            $cliFileName = pathinfo($cliPath, PATHINFO_FILENAME);
            $cliCommand = strtolower(preg_replace('/(?<!^)[A-Z]/', ' $0', $cliFileName));
            $cliClassName = "OpenBroadcaster\\CLI\\" . $cliFileName;

            require_once(OB_LOCAL . '/core/cli/' . $cliPath);

            $cliClass = new $cliClassName();
            $rows = [...$rows, ...$cliClass->help()];
        }

        echo Helpers::table(spacing: 5, rows: $rows);
    }
}

(new OBCLI($argv))->run();