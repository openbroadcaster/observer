<?php

namespace OpenBroadcaster\Updates;

use OpenBroadcaster\Base\Update;

class Update20130610 extends Update
{
    public function items()
    {
        $updates = [];
        $updates[] = 'Update media_searches table, should be using MyISAM rather than InnoDB.';
        return $updates;
    }

    public function run()
    {
        $this->db->query('ALTER TABLE `media_searches` ENGINE = MYISAM');
        return true;
    }
}
