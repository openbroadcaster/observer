<?php

class OBUpdate20200220 extends OBUpdate
{
  public function items() {
    $updates   = array();
    $updates[] = 'Support custom playlist item types.';

    return $updates;
  }

  public function run() {

    $this->db->query('
CREATE TABLE IF NOT EXISTS `playlists_items_types` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `description` varchar(255) NOT NULL,
  `duration` int(255) UNSIGNED NOT NULL,
  `callback_model` varchar(255) NOT NULL,
  `callback_method` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ');
    $this->db->query('ALTER TABLE `playlists_items` CHANGE `item_type` `item_type` ENUM(\'media\',\'dynamic\',\'station_id\',\'breakpoint\',\'custom\') CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL DEFAULT \'media\';');
    return true;
  }
}
