<?php

class OBUpdate20210118 extends OBUpdate
{
  public function items() {
    $updates   = array();
    $updates[] = "Dayparting (dynamic selection restrictions) table.";
    return $updates;
  }

  public function run() {

    $this->db->query('
      CREATE TABLE IF NOT EXISTS `dayparting` (
        `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
        `description` varchar(255) CHARACTER SET utf8 NOT NULL DEFAULT \'\',
        `start_doy` smallint(3) UNSIGNED DEFAULT NULL,
        `end_doy` smallint(3) UNSIGNED DEFAULT NULL,
        `start_time` time DEFAULT NULL,
        `end_time` time DEFAULT NULL,
        `dow` set(\'Mon\',\'Tue\',\'Wed\',\'Thu\',\'Fri\',\'Sat\',\'Sun\') CHARACTER SET utf8 DEFAULT NULL,
        `filters` text CHARACTER SET utf8 NOT NULL,
        PRIMARY KEY (`id`)
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
    ');

    if ($this->db->error()) return false;
    return true;
  }
}
