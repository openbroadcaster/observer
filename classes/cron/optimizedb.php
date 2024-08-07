<?php

namespace OB\Classes\Cron;

use OB\Classes\Base\Cron;

class OptimizeDB extends Cron
{
    public function interval(): int
    {
        // daily
        return 86400;
    }

    public function run(): bool
    {
        $db = \OBFDB::get_instance();

        $db->query('show table status');
        $tables = $db->assoc_list();
        if (!empty($tables)) {
            foreach ($tables as $nfo) {
                if (empty($nfo['Data_free'])) {
                    continue;
                }
                $fragmentation = $nfo['Data_free'] * 100 / $nfo['Data_length'];

                if ($fragmentation > 10) {
                    $db->query('OPTIMIZE TABLE `' . $nfo['Name'] . '`');
                }
            }
        }

        return true;
    }
}
