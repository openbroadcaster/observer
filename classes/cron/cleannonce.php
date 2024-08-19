<?php

namespace OB\Classes\Cron;

use OB\Classes\Base\Cron;

class CleanNonce extends Cron
{
    public function interval(): int
    {
        return 300;
    }

    public function run(): bool
    {
        $db = \OBFDB::get_instance();

        // remove from users_nonces where created is older than 1 hour
        $db->query('DELETE FROM users_nonces where created < "' . date('Y-m-d H:i:s', strtotime('-1 hour')) . '"');

        return true;
    }
}
