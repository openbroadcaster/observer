<?php

class OBUpdate20140322 extends OBUpdate
{
    public function items()
    {
        $updates = array();
        $updates[] = 'Live assist button playlists.';
        return $updates;
    }

    public function run()
    {
        $this->db->query("CREATE TABLE IF NOT EXISTS `playlists_liveassist_buttons` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `playlist_id` int(10) unsigned NOT NULL,
  `order_id` int(10) unsigned NOT NULL,
  `button_playlist_id` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `playlist_id` (`playlist_id`,`order_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;");

        $this->db->query("CREATE TABLE IF NOT EXISTS `schedules_media_cache` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `mode` enum('once','recurring','default_playlist') NOT NULL,
  `schedule_id` int(10) unsigned DEFAULT NULL,
  `device_id` int(10) unsigned DEFAULT NULL,
  `start` int(10) unsigned NOT NULL,
  `duration` mediumint(9) unsigned NOT NULL,
  `data` mediumtext CHARACTER SET utf8 NOT NULL,
  `created` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `schedule_id` (`schedule_id`,`start`),
  KEY `duration` (`duration`),
  KEY `device_id` (`device_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;");

        return true;
    }
}
