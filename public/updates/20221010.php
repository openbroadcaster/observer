<?php

class OBUpdate20221010 extends OBUpdate
{
    public function items()
    {
        $updates   = [];
        $updates[] = 'Rename old emergencies tables.';
        $updates[] = 'Rename emergency broadcasts permission.';
        $updates[] = 'Rename parent emergency field in players table.';
        return $updates;
    }

    public function run()
    {
        $this->db->query('ALTER TABLE `emergencies` RENAME `alerts`;');
        $this->db->query('UPDATE `users_permissions` SET `name` = "manage_alerts", `description` = "create, edit, and delete alerts" WHERE `name` = "manage_emergency_broadcasts";');
        $this->db->query('ALTER TABLE `players` CHANGE `use_parent_emergency` `use_parent_alert` TINYINT(1);');

        return true;
    }
}
