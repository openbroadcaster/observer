<?php

namespace OpenBroadcaster\Updates;

use OpenBroadcaster\Base\Update;

class Update20260421 extends Update
{
    public function items()
    {
        $updates   = [];
        $updates[] = "Update media metadata table to make name unique, having duplicates should be impossible on the DB layer.";
        return $updates;
    }

    public function run()
    {
        $this->db->query(<<<SQL
            ALTER TABLE media_metadata ADD UNIQUE (name);
        SQL);

        if ($this->db->error()) {
            echo $this->db->error() . PHP_EOL;
            return false;
        }

        return true;
    }
}