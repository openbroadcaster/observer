<?php

class OBUpdate20180403 extends OBUpdate
{
    public function items()
    {
        $updates = array();
        $updates[] = 'Store metadata of deleted media in case needed for reporting.';
        return $updates;
    }

    public function run()
    {
        $this->db->query("CREATE TABLE IF NOT EXISTS `media_deleted` (
  `media_id` int(10) UNSIGNED NOT NULL,
  `metadata` mediumtext NOT NULL,
  PRIMARY KEY (`media_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

        return true;
    }
}
