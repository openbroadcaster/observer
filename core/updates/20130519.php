<?php

namespace OpenBroadcaster\Updates;

use OpenBroadcaster\Base\Update;

class Update20130519 extends Update
{
    public function items()
    {
        $updates = [];
        $updates[] = 'Update devices table to support storing of remote version.';
        return $updates;
    }

    public function run()
    {
        $this->db->query('ALTER TABLE  `devices` ADD  `version` VARCHAR( 255 ) NOT NULL DEFAULT  \'\' AFTER  `last_connect_media`');
        return true;
    }
}
