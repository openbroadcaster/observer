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
            ['cron run', 'run scheduled tasks'],
            ['updates list', 'list available updates'],
            ['updates run', 'run available updates']
        ]);
    }

    public function check()
    {
        require(__DIR__ . '/commands/check.php');
    }

    public function cron()
    {
        global $subcommand;
        if ($subcommand == 'run') {
            require('cron.php');
        } else {
            $this->help();
        }
    }

    public function updates()
    {
        global $subcommand;
        if ($subcommand == 'run') {
            require(__DIR__ . '/commands/updates_run.php');
        } elseif ($subcommand == 'list') {
            require(__DIR__ . '/commands/updates_list.php');
        } else {
            $this->help();
        }
    }
}
