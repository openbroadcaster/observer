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

        // remove expired tokens
        $db->query('DELETE FROM users_nonces WHERE DATE_ADD(created, INTERVAL expiry SECOND) < NOW()');

        return true;
    }
}
