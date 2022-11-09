<?php

class OBUpdate20200818 extends OBUpdate
{
    public function items()
    {
        $updates   = array();
        $updates[] = "CASCADE updates to media tables (including genres, metadata*, permissions*, searches, versions). Clean up tables first.";
        return $updates;
    }

    public function run()
    {
        // CASCADE updates to media tables (including genres, metadata*, permissions*, searches, versions). Clean up tables first.

        // `media` (update other table structures too so it works)
        $this->db->query("ALTER TABLE `media` CHANGE `category_id` `category_id` INT(10) UNSIGNED NULL DEFAULT NULL, CHANGE `country_id` `country_id` INT(10) UNSIGNED NULL DEFAULT NULL, CHANGE `language_id` `language_id` INT(10) UNSIGNED NULL DEFAULT NULL, CHANGE `genre_id` `genre_id` INT(10) UNSIGNED NULL DEFAULT NULL, CHANGE `owner_id` `owner_id` INT(10) UNSIGNED NULL DEFAULT '0';");
        $this->db->query('ALTER TABLE `media_categories` CHANGE `id` `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT;');
        $this->db->query('ALTER TABLE `media_countries` CHANGE `id` `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT;');
        $this->db->query('ALTER TABLE `media_languages` CHANGE `id` `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT;');
        $this->db->query('ALTER TABLE `media_genres` CHANGE `id` `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT;');

        $this->db->query('UPDATE `media` SET `category_id` = NULL WHERE `category_id` NOT IN (SELECT `id` FROM `media_categories`);');
        $this->db->query('UPDATE `media` SET `country_id` = NULL WHERE `country_id` NOT IN (SELECT `id` FROM `media_countries`);');
        $this->db->query('UPDATE `media` SET `language_id` = NULL WHERE `language_id` NOT IN (SELECT `id` FROM `media_languages`);');
        $this->db->query('UPDATE `media` SET `genre_id` = NULL WHERE `genre_id` NOT IN (SELECT `id` FROM `media_genres`);');
        $this->db->query('UPDATE `media` SET `owner_id` = NULL WHERE `owner_id` NOT IN (SELECT `id` FROM `users`);');

        $this->db->query('ALTER TABLE `media` ADD FOREIGN KEY (`category_id`) REFERENCES `media_categories`(`id`) ON DELETE SET NULL ON UPDATE CASCADE;');
        $this->db->query('ALTER TABLE `media` ADD FOREIGN KEY (`country_id`) REFERENCES `media_countries`(`id`) ON DELETE SET NULL ON UPDATE CASCADE;');
        $this->db->query('ALTER TABLE `media` ADD FOREIGN KEY (`language_id`) REFERENCES `media_languages`(`id`) ON DELETE SET NULL ON UPDATE CASCADE;');
        $this->db->query('ALTER TABLE `media` ADD FOREIGN KEY (`genre_id`) REFERENCES `media_genres`(`id`) ON DELETE SET NULL ON UPDATE CASCADE;');
        $this->db->query('ALTER TABLE `media` ADD FOREIGN KEY (`owner_id`) REFERENCES `users`(`id`) ON DELETE SET NULL ON UPDATE CASCADE;');

        // `media_genres`
        $this->db->query('ALTER TABLE `media_genres` ADD INDEX(`media_category_id`);');
        $this->db->query('DELETE FROM `media_genres` WHERE `media_category_id` NOT IN (SELECT `id` FROM `media_categories`);');
        $this->db->query('ALTER TABLE `media_genres` ADD FOREIGN KEY (`media_category_id`) REFERENCES `media_categories`(`id`) ON DELETE CASCADE ON UPDATE CASCADE;');

        // `media_metadata`
        $this->db->query('DELETE FROM `media_metadata` WHERE `media_id` NOT IN (SELECT `id` FROM `media`);');
        $this->db->query('ALTER TABLE `media_metadata` ADD FOREIGN KEY (`media_id`) REFERENCES `media`(`id`) ON DELETE CASCADE ON UPDATE CASCADE;');

        // `media_searches`
        $this->db->query('DELETE FROM `media_searches` WHERE `user_id` NOT IN (SELECT `id` FROM `users`);');
        $this->db->query('ALTER TABLE `media_searches` ADD FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE ON UPDATE CASCADE;');

        // `media_versions`
        $this->db->query('ALTER TABLE `media_versions` ADD INDEX(`media_id`);');
        $this->db->query('DELETE FROM `media_versions` WHERE `media_id` NOT IN (SELECT `id` FROM `media`);');
        $this->db->query('ALTER TABLE `media_versions` ADD FOREIGN KEY (`media_id`) REFERENCES `media`(`id`) ON DELETE CASCADE ON UPDATE CASCADE;');

        return true;
    }
}
