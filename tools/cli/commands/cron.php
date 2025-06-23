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

            // disabled "running" message since we have things running every second (would be good to have a debug mode)
            // echo "Running job '{$job['module']}/{$job['name']}'..." . PHP_EOL;
            exec($argv[0] . ' cron run ' . $job['module'] . ' ' . $job['name'] . ' >> ' . OB_CRON_LOG . ' &');
        }

        sleep(1);
    }
}
