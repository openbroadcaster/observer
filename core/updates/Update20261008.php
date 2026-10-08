<?php

namespace OpenBroadcaster\Updates;

use OpenBroadcaster\Base\Update;

class Update20261008 extends Update
{
    public function items()
    {
        $updates = [];
        $updates[] = 'Drop old schedules_liveassist_buttons_cache table if it exists.';
        $updates[] = 'Add shows_liveassist_buttons_cache table for caching resolved live assist button playlists.';
        return $updates;
    }

    public function run()
    {
        // cache only, safe to drop.
        $this->db->query('DROP TABLE IF EXISTS `schedules_liveassist_buttons_cache`;');
        if ($this->db->error()) {
            echo 'Failed to drop schedules_liveassist_buttons_cache table.';
            return false;
        }

        $this->db->query('DROP TABLE IF EXISTS `shows_liveassist_buttons_cache`;');
        if ($this->db->error()) {
            echo 'Failed to drop shows_liveassist_buttons_cache table.';
            return false;
        }

        $this->db->query("CREATE TABLE `shows_liveassist_buttons_cache` (
            `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
            `show_expanded_id` INT(10) UNSIGNED NOT NULL,
            `player_id` INT(10) UNSIGNED NOT NULL,
            `button_id` INT(10) UNSIGNED NOT NULL,
            `start` INT(10) UNSIGNED NOT NULL,
            `data` MEDIUMTEXT NOT NULL,
            `created` INT(10) UNSIGNED NOT NULL,
            PRIMARY KEY (`id`),
            UNIQUE KEY `player_show_start_button` (`player_id`, `show_expanded_id`, `start`, `button_id`),
            KEY `show_expanded_id` (`show_expanded_id`),
            KEY `button_id` (`button_id`),
            KEY `start` (`start`),
            FOREIGN KEY (`show_expanded_id`) REFERENCES `shows_expanded` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
            FOREIGN KEY (`player_id`) REFERENCES `players` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
            FOREIGN KEY (`button_id`) REFERENCES `playlists_liveassist_buttons` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
        if ($this->db->error()) {
            echo 'Failed to create shows_liveassist_buttons_cache table.';
            return false;
        }

        return true;
    }
}
