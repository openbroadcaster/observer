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

        $checker = new OBFChecker();
        $methods = get_class_methods($checker);
        $results = [];

        $check_fatal_error = false;

        foreach ($methods as $method) {
            $result = $checker->$method();
            $results[] = $result;
            if ($result[2] > 1) {
                $check_fatal_error = true;
                break;
            }
        }

        foreach ($results as $result) {
            if ($result[2] == 0) {
                echo "\033[1;32m✔\033[0m ";
            } elseif ($result[2] == 1) {
                echo "\033[1;33m?\033[0m ";
            } else {
                echo "\033[1;31m🗴\033[0m ";
            }
            $output = $result[1];
            if (is_array($output)) {
                $output = implode(' ', $output);
            }
            $output = str_replace(PHP_EOL, ' ', $result[1]);
            $output = preg_replace('/\s+/', ' ', $output);
            $output = wordwrap($output);
            $output = str_replace(PHP_EOL, PHP_EOL . '  ', $output);
            echo $output . PHP_EOL;
        }

        if ($check_fatal_error) {
            echo "\033[31m";
            echo PHP_EOL . 'Error detected, testing stopped. Correct the above error then run again.' . PHP_EOL;
            echo "\033[0m";
        }
    }
}
