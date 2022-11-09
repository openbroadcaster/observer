<?php

class OBUpdate20220704 extends OBUpdate
{
    public function items()
    {
        $updates   = array();
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
