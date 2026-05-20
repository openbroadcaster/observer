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

        // Confirm that process is running as same user that web process uses, otherwise all sorts of permission
        // problems may happen and checks cannot be guaranteed to make sense. Note that this purposely ignores
        // self-signed or invalid SSL certificates.
        $token = bin2hex(random_bytes(32));
        $tmpFile = "/tmp/ob_cli_{$token}";
        touch($tmpFile);

        $requestUrl = rtrim(OB_SITE, '/') . '/same-user.php?token=' . $token;
        $ch = curl_init($requestUrl);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_NOBODY => true,
            CURLOPT_TIMEOUT => 5,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
        ]);
        curl_exec($ch);
        $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        unlink($tmpFile);

        if ($statusCode !== 200) {
            echo Helpers::bold('CLI process and web server are running as different users. (' . $statusCode . ') ') . PHP_EOL;
            exit(1);
        }

        // Find the most specific CLI class based on the commands provided.
        $commands = array_slice($this->argv, 1);
        $cliInstance = null;
        $cliMap = $this->getCliMap();
        $commandLength = count($this->argv);
        do {
            $command = implode(' ', $commands);

            if (isset($cliMap[$command])) {
                require_once($cliMap[$command]['file']);

                $cliInstance = new ($cliMap[$command]['class'])();

                break;
            }

            $commandLength = $commandLength - 1;
        } while ($commands = array_slice($commands, 0, -1));

        if ($cliInstance !== null) {
            $success = $cliInstance->run(array_values(array_slice($this->argv, $commandLength)));
            exit(! $success);
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

        $cliMap = $this->getCliMap();
        foreach ($cliMap as $cliCommand => $cliItem) {
            require_once($cliItem['file']);

            $cliClass = new ($cliItem['class'])();
            $help = $cliClass->help();

            if (is_array($help)) {
                foreach ($help as &$helpItem) {
                    $helpItem[0] = $cliCommand . ' ' . $helpItem[0];
                }
                $rows = [...$rows, ...$help];
            }

            if (is_string($help)) {
                $rows = [...$rows, [$cliCommand, $help]];
            }
        }

        echo Helpers::table(spacing: 5, rows: $rows);
    }

    private function getCliMap(): array
    {
        $cliMap = [];
        $coreDir = OB_LOCAL . '/core';
        $subDirs = [$coreDir, ...glob(OB_LOCAL . '/modules/*')];

        foreach ($subDirs as $subDir) {
            $dir = $subDir . '/cli/';
            if (! file_exists($dir) || ! is_dir($dir)) {
                continue;
            }

            $files = array_filter(scandir($dir), fn($f) => $f[0] !== '.');

            foreach ($files as $file) {
                $cliFileName = pathinfo($file, PATHINFO_FILENAME);
                $cliCommand = strtolower(preg_replace('/(?<!^)[A-Z]/', ' $0', $cliFileName));

                if ($subDir === $coreDir) {
                    $cliClassName = "OpenBroadcaster\\CLI\\{$cliFileName}";
                } else {
                    $moduleName = pathinfo($subDir, PATHINFO_FILENAME);
                    $cliClassName = "OpenBroadcaster\\Modules\\{$moduleName}\\CLI\\{$cliFileName}";
                }

                if (isset($cliMap[$cliCommand])) {
                    echo "Duplicate CLI command found: '{$cliCommand}'. Perhaps one or more models define conflicting commands? Quitting." . PHP_EOL;

                    exit(2);
                }

                if ($subDir === $coreDir) {
                }
                $cliMap[$cliCommand] = [
                    'type' => ($subDir === $coreDir) ? 'core' : 'module',
                    'file' => $dir . $file,
                    'class' => $cliClassName,
                ];
            }
        }

        return $cliMap;
    }
}

(new OBCLI($argv))->run();