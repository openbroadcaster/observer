<?php

class OBUpdate20130502 extends OBUpdate
{
    public function items()
    {
        $updates = [];

        $updates[] = '"My Searches" system';
        $updates[] = 'Misc updates to prepare for advanced playlists.';

        return $updates;
    }

    public function run()
    {
        $this->db->query('CREATE TABLE IF NOT EXISTS `media_searches` (
      `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
      `user_id` int(10) unsigned NOT NULL,
      `query` text NOT NULL,
      `type` enum(\'saved\',\'history\') NOT NULL,
      `default` tinyint(1) NOT NULL,
      `description` varchar(255) NOT NULL DEFAULT \'\',
      PRIMARY KEY (`id`),
      KEY `user_id` (`user_id`),
      KEY `type` (`type`),
      KEY `default` (`default`)
    )');

        $this->db->query('ALTER TABLE `playlists` ADD `type` ENUM( \'standard\', \'advanced\' ) NOT NULL DEFAULT \'standard\' AFTER `owner_id`');

        $this->db->query('ALTER TABLE `playlists_items` CHANGE `ord` `ord` DECIMAL( 10, 3 ) NULL DEFAULT \'0\'');

        $this->db->query('ALTER TABLE `playlog` CHANGE `context` `context` ENUM( \'show\', \'emerg\', \'fallback\' ) NOT NULL');

        return true;
    }
}
