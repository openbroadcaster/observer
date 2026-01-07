<?php

class OBUpdate20230315 extends OBUpdate
{
    public function items()
    {
        $updates = [];
        $updates[] = 'Add v2 permissions column to users appkey table.';

        return $updates;
    }

    public function run()
    {
        $this->db->query('ALTER TABLE `users_appkeys` ADD COLUMN `permissions_v2` TEXT NOT NULL AFTER `permissions`;');
        if ($this->db->error()) {
            return false;
        }

        return true;
    }
}
