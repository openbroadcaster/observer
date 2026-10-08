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
        $db = \OpenBroadcaster\Support\DB::get_instance();

        $cutoff = strtotime('-1 week');

        // remove cached schedule data for shows which stopped longer than 1 week ago (start is a unix timestamp)
        $db->query('DELETE FROM shows_cache WHERE start + duration < ' . (int) $cutoff);
        if ($db->error()) {
            return false;
        }

        // remove cached liveassist button data for shows which started longer than 1 week ago
        // (no duration stored; if a show runs longer than this, the cache is simply regenerated)
        $db->query('DELETE FROM shows_liveassist_buttons_cache WHERE start < ' . (int) $cutoff);
        if ($db->error()) {
            return false;
        }

        return true;
    }
}
