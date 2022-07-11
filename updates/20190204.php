<?php

class OBUpdate20190204 extends OBUpdate
{
  public function items()
  {
    $updates = array();
    $updates[] = 'Media version system and metadata tag field type.';
    return $updates;
  }

  public function run()
  {
    $this->db->query("
CREATE TABLE IF NOT EXISTS `media_versions` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `media_id` int(10) UNSIGNED NOT NULL,
  `active` tinyint(1) DEFAULT '0',
  `created` int(10) UNSIGNED NOT NULL,
  `file_hash` varchar(255) CHARACTER SET latin1 NOT NULL,
  `format` varchar(12) CHARACTER SET latin1 NOT NULL,
  `duration` decimal(10,3) UNSIGNED DEFAULT NULL,
  `notes` text NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");

    $this->db->query("INSERT INTO `users_permissions` (`id`, `name`, `description`, `category`) VALUES (NULL, 'manage_media_versions', 'manage media versions', 'media');");

    return true;
  }
}
