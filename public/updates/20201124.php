<?php

class OBUpdate20201124 extends OBUpdate
{
    public function items()
    {
        $updates   = [];
        $updates[] = "Update users_permissions with renamed devices (now players).";
        $updates[] = "Rename devices tables to players.";
        $updates[] = "Update device columns to player in all tables.";
        $updates[] = "Update permission descriptions and categories.";
        return $updates;
    }

    public function run()
    {
        // Update users_permissions with renamed devices (now players).
        $this->db->query("UPDATE `users_permissions` SET `name` = 'manage_players' WHERE `users_permissions`.`name` = 'manage_devices';");
        $this->db->query("UPDATE `users_permissions` SET `name` = 'view_player_monitor' WHERE `users_permissions`.`name` = 'view_device_monitor';");

        // Rename devices tables to players.
        $this->db->query("ALTER TABLE `devices` RENAME TO `players`;");
        $this->db->query("ALTER TABLE `devices_station_ids` RENAME TO `players_station_ids`;");

        // Update device columns to player in all tables.
        $this->db->query("ALTER TABLE `players` CHANGE `parent_device_id` `parent_player_id` INT(10) UNSIGNED NULL DEFAULT NULL;");
        $this->db->query("ALTER TABLE `players_station_ids` CHANGE `device_id` `player_id` INT(10) UNSIGNED NOT NULL;");
        $this->db->query("ALTER TABLE `emergencies` CHANGE `device_id` `player_id` INT(10) UNSIGNED NOT NULL;");
        $this->db->query("ALTER TABLE `notices` CHANGE `device_id` `player_id` INT(10) UNSIGNED NULL DEFAULT NULL;");
        $this->db->query("ALTER TABLE `playlog` CHANGE `device_id` `player_id` INT(10) UNSIGNED NOT NULL;");
        $this->db->query("ALTER TABLE `schedules` CHANGE `device_id` `player_id` INT(10) UNSIGNED NOT NULL;");
        $this->db->query("ALTER TABLE `schedules_media_cache` CHANGE `device_id` `player_id` INT(10) UNSIGNED NULL DEFAULT NULL;");
        $this->db->query("ALTER TABLE `schedules_recurring` CHANGE `device_id` `player_id` INT(10) UNSIGNED NOT NULL;");
        $this->db->query("ALTER TABLE `timeslots` CHANGE `device_id` `player_id` INT(10) UNSIGNED NOT NULL;");
        $this->db->query("ALTER TABLE `timeslots_recurring` CHANGE `device_id` `player_id` INT(10) UNSIGNED NOT NULL;");

        // Update permission descriptions and categories.
        $this->db->query("UPDATE `users_permissions` SET `category` = 'player' WHERE `users_permissions`.`name` = 'manage_timeslots';");
        $this->db->query("UPDATE `users_permissions` SET `category` = 'player' WHERE `users_permissions`.`name` = 'manage_emergency_broadcasts';");
        $this->db->query("UPDATE `users_permissions` SET `category` = 'player' WHERE `users_permissions`.`name` = 'view_player_monitor';");
        $this->db->query("UPDATE `users_permissions` SET `description` = 'manage players' WHERE `users_permissions`.`name` = 'manage_players';");
        $this->db->query("UPDATE `users_permissions` SET `description` = 'view player logs' WHERE `users_permissions`.`name` = 'view_player_monitor';");

        return true;
    }
}
