<?php

namespace ob\tools\cli;

define('OB_CRON_LOG', '/tmp/cronlog');

global $argv;

if (!defined('OB_CLI')) {
    die('Command line access only.');
}

require_once('components.php');

$db = \OBFDB::get_instance();

// Check if a specific cron job was specified, and if the 'now' flag is set.
if (isset($argv[3]) && isset($argv[4])) {
    $module = $argv[3];
    $task = $argv[4];
    $forceRun = ($argv[5] ?? '') === 'now';

    // Get cron job class instance.
    require_once('classes/base/cron.php');
    if ($module === 'core') {
        if (! file_exists('classes/cron/' . $task . '.php')) {
            echo "Task '{$module}/{$task}' not found." . PHP_EOL;
            exit(1);
        }

        require_once('classes/cron/' . $task . '.php');
        $class = '\\OB\\Classes\\Cron\\' . $task;
    } else {
        if (! file_exists('modules/' . $module . '/cron/' . $task . '.php')) {
            echo "Task '{$module}/{$task}' not found." . PHP_EOL;
            exit(1);
        }

        require_once('modules/' . $module . '/cron/' . $task . '.php');
        $moduleNamespace = str_replace(' ', '', ucwords(str_replace('_', ' ', $module)));
        $class = '\\OB\\Modules\\' . $moduleNamespace . '\\Cron\\' . $task;
    }

    $job = new $class();

    // Check when last run if "now" isn't specified, and display an error if it's too
    // soon.
    $db->where('name', 'cron-' . $module . '-' . $task);
    $lastRun = $db->get_one('settings');

    if (! $forceRun && $lastRun && $lastRun['value'] + $job->interval() > time()) {
        echo "Task '{$module}/{$task}' was run too recently. Use 'now' to force run." . PHP_EOL;
        exit(1);
    }

    // Check if already a PID exists in DB for this task, quitting if so. Otherwise, add the
    // current process ID to the DB.
    $db->where('name', 'cronpid-' . $module . '-' . $task);
    $pid = $db->get_one('settings');
    if ($pid && $pid['value'] !== null && posix_getpgid($pid['value'])) {
        echo "Task '{$module}/{$task}' is already running." . PHP_EOL;
        exit(1);
    }

    if ($pid) {
        $db->where('name', 'cronpid-' . $module . '-' . $task);
        $db->update('settings', [
            'value' => getmypid()
        ]);
    } else {
        $db->insert('settings', [
            'name' => 'cronpid-' . $module . '-' . $task,
            'value' => getmypid()
        ]);
    }

    // Run job.
    echo "Running job..." . PHP_EOL;
    $status = $job->run();

    if ($status) {
        if ($lastRun) {
            $db->where('name', 'cron-' . $module . '-' . $task);
            $db->update('settings', [
                'value' => time()
            ]);
        } else {
            $db->insert('settings', [
                'name' => 'cron-' . $module . '-' . $task, 'value' => time()
            ]);
        }

        echo "Job ran successfully." . PHP_EOL;
    } else {
        echo "Failed to run job. Check individual cron job messages for more detailed information." . PHP_EOL;
    }

    // Removed PID from DB.
    $db->where('name', 'cronpid-' . $module . '-' . $task);
    $db->delete('settings');

    exit();
}

// NOTE: Code below is for running cron jobs in monitor mode or running all jobs once.

// Get all cron job module/name combinations.
$jobs = [];

foreach (glob('classes/cron/*.php') as $file) {
    require_once($file);
    $class = '\\OB\\Classes\Cron\\' . basename($file, '.php');
    $instance = new $class();

    $jobs[] = [
        'module'   => 'core',
        'name'     => basename($file, '.php'),
        'interval' => $instance->interval(),
    ];
}

foreach (glob('modules/*', GLOB_ONLYDIR) as $module) {
    foreach (glob($module . '/cron/*.php') as $file) {
        require_once($file);
        $moduleNamespace = str_replace(' ', '', ucwords(str_replace('_', ' ', basename($module))));
        $class = '\\OB\\Modules\\' . $moduleNamespace . '\\Cron\\' . basename($file, '.php');
        $instance = new $class();

        $jobs[] = [
            'module'   => basename($module),
            'name'     => basename($file, '.php'),
            'interval' => $instance->interval(),
        ];
    }
}

if ($subcommand === 'run') {
    foreach ($jobs as $job) {
        echo "Running job '{$job['module']}/{$job['name']}'..." . PHP_EOL;
        exec($argv[0] . ' cron run ' . $job['module'] . ' ' . $job['name'] . ' >> ' . OB_CRON_LOG . ' &');
    }
} elseif ($subcommand === 'monitor') {
    while (true) {
        foreach ($jobs as $job) {
            $db->where('name', 'cron-' . $job['module'] . '-' . $job['name']);
            $lastRun = $db->get_one('settings');

            if ($lastRun && $lastRun['value'] + $job['interval'] > time()) {
                continue;
            }

            echo "Running job '{$job['module']}/{$job['name']}'..." . PHP_EOL;
            exec($argv[0] . ' cron run ' . $job['module'] . ' ' . $job['name'] . ' >> ' . OB_CRON_LOG . ' &');
        }

        sleep(1);
    }
}

// lock is acquired right before running task, and released right after.
/*$lock = new \OBFLock('core-cron');

// require cron files
$jobs = [];
require_once('classes/base/cron.php');
foreach (glob('classes/cron/*.php') as $file) {
    require_once($file);
    $class = '\\OB\\Classes\Cron\\' . basename($file, '.php');
    $jobs[] = new $class();
}

foreach (glob('modules/*', GLOB_ONLYDIR) as $module) {
    // Only run cron jobs for installed modules.
    $moduleDir = basename($module);
    $db->where('directory', $moduleDir);
    $moduleInstalled = $db->get_one('modules');
    if (!$moduleInstalled) {
        continue;
    }

    // Require module cron files and add to jobs array.
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
        $matches = [];
        preg_match("/\/modules\/(.*)\/cron\//", $classReflection->getFileName(), $matches);
        $moduleName = $matches[1];
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
*/
