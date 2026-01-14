<?php

namespace OpenBroadcaster\Classes\Updates;

use OpenBroadcaster\Classes\Base\Update;

class OBUpdate20220704 extends Update
{
    public function items()
    {
        $updates   = [];
        $updates[] = 'Rename old schedules tables, add underscore for deprecated/old tables.';
        return $updates;
    }

    public function run()
    {
        $this->db->query('ALTER TABLE `schedules` RENAME `_schedules`;');
        $this->db->query('ALTER TABLE `schedules_media_cache` RENAME `_schedules_media_cache`;');
        $this->db->query('ALTER TABLE `schedules_recurring` RENAME `_schedules_recurring`;');
        $this->db->query('ALTER TABLE `schedules_recurring_expanded` RENAME `_schedules_recurring_expanded`;');

        return true;
    }
}
