<?php

class OBUpdate20210312 extends OBUpdate
{
    public function items()
    {
        $updates   = [];
        $updates[] = 'Clear and update show cache table.';
        return $updates;
    }

    public function run()
    {
        $this->db->query('truncate shows_cache;');
        $this->db->query('ALTER TABLE `shows_cache` ADD `created` INT UNSIGNED NOT NULL AFTER `data`;');
        $this->db->query('ALTER TABLE `shows_cache` CHANGE `start` `start` INT UNSIGNED NOT NULL;');
        return true;
    }
}
