<?php

class OBUpdate20190328 extends OBUpdate
{
  public function items()
  {
    $updates = array();
    $updates[] = 'Media and playlists advanced permissions.';
    return $updates;
  }

  public function run()
  {
    // these were not previously set to unsigned. fix so that foreign key relationships with new tables (below) work.
    $this->db->query("ALTER TABLE `media` CHANGE `id` `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT;");
    $this->db->query("ALTER TABLE `playlists` CHANGE `id` `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT;");
    $this->db->query("ALTER TABLE `users_groups` CHANGE `id` `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT;");
    $this->db->query("ALTER TABLE `users` CHANGE `id` `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT;");

    // media group permissions table
    $this->db->query("
CREATE TABLE IF NOT EXISTS `media_permissions_groups` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `media_id` int(10) UNSIGNED NOT NULL,
  `group_id` int(10) UNSIGNED NOT NULL,
  PRIMARY KEY (`id`),
  KEY `media_id` (`media_id`),
  KEY `group_id` (`group_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");

    $this->db->query("
ALTER TABLE `media_permissions_groups`
  ADD CONSTRAINT `media_permissions_groups_ibfk_1` FOREIGN KEY (`media_id`) REFERENCES `media` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `media_permissions_groups_ibfk_2` FOREIGN KEY (`group_id`) REFERENCES `users_groups` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
    ");

    // media user permissions table
    $this->db->query("
CREATE TABLE IF NOT EXISTS `media_permissions_users` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `media_id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  PRIMARY KEY (`id`),
  KEY `media_id` (`media_id`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");

    $this->db->query("
ALTER TABLE `media_permissions_users`
  ADD CONSTRAINT `media_permissions_users_ibfk_1` FOREIGN KEY (`media_id`) REFERENCES `media` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `media_permissions_users_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
    ");

    // playlists group permissions table
    $this->db->query("
CREATE TABLE IF NOT EXISTS `playlists_permissions_groups` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `playlist_id` int(10) UNSIGNED NOT NULL,
  `group_id` int(10) UNSIGNED NOT NULL,
  PRIMARY KEY (`id`),
  KEY `playlist_id` (`playlist_id`),
  KEY `group_id` (`group_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");

    $this->db->query("
ALTER TABLE `playlists_permissions_groups`
  ADD CONSTRAINT `playlists_permissions_groups_ibfk_1` FOREIGN KEY (`playlist_id`) REFERENCES `playlists` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `playlists_permissions_groups_ibfk_2` FOREIGN KEY (`group_id`) REFERENCES `users_groups` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
    ");

    // playlists user permissions table
    $this->db->query("
CREATE TABLE IF NOT EXISTS `playlists_permissions_users` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `playlist_id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  PRIMARY KEY (`id`),
  KEY `playlist_id` (`playlist_id`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");

    $this->db->query("
ALTER TABLE `playlists_permissions_users`
  ADD CONSTRAINT `playlists_permissions_users_ibfk_1` FOREIGN KEY (`playlist_id`) REFERENCES `playlists` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `playlists_permissions_users_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
    ");

    // permission to allow setting user/group permissions on media
    $this->db->insert('users_permissions', [
      'name'=>'media_advanced_permissions',
      'description'=>'advanced permissions',
      'category'=>'media'
    ]);

    // permission to allow setting user/group permissions on playlists
    $this->db->insert('users_permissions', [
      'name'=>'playlists_advanced_permissions',
      'description'=>'advanced permissions',
      'category'=>'playlists'
    ]);

    return true;
  }
}
