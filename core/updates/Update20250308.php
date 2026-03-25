<?php

namespace OpenBroadcaster\Updates;

use OpenBroadcaster\Base\Update;

class Update20250308 extends Update
{
    public function items()
    {
        $updates = [];
        $updates[] = 'Add voicetrack playlist item type.';
        return $updates;
    }

    public function run()
    {
        $this->db->query(<<<SQL
            ALTER TABLE `playlists_items` CHANGE COLUMN `item_type` `item_type` ENUM('media','dynamic','station_id','breakpoint','custom','voicetrack') NOT NULL default 'media';
        SQL);

        return true;
    }
}
