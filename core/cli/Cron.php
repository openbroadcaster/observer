<?php

namespace OpenBroadcaster\CLI;

use OpenBroadcaster\Base\CLI;

class Cron extends CLI
{
    private string $obCronLog;
    protected array|string $help = [
        ['run', 'run scheduled tasks once'],
        ['run <module> <task> [now]', 'run scheduled task for module'],
        ['monitor', 'monitor and run cron tasks as needed'],
    ];

    public function run(array $args): bool
    {
        $this->obCronLog = '/tmp/ob-cronlog-' . md5(OB_LOCAL);

        // Check if a specific cron job was specified, and if the 'now' flag is set.
        if ($args[0] === 'run' && isset($args[1]) && isset($args[2])) {
            $module = $args[1];
            $task = $args[2];
            $forceRun = ($args[3] ?? '') === 'now';

            // Get cron job class instance.
            if ($module === 'core') {
                $class = '\\OpenBroadcaster\\Cron\\' . $task;
            } else {
                $moduleNamespace = str_replace(' ', '', ucwords(str_replace('_', ' ', $module)));
                $class = '\\OpenBroadcaster\\Modules\\' . $moduleNamespace . '\\Cron\\' . $task;
            }

            if (! class_exists($class)) {
                echo "Task '{$module}/{$task}' not found." . PHP_EOL;
                return false;
            }

            $job = new $class();

            // Check when last run if "now" isn't specified, and display an error if it's too
            // soon.
            $this->db->where('name', 'cron-' . $module . '-' . $task);
            $lastRun = $this->db->get_one('settings');

            if (! $forceRun && $lastRun && $lastRun['value'] + $job->interval() > time()) {
                echo "Task '{$module}/{$task}' was run too recently. Use 'now' to force run." . PHP_EOL;
                return false;
            }

            // Check if already a PID exists in DB for this task, quitting if so. Otherwise, add the
            // current process ID to the DB.
            $this->db->where('name', 'cronpid-' . $module . '-' . $task);
            $pid = $this->db->get_one('settings');
            if ($pid && $pid['value'] !== null && posix_getpgid($pid['value'])) {
                echo "Task '{$module}/{$task}' is already running." . PHP_EOL;
                return false;
            }

            if ($pid) {
                $this->db->where('name', 'cronpid-' . $module . '-' . $task);
                $this->db->update('settings', [
                    'value' => getmypid()
                ]);
            } else {
                $this->db->insert('settings', [
                    'name' => 'cronpid-' . $module . '-' . $task,
                    'value' => getmypid()
                ]);
            }

            // Run job.
            echo "Running job '{$module}/{$task}'..." . PHP_EOL;
            $status = $job->run();
            if ($status) {
                if ($lastRun) {
                    $this->db->where('name', 'cron-' . $module . '-' . $task);
                    $this->db->update('settings', [
                        'value' => time()
                    ]);
                } else {
                    $this->db->insert('settings', [
                        'name' => 'cron-' . $module . '-' . $task, 'value' => time()
                    ]);
                }

                echo "Job ran successfully." . PHP_EOL;
            } else {
                echo "Failed to run job. Check individual cron job messages for more detailed information." . PHP_EOL;
            }

            // Removed PID from DB.
            $this->db->where('name', 'cronpid-' . $module . '-' . $task);
            $this->db->delete('settings');

            return true;
        }

        // NOTE: Code below is for running cron jobs in monitor mode or running all jobs once.

        // Get all cron job module/name combinations.
        $jobs = [];

        foreach (glob(OB_LOCAL . '/core/cron/*.php') as $file) {
            require_once($file);
            $class = '\\OpenBroadcaster\Cron\\' . basename($file, '.php');
            $instance = new $class();

            $jobs[] = [
                'module'   => 'core',
                'name'     => basename($file, '.php'),
                'interval' => $instance->interval(),
            ];
        }

        foreach (glob(OB_LOCAL . '/modules/*', GLOB_ONLYDIR) as $module) {
            foreach (glob($module . '/cron/*.php') as $file) {
                require_once($file);
                $moduleNamespace = str_replace(' ', '', ucwords(str_replace('_', ' ', basename($module))));
                $class = '\\OpenBroadcaster\\Modules\\' . $moduleNamespace . '\\Cron\\' . basename($file, '.php');
                $instance = new $class();

                $jobs[] = [
                    'module'   => basename($module),
                    'name'     => basename($file, '.php'),
                    'interval' => $instance->interval(),
                ];
            }
        }

        if (count($args) < 1) {
            (new OBCLI())->help();
            return false;
        }

        if ($args[0] === 'run') {
            foreach ($jobs as $job) {
                echo "Running job '{$job['module']}/{$job['name']}'..." . PHP_EOL;
                //exec(OB_LOCAL . '/cli/ob' . ' cron run ' . $job['module'] . ' ' . $job['name'] . ' >> ' . $this->obCronLog . ' &');
            }
        } elseif ($args[0] === 'monitor') {
            while (true) {
                foreach ($jobs as $job) {
                    $this->db->where('name', 'cron-' . $job['module'] . '-' . $job['name']);
                    $lastRun = $this->db->get_one('settings');
                    if ($lastRun && $lastRun['value'] + $job['interval'] > time()) {
                        continue;
                    }

                    // disabled "running" message since we have things running every second (would be good to have a debug mode)
                    // echo "Running job '{$job['module']}/{$job['name']}'..." . PHP_EOL;
                    // $output = '';
                    // exec(OB_LOCAL . '/cli/ob' . ' cron run ' . $job['module'] . ' ' . $job['name'], $output);
                    // echo implode(PHP_EOL, $output) . PHP_EOL;
                    exec(OB_LOCAL . '/cli/ob' . ' cron run ' . $job['module'] . ' ' . $job['name'] . ' >> ' . $this->obCronLog . ' &');
                }

                sleep(1);
            }
        } else {
            (new OBCLI())->help();
            return false;
        }

        return true;
    }
}