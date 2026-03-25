<?php

namespace OpenBroadcaster\Updates;

use OpenBroadcaster\Base\Update;

class Update20230328 extends Update
{
    public function items()
    {
        $updates = [];
        $updates[] = 'Allow unicode setting names and values in the database.';

        return $updates;
    }

    public function run()
    {
        $this->db->query('ALTER TABLE `settings` MODIFY `name` VARCHAR(255) COLLATE utf8mb4_unicode_ci, MODIFY `value` TEXT COLLATE utf8mb4_general_ci;');
        if ($this->db->error()) {
            return false;
        }

        return true;
    }
}
