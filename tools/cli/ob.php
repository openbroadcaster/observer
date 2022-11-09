<?php

/*
    Copyright 2012-2022 OpenBroadcaster, Inc.

    This file is part of OpenBroadcaster Server.

    OpenBroadcaster Server is free software: you can redistribute it and/or modify
    it under the terms of the GNU Affero General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    OpenBroadcaster Server is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU Affero General Public License for more details.

    You should have received a copy of the GNU Affero General Public License
    along with OpenBroadcaster Server.  If not, see <http://www.gnu.org/licenses/>.
*/

// command line tool (alpha)

namespace ob\tools\cli;

require(__DIR__ . '/helpers.php');

define('OB_CLI', true);

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

$obcli = new OBCLI();

if (method_exists($obcli, $command)) {
    $obcli->$command();
} else {
    echo 'OpenBroadcaster CLI Tool (alpha). Run ob.php <command>.

Commands:
check    check configuration for errors
';
}



class OBCLI
{
    public function check()
    {
        require('updates/checker.php');

        $checker = new \OBFChecker();
        $methods = get_class_methods($checker);
        $results = [];
        $rows = [];
        $errors = 0;
        $warnings = 0;
        $pass = 0;

        $check_fatal_error = false;

        foreach ($methods as $method) {
            $result = $checker->$method();
            $results[] = $result;

            $formatting1 = '';
            $formatting2 = '';

            switch ($result[2]) {
                case 0:
                    $formatting = "\033[32m";
                    $pass++;
                    break;
                case 1:
                    $formatting = "\033[33m";
                    $warnings++;
                    break;
                case 2:
                    $formatting = "\033[31m";
                    $errors++;
            }
            $rows[] = [[$formatting,$result[0]], [$formatting, $result[1]]];

            if ($result[2] > 1) {
                $check_fatal_error = true;
                break;
            }
        }

        Helpers::table(rows: $rows);

        if ($check_fatal_error) {
            echo "\033[31m";
            echo PHP_EOL . 'Error detected, testing stopped. Correct the above error then run again.' . PHP_EOL;
            echo "\033[0m";
        } else {
                echo PHP_EOL .
                "\033[32m" . str_pad($pass, 2, ' ', STR_PAD_LEFT) . " pass\033[0m    " .
                "\033[33m" . str_pad($warnings, 2, ' ', STR_PAD_LEFT) . " warnings\033[0m    " .
                "\033[31m" . str_pad($errors, 2, ' ', STR_PAD_LEFT) . " errors\033[0m" . PHP_EOL;
        }
    }
}
