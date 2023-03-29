<?php

class OBUpdate20230328 extends OBUpdate
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
