<?php

class OBUpdate20200817 extends OBUpdate
{
    public function items()
    {
        $updates   = array();
        $updates[] = "CASCADE updates to schedules, schedules_media_cache, schedules_permissions, schedules_recurring, and their sub-tables. Clean up tables first.";
        $updates[] = "CASCADE updates to translations tables. Clean up tables first.";
        $updates[] = "CASCADE updates to users tables. Clean up tables first.";
        return $updates;
    }

    public function run()
    {
        // CASCADE updates to schedules, schedules_media_cache, schedules_permissions, schedules_recurring, and their sub-tables. Clean up tables first.

        // `schedules`
        $this->db->query('ALTER TABLE `schedules` ADD INDEX(`device_id`);');
        $this->db->query('ALTER TABLE `schedules` ADD INDEX(`user_id`);');
        $this->db->query('ALTER TABLE `schedules` CHANGE `user_id` `user_id` INT(10) UNSIGNED NULL;');
        $this->db->query('DELETE FROM `schedules` WHERE `device_id` NOT IN (SELECT `id` FROM `devices`);');
        $this->db->query('UPDATE `schedules` SET `user_id` = NULL WHERE `user_id` NOT IN (SELECT `id` FROM `users`);');
        $this->db->query('ALTER TABLE `schedules` ADD FOREIGN KEY (`device_id`) REFERENCES `devices`(`id`) ON DELETE CASCADE ON UPDATE CASCADE;');
        $this->db->query('ALTER TABLE `schedules` ADD FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL ON UPDATE CASCADE;');

        // `schedules_media_cache`
        $this->db->query('DELETE FROM `schedules_media_cache` WHERE `schedule_id` NOT IN (SELECT `id` FROM `schedules`);');
        $this->db->query('DELETE FROM `schedules_media_cache` WHERE `device_id` NOT IN (SELECT `id` FROM `devices`);');
        $this->db->query('ALTER TABLE `schedules_media_cache` ADD FOREIGN KEY (`schedule_id`) REFERENCES `schedules`(`id`) ON DELETE CASCADE ON UPDATE CASCADE;');
        $this->db->query('ALTER TABLE `schedules_media_cache` ADD FOREIGN KEY (`device_id`) REFERENCES `devices`(`id`) ON DELETE CASCADE ON UPDATE CASCADE;');

        // `schedules_permissions`
        $this->db->query('ALTER TABLE `schedules_permissions` ADD INDEX(`device_id`);');
        $this->db->query('ALTER TABLE `schedules_permissions` ADD INDEX(`user_id`);');
        $this->db->query('DELETE FROM `schedules_permissions` WHERE `device_id` NOT IN (SELECT `id` FROM `devices`);');
        $this->db->query('DELETE FROM `schedules_permissions` WHERE `user_id` NOT IN (SELECT `id` FROM `users`);');
        $this->db->query('ALTER TABLE `schedules_permissions` ADD FOREIGN KEY (`device_id`) REFERENCES `devices`(`id`) ON DELETE CASCADE ON UPDATE CASCADE;');
        $this->db->query('ALTER TABLE `schedules_permissions` ADD FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE ON UPDATE CASCADE;');

        // `schedules_permissions_recurring`
        $this->db->query('ALTER TABLE `schedules_permissions_recurring` ADD INDEX(`device_id`);');
        $this->db->query('ALTER TABLE `schedules_permissions_recurring` ADD INDEX(`user_id`);');
        $this->db->query('DELETE FROM `schedules_permissions_recurring` WHERE `device_id` NOT IN (SELECT `id` FROM `devices`);');
        $this->db->query('DELETE FROM `schedules_permissions_recurring` WHERE `user_id` NOT IN (SELECT `id` FROM `users`);');
        $this->db->query('ALTER TABLE `schedules_permissions_recurring` ADD FOREIGN KEY (`device_id`) REFERENCES `devices`(`id`) ON DELETE CASCADE ON UPDATE CASCADE;');
        $this->db->query('ALTER TABLE `schedules_permissions_recurring` ADD FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE ON UPDATE CASCADE;');

        // `schedules_permissions_recurring_expanded`
        $this->db->query('ALTER TABLE `schedules_permissions_recurring_expanded` ADD INDEX(`recurring_id`);');
        $this->db->query('DELETE FROM `schedules_permissions_recurring_expanded` WHERE `recurring_id` NOT IN (SELECT `id` FROM `schedules_permissions_recurring`);');
        $this->db->query('ALTER TABLE `schedules_permissions_recurring_expanded` ADD FOREIGN KEY (`recurring_id`) REFERENCES `schedules_permissions_recurring`(`id`) ON DELETE CASCADE ON UPDATE CASCADE;');

        // `schedules_recurring`
        $this->db->query('ALTER TABLE `schedules_recurring` ADD INDEX(`device_id`);');
        $this->db->query('ALTER TABLE `schedules_recurring` ADD INDEX(`user_id`);');
        $this->db->query('ALTER TABLE `schedules_recurring` CHANGE `user_id` `user_id` INT(10) UNSIGNED NULL;');
        $this->db->query('DELETE FROM `schedules_recurring` WHERE `device_id` NOT IN (SELECT `id` FROM `devices`);');
        $this->db->query('UPDATE `schedules_recurring` SET `user_id` = NULL WHERE `user_id` NOT IN (SELECT `id` FROM `users`);');
        $this->db->query('ALTER TABLE `schedules_recurring` ADD FOREIGN KEY (`device_id`) REFERENCES `devices`(`id`) ON DELETE CASCADE ON UPDATE CASCADE;');
        $this->db->query('ALTER TABLE `schedules_recurring` ADD FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL ON UPDATE CASCADE;');

        // `schedules_recurring_expanded`
        $this->db->query('ALTER TABLE `schedules_recurring_expanded` ADD INDEX(`recurring_id`);');
        $this->db->query('DELETE FROM `schedules_recurring_expanded` WHERE `recurring_id` NOT IN (SELECT `id` FROM `schedules_recurring`);');
        $this->db->query('ALTER TABLE `schedules_recurring_expanded` ADD FOREIGN KEY (`recurring_id`) REFERENCES `schedules_recurring`(`id`) ON DELETE CASCADE ON UPDATE CASCADE;');

        // CASCADE updates to translations tables. Clean up tables first.

        // `translations_values`
        $this->db->query('ALTER TABLE `translations_values` ADD INDEX(`language_id`);');
        $this->db->query('DELETE FROM `translations_values` WHERE `language_id` NOT IN (SELECT `id` FROM `translations_languages`);');
        $this->db->query('ALTER TABLE `translations_values` ADD FOREIGN KEY (`language_id`) REFERENCES `translations_languages`(`id`) ON DELETE CASCADE ON UPDATE CASCADE;');

        // CASCADE updates to users tables. Clean up tables first.

        // `users_appkeys`
        $this->db->query('DELETE FROM `users_appkeys` WHERE `user_id` NOT IN (SELECT `id` FROM `users`);');
        $this->db->query('ALTER TABLE `users_appkeys` ADD FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE ON UPDATE CASCADE;');

        // `users_permissions_to_groups` (plus structure change to `users_permissions`)
        $this->db->query('ALTER TABLE `users_permissions` CHANGE `id` `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT;');
        $this->db->query('ALTER TABLE `users_permissions_to_groups` CHANGE `id` `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT, CHANGE `permission_id` `permission_id` INT(10) UNSIGNED NOT NULL, CHANGE `group_id` `group_id` INT(10) UNSIGNED NOT NULL;');
        $this->db->query('DELETE FROM `users_permissions_to_groups` WHERE `permission_id` NOT IN (SELECT `id` FROM `users_permissions`);');
        $this->db->query('DELETE FROM `users_permissions_to_groups` WHERE `group_id` NOT IN (SELECT `id` FROM `users_groups`);');
        $this->db->query('ALTER TABLE `users_permissions_to_groups` ADD FOREIGN KEY (`permission_id`) REFERENCES `users_permissions`(`id`) ON DELETE CASCADE ON UPDATE CASCADE;');
        $this->db->query('ALTER TABLE `users_permissions_to_groups` ADD FOREIGN KEY (`group_id`) REFERENCES `users_groups`(`id`) ON DELETE CASCADE ON UPDATE CASCADE;');

        // `users_settings`
        $this->db->query('ALTER TABLE `users_settings` CHANGE `id` `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT, CHANGE `user_id` `user_id` INT(10) UNSIGNED NOT NULL;');
        $this->db->query('DELETE FROM `users_settings` WHERE `user_id` NOT IN (SELECT `id` FROM `users`);');
        $this->db->query('ALTER TABLE `users_settings` ADD FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE ON UPDATE CASCADE;');

        // `users_to_groups`
        $this->db->query('ALTER TABLE `users_to_groups` ADD INDEX(`group_id`);');
        $this->db->query('ALTER TABLE `users_to_groups` CHANGE `id` `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT, CHANGE `user_id` `user_id` INT(10) UNSIGNED NOT NULL, CHANGE `group_id` `group_id` INT(10) UNSIGNED NOT NULL;');
        $this->db->query('DELETE FROM `users_to_groups` WHERE `user_id` NOT IN (SELECT `id` FROM `users`);');
        $this->db->query('DELETE FROM `users_to_groups` WHERE `group_id` NOT IN (SELECT `id` FROM `users_groups`);');
        $this->db->query('ALTER TABLE `users_to_groups` ADD FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE ON UPDATE CASCADE;');
        $this->db->query('ALTER TABLE `users_to_groups` ADD FOREIGN KEY (`group_id`) REFERENCES `users_groups`(`id`) ON DELETE CASCADE ON UPDATE CASCADE;');

        return true;
    }
}
