<?php

class OBUpdate20200814 extends OBUpdate
{
    public function items()
    {
        $updates   = [];
        $updates[] = "CASCADE updates to playlists. Allow NULLS in owner_id when a user gets deleted. Clean up table first.";
        $updates[] = "CASCADE updates to playlists_items. Note that media items will NOT cascade since item_id can refer to other types as well. Clean up table first.";
        $updates[] = "CASCADE updates to playlist_liveassist_buttons. Clean up table first.";

        return $updates;
    }

    public function run()
    {
        // CASCADE updates to playlists. Allow NULLS in owner_id when a user gets deleted. Clean up table first.
        $this->db->query("ALTER TABLE `playlists` CHANGE `owner_id` `owner_id` INT(10) UNSIGNED NULL DEFAULT '0';");
        $this->db->query('UPDATE `playlists` SET `owner_id` = NULL WHERE `owner_id` NOT IN (SELECT `id` FROM `users`);');
        $this->db->query('ALTER TABLE `playlists` ADD FOREIGN KEY (`owner_id`) REFERENCES `users`(`id`) ON DELETE SET NULL ON UPDATE CASCADE;');

        // CASCADE updates to playlists_items. Note that media items will NOT cascade since item_id can refer to other types as well. Clean up table first.
        $this->db->query('ALTER TABLE `playlists_items` CHANGE `playlist_id` `playlist_id` INT(10) UNSIGNED NOT NULL;');
        $this->db->query('DELETE FROM `playlists_items` WHERE `playlist_id` NOT IN (SELECT `id` FROM `playlists`);');
        $this->db->query('ALTER TABLE `playlists_items` ADD FOREIGN KEY (`playlist_id`) REFERENCES `playlists`(`id`) ON DELETE CASCADE ON UPDATE CASCADE;');

        // CASCADE updates to playlist_liveassist_buttons buttons. Clean up table first.
        $this->db->query('ALTER TABLE `playlists_liveassist_buttons` ADD INDEX(`button_playlist_id`);');
        $this->db->query('DELETE FROM `playlists_liveassist_buttons` WHERE `playlist_id` NOT IN (SELECT `id` FROM `playlists`);');
        $this->db->query('DELETE FROM `playlists_liveassist_buttons` WHERE `button_playlist_id` NOT IN (SELECT `id` FROM `playlists`);');
        $this->db->query('ALTER TABLE `playlists_liveassist_buttons` ADD FOREIGN KEY (`playlist_id`) REFERENCES `playlists`(`id`) ON DELETE CASCADE ON UPDATE CASCADE;');
        $this->db->query('ALTER TABLE `playlists_liveassist_buttons` ADD FOREIGN KEY (`button_playlist_id`) REFERENCES `playlists`(`id`) ON DELETE CASCADE ON UPDATE CASCADE;');

        return true;
    }
}
