<?php

class OBUpdate20250407 extends OBUpdate
{
    public function items()
    {
        $updates = [];
        $updates[] = 'Add enabled setting to users permissions.';
        return $updates;
    }

    public function run()
    {
        $this->db->query(<<<SQL
            ALTER TABLE `users_permissions` ADD COLUMN `enabled` TINYINT(1) NOT NULL DEFAULT 1;
        SQL);

        if ($this->db->error()) {
            echo $this->db->error();
            return false;
        }

        return true;
    }
}
