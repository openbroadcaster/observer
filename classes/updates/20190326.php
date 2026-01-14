<?php

namespace OpenBroadcaster\Updates;

use OpenBroadcaster\Base\Update;

class OBUpdate20190326 extends Update
{
    public function items()
    {
        $updates = [];
        $updates[] = 'Support longer setting values (text column).';
        return $updates;
    }

    public function run()
    {
        $this->db->query("ALTER TABLE `settings` CHANGE `value` `value` TEXT CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL;");
        return true;
    }
}
