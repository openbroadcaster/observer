<?php

class OBUpdate20241122 extends OBUpdate
{
    public function items()
    {
        $updates = [];
        $updates[] = 'Add general properties column to media table.';
        return $updates;
    }

    public function run()
    {
        $this->db->query('
            ALTER TABLE `media` ADD COLUMN `properties` TEXT NULL DEFAULT NULL AFTER `dynamic_select`;
        ');

        return true;
    }
}
