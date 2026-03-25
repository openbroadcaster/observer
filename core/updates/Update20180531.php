<?php

namespace OpenBroadcaster\Updates;

use OpenBroadcaster\Base\Update;

class Update20180531 extends Update
{
    public function items()
    {
        $updates = [];
        $updates[] = 'Store genre default for new media.';
        return $updates;
    }

    public function run()
    {
        $this->db->query('ALTER TABLE `media_genres` ADD `is_default` BOOLEAN NOT NULL DEFAULT FALSE AFTER `media_category_id`');
        return true;
    }
}
