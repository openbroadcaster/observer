<?php

namespace OpenBroadcaster\Cron;

use OpenBroadcaster\Base\Cron;

class CleanShowsCache extends Cron
{
    public function interval(): int
    {
        return 300;
    }

    public function run(): bool
    {
        $db = \OBFDB::get_instance();

        // remove cached schedule data for shows which stopped longer than 1 week ago (+/- some variablity due to timezones)
        $db->query('DELETE FROM shows_cache where DATE_ADD(start, INTERVAL duration SECOND) < "' . date('Y-m-d H:i:s', strtotime('-1 week')) . '"');

        return true;
    }
}
