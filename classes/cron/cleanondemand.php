<?php

namespace OB\Classes\Cron;

use OB\Classes\Base\Cron;

class CleanOnDemand extends Cron
{
    public function interval(): int
    {
        return 3600;
    }

    public function run(): bool
    {
        echo 'Cleaning on-demand cache items...' . PHP_EOL;

        $ondemandDir = OB_CACHE . '/ondemand/';
        if (! is_dir($ondemandDir)) {
            echo 'No on-demand cache folder, so no items to clean.' . PHP_EOL;
            return true;
        }

        $streamDirs = scandir($ondemandDir);
        $total = count($streamDirs) - 2;
        $count = 0;
        foreach ($streamDirs as $streamDir) {
            if ($streamDir === '.' || $streamDir === '..') {
                continue;
            }

            $streamDirPath = $ondemandDir . $streamDir;
            if (! is_dir($streamDirPath)) {
                continue;
            }

            if (file_exists($streamDirPath . '/last_access_time')) {
                // Remove cache items older than 24 hours.
                $lastAccessTime = file_get_contents($streamDirPath . '/last_access_time');
                if ($lastAccessTime + (60 * 60 * 24) < time()) {
                    array_map('unlink', glob($streamDirPath . '/*'));
                    rmdir($streamDirPath);
                    $count++;
                }
            } else {
                // Last access time file might not exist if cache was generated but never accessed,
                // so create it now and set the last access time to the current time. It'll be cleaned
                // 24 hours from now.
                file_put_contents($streamDirPath . '/last_access_time', time());
            }
        }

        echo 'On-demand cache items cleaned. Total items: ' . $total . '. ' . 'Items removed: ' . $count . PHP_EOL;

        return true;
    }
}
