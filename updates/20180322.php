<?php

class OBUpdate20180322 extends OBUpdate
{
    public function items()
    {
        $updates = array();
        $updates[] = 'Custom media metadata management.';
        return $updates;
    }

    public function run()
    {
        $this->db->query("CREATE TABLE IF NOT EXISTS `media_metadata` (
  `media_id` int(10) UNSIGNED NOT NULL,
  PRIMARY KEY (`media_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

        $this->db->query("CREATE TABLE IF NOT EXISTS `media_metadata_columns` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(32) NOT NULL,
  `description` varchar(255) NOT NULL,
  `type` varchar(255) NOT NULL,
  `order_id` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `settings` text,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

        return true;
    }
}
