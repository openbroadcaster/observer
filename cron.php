<?php

/*
    Copyright 2012-2024 OpenBroadcaster, Inc.

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

require_once('components.php');

$lock = new OBFLock('core-cron');
if (!$lock->acquire()) {
    die('Unable to get cron lock. Already running?' . PHP_EOL);
}

$db = OBFDB::get_instance();

// set cron last run
$db->where('name', 'cron_last_run');
$cron_last_run = $db->get('settings');
if (!$cron_last_run) {
    $db->insert('settings', ['name' => 'cron_last_run', 'value' => time()]);
} else {
    $db->where('name', 'cron_last_run');
    $db->update('settings', ['value' => time()]);
}

// require cron files
// TODO add support for module cron classes (last run tracked in db as cron-modulename-classname).
$jobs = [];
require_once('classes/base/cron.php');
foreach (glob('classes/cron/*.php') as $file) {
    require_once($file);
    $class = '\OB\Classes\Cron\\' . basename($file, '.php');
    $jobs[] = new $class();
}

// loop through jobs and check interval against last run. run if needed.
foreach ($jobs as $job) {
    // reliable way to get the class name without namespace
    $classReflection = new ReflectionClass($job);
    $className = strtolower($classReflection->getShortName());

    // get our last run time
    $db->where('name', 'cron-core-' . $className);
    $lastRun = $db->get_one('settings');
    $interval = $job->interval();

    // if no last run time, or time to run again...
    if (!$lastRun || $lastRun['value'] + $interval < time()) {
        $status = $job->run();

        // if successful, update last run time
        if ($status) {
            if ($lastRun) {
                // has previous run, update time.
                $db->where('name', 'cron-core-' . $className);
                $db->update('settings', ['value' => time()]);
            } else {
                // first time running, insert time.
                $db->insert('settings', ['name' => 'cron-core-' . $className, 'value' => time()]);
            }
        }
    }
}
