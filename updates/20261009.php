<?php

class OBUpdate20261009 extends OBUpdate
{
    public function items()
    {
        $updates = [];
        $updates[] = 'Add (player_id, start) index to shows_cache for faster schedule cache lookups.';
        return $updates;
    }

    public function run()
    {
        $this->db->query('ALTER TABLE `shows_cache` ADD INDEX `player_id_start` (`player_id`, `start`);');
        if ($this->db->error()) {
            echo 'Failed to add index to shows_cache table.';
            return false;
        }

        return true;
    }
}
