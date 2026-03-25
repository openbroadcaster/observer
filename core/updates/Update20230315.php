<?php

namespace OpenBroadcaster\Updates;

use OpenBroadcaster\Base\Update;

class Update20230315 extends Update
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
