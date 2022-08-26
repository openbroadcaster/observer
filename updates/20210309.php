<?php

class OBUpdate20210309 extends OBUpdate
{
    public function items()
    {
        $updates   = array();
        $updates[] = 'Refactor database structure for timeslots and shows.';
        return $updates;
    }

    public function run()
    {
        $this->db->query('START TRANSACTION;');

        $this->db->query("CREATE TABLE IF NOT EXISTS `shows` (
      `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
      `player_id` int(10) UNSIGNED NOT NULL,
      `user_id` int(10) UNSIGNED DEFAULT NULL,
      `item_id` int(10) UNSIGNED NOT NULL,
      `item_type` enum('media','playlist','linein') NOT NULL,
      `start` datetime NOT NULL,
      `show_end` datetime NOT NULL,
      `mode` enum('once','daily','weekly','monthly','xdays','xweeks','xmonths') NOT NULL,
      `recurring_interval` smallint(5) UNSIGNED NOT NULL,
      `recurring_end` date NOT NULL,
      PRIMARY KEY (`id`),
      KEY `player_id` (`player_id`),
      KEY `user_id` (`user_id`)
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

        $this->db->query("ALTER TABLE `shows`
      ADD CONSTRAINT `shows_ibfk_1` FOREIGN KEY (`player_id`) REFERENCES `players` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
      ADD CONSTRAINT `shows_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;");

        $this->db->query("CREATE TABLE IF NOT EXISTS `shows_expanded` (
      `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
      `show_id` int(10) UNSIGNED NOT NULL,
      `start` datetime NOT NULL,
      `end` datetime NOT NULL,
      PRIMARY KEY (`id`),
      KEY `show_id` (`show_id`)
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

        $this->db->query("ALTER TABLE `shows_expanded`
      ADD CONSTRAINT `shows_expanded_ibfk_1` FOREIGN KEY (`show_id`) REFERENCES `shows` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;");

        $this->db->query("CREATE TABLE IF NOT EXISTS `shows_cache` (
      `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
      `show_expanded_id` int(10) UNSIGNED DEFAULT NULL,
      `player_id` int(10) UNSIGNED NOT NULL,
      `start` datetime NOT NULL,
      `duration` int(10) UNSIGNED NOT NULL,
      `data` mediumtext NOT NULL,
      PRIMARY KEY (`id`),
      KEY `player_id` (`player_id`),
      KEY `show_expanded_id` (`show_expanded_id`)
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

        $this->db->query("ALTER TABLE `shows_cache`
      ADD CONSTRAINT `shows_cache_ibfk_1` FOREIGN KEY (`player_id`) REFERENCES `players` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
      ADD CONSTRAINT `shows_cache_ibfk_2` FOREIGN KEY (`show_expanded_id`) REFERENCES `shows_expanded` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;");

        // RENAME TABLE `dev_ob2`.`timeslots_depr` TO `dev_ob2`.`timeslots_depr2`;
        $this->db->query("RENAME TABLE `timeslots` TO `timeslots_old`;");
        $this->db->query("RENAME TABLE `timeslots_recurring` TO `timeslots_recurring_old`;");
        $this->db->query("RENAME TABLE `timeslots_recurring_expanded` TO `timeslots_recurring_expanded_old`;");

        $this->db->query("CREATE TABLE IF NOT EXISTS `timeslots` (
      `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
      `player_id` int(10) UNSIGNED NOT NULL,
      `user_id` int(10) UNSIGNED NOT NULL,
      `start` datetime NOT NULL,
      `timeslot_end` datetime NOT NULL,
      `mode` enum('once','daily','weekly','monthly','xdays','xweeks','xmonths') NOT NULL,
      `recurring_interval` smallint(5) UNSIGNED NOT NULL,
      `recurring_end` date NOT NULL,
      `description` varchar(255) NOT NULL,
      PRIMARY KEY (`id`),
      KEY `player_id` (`player_id`),
      KEY `user_id` (`user_id`)
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

        $this->db->query("ALTER TABLE `timeslots`
      ADD CONSTRAINT `timeslots_ibfk_1` FOREIGN KEY (`player_id`) REFERENCES `players` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
      ADD CONSTRAINT `timeslots_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;");

        $this->db->query("CREATE TABLE IF NOT EXISTS `timeslots_expanded` (
      `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
      `timeslot_id` int(10) UNSIGNED NOT NULL,
      `start` datetime NOT NULL,
      `end` datetime NOT NULL,
      PRIMARY KEY (`id`),
      KEY `timeslot_id` (`timeslot_id`)
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

        $this->db->query("ALTER TABLE `timeslots_expanded`
      ADD CONSTRAINT `timeslots_expanded_ibfk_1` FOREIGN KEY (`timeslot_id`) REFERENCES `timeslots` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;");

        $this->db->query('COMMIT;');
        if ($this->db->error()) {
            return false;
        }

        return true;
    }
}
