<?php

namespace ob\tools\cli;

if (!defined('OB_CLI')) {
    die('Command line access only.');
}

require_once('components.php');

$db = \OBFDB::get_instance();

// lock is aquired right before running task, and released right after.
$lock = new \OBFLock('core-cron');

// require cron files
// TODO add support for module cron classes (last run tracked in db as cron-modulename-classname).
$jobs = [];
require_once('classes/base/cron.php');
foreach (glob('classes/cron/*.php') as $file) {
    require_once($file);
    $class = '\\OB\\Classes\Cron\\' . basename($file, '.php');
    $jobs[] = new $class();
}

foreach (glob('modules/*', GLOB_ONLYDIR) as $module) {
    foreach (glob($module . '/cron/*.php') as $file) {
        $moduleNamespace = str_replace(' ', '', ucwords(str_replace('_', ' ', basename($module))));
        require_once($file);
        $class = '\\OB\\Modules\\' . $moduleNamespace . '\\Cron\\' . basename($file, '.php');
        $jobs[] = new $class();
    }
}

// check our jobs to see what needs to be run
$jobs_to_run = [];
foreach ($jobs as $index => $job) {
    // reliable way to get the class name without namespace
    $classReflection = new \ReflectionClass($job);
    $className = strtolower($classReflection->getShortName());

    // check if module or core, if module, save module name
    $moduleName = 'core';
    $namespaceName = $classReflection->getNamespaceName();
    if (strpos($namespaceName, 'OB\\Modules\\') === 0) {
        $moduleName = strtolower(str_replace('\\Cron', '', str_replace('OB\\Modules\\', '', $namespaceName)));
    }

    // get our last run time
    $db->where('name', 'cron-' . $moduleName . '-' . $className);
    $lastRun = $db->get_one('settings');

    // if no last run time, set nextRun to zero
    if (!$lastRun) {
        $nextRun = 0;
    } else {
        $nextRun = $lastRun['value'] + $job->interval();
    }
    $jobs_to_run[$index] = [
        'nextRun' => $nextRun,
        'className' => $className,
        'moduleName' => $moduleName
    ];
}

$run_jobs = function () use ($jobs, &$jobs_to_run, $db, $lock) {
    // loop through jobs and check interval against last run. run if needed.
    foreach ($jobs_to_run as $index => $job_to_run) {
        // if not time to run, skip
        if ($job_to_run['nextRun'] > time()) {
            continue;
        }

        // aquire lock (or exit)
        $lock_aquired = $lock->acquire();
        if (!$lock_aquired) {
            echo 'Unable to get cron lock. Is another monitor or job already running?' . PHP_EOL;
            exit(1);
        }

        // get the job
        $job = $jobs[$index];

        // run job
        $status = $job->run();

        // if successful, update last run time for this job and main, as well as update our next run time locally
        if ($status) {
            // update last run time for this job
            if ($job_to_run['nextRun'] > 0) {
                // has previous run, update time.
                $db->where('name', 'cron-' . $job_to_run['moduleName'] . '-' . $job_to_run['className']);
                $db->update('settings', ['value' => time()]);
            } else {
                // first time running, insert time.
                $db->insert('settings', ['name' => 'cron-' . $job_to_run['moduleName'] . '-' . $job_to_run['className'], 'value' => time()]);
            }

            // update next run time in our local variable
            $jobs_to_run[$index]['nextRun'] = time() + $job->interval();

            // update our main cron_last_run time
            $db->where('name', 'cron_last_run');
            $cron_last_run = $db->get_one('settings');
            if (!$cron_last_run) {
                $db->insert('settings', ['name' => 'cron_last_run', 'value' => time()]);
            } else {
                $db->where('name', 'cron_last_run');
                $db->update('settings', ['value' => time()]);
            }

            // update $jobs_to_run with next time
            $jobs_to_run[$index]['nextRun'] = time() + $job->interval();
        }

        // release our lock
        $lock->release();
    }
};

if ($subcommand === 'monitor') {
    // monitor mode
    echo 'cron monitor started' . PHP_EOL;
    while (true) {
        $run_jobs();
        sleep(1);
    }
} else {
    $run_jobs();
}
