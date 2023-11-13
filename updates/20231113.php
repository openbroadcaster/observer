<?php

class OBUpdate20231113 extends OBUpdate
{
    public function items()
    {
        $updates = [];

        $updates[] = "Add longitude/latitude support to players database table.";

        return $updates;
    }

    public function run()
    {
        $this->db->query('ALTER TABLE `players` ADD COLUMN `longitude` DECIMAL(8,5) DEFAULT NULL;');
        if ($this->db->error()) {
            return false;
        }

        $this->db->query('ALTER TABLE `players` ADD COLUMN `latitude` DECIMAL(8,5) DEFAULT NULL;');
        if ($this->db->error()) {
            return false;
        }

        return true;
    }
}