<?php

class OBUpdate20190901 extends OBUpdate
{
    public function items()
    {
        $updates   = array();
        $updates[] = 'Translation tables.';

        return $updates;
    }

    public function run()
    {
        $this->db->query('CREATE TABLE IF NOT EXISTS `translations_sources` (
      `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
      `string` text NOT NULL,
      PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;');

        $this->db->query('CREATE TABLE IF NOT EXISTS `translations_languages` (
      `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
      `name` varchar(255) NOT NULL,
      `code` varchar(50) NOT NULL UNIQUE,
      PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;');

        $this->db->query('CREATE TABLE IF NOT EXISTS `translations_values` (
      `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
      `language_id` int(10) unsigned NOT NULL,
      `source_str` text NOT NULL,
      `result_str` text NOT NULL,
      PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;');

        return true;
    }
}
