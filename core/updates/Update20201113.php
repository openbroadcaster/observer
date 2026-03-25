<?php

namespace OpenBroadcaster\Updates;

use OpenBroadcaster\Base\Update;

class Update20201113 extends Update
{
    public function items()
    {
        $updates   = [];
        $updates[] = "Add AppKey permissions.";
        return $updates;
    }

    public function run()
    {
        $this->db->query("ALTER TABLE `users_appkeys` ADD `permissions` TEXT NOT NULL DEFAULT '' AFTER `key`;");
        if ($this->db->error()) {
            return false;
        }
        return true;
    }
}
