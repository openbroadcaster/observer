<?php

namespace OpenBroadcaster\Updates;

use OpenBroadcaster\Base\Update;

class Update20260219 extends Update
{
    public function items()
    {
        $updates   = [];
        $updates[] = "Create media_searches_shared table for sharing saved searches with users and groups.";
        return $updates;
    }

    public function run()
    {
        $this->db->query('CREATE TABLE IF NOT EXISTS `media_searches_shared` (
            `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
            `search_id` INT(10) UNSIGNED NOT NULL,
            `shared_by` INT(10) UNSIGNED NOT NULL,
            `shared_with_user_id` INT(10) UNSIGNED DEFAULT NULL,
            `shared_with_group_id` INT(10) UNSIGNED DEFAULT NULL,
            PRIMARY KEY (`id`),
            KEY `search_id` (`search_id`),
            KEY `shared_by` (`shared_by`),
            KEY `shared_with_user_id` (`shared_with_user_id`),
            KEY `shared_with_group_id` (`shared_with_group_id`),
            FOREIGN KEY (`search_id`) REFERENCES `media_searches`(`id`) ON DELETE CASCADE ON UPDATE CASCADE,
            FOREIGN KEY (`shared_by`) REFERENCES `users`(`id`) ON DELETE CASCADE ON UPDATE CASCADE,
            FOREIGN KEY (`shared_with_user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE ON UPDATE CASCADE,
            FOREIGN KEY (`shared_with_group_id`) REFERENCES `users_groups`(`id`) ON DELETE CASCADE ON UPDATE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8;');

        return true;
    }
}
