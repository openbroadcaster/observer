<?php

class OBUpdate20230318 extends OBUpdate
{
    public function items()
    {
        $updates = [];
        $updates[] = 'Create players log table.';

        return $updates;
    }

    public function run()
    {
        $this->db->query('CREATE TABLE `players_log` ('
            . ' `id` int(10) unsigned NOT NULL AUTO_INCREMENT,'
            . ' `player_id` int(10) unsigned NOT NULL,'
            . ' `timestamp` int(10) unsigned NOT NULL,'
            . ' `media_id` int(10) unsigned DEFAULT NULL,'
            . ' `playlist_id` int(10) unsigned DEFAULT NULL,'
            . ' `media_end` int(10) unsigned DEFAULT NULL,'
            . ' `playlist_end` int(10) unsigned DEFAULT NULL,'
            . ' `show_name` varchar(255) DEFAULT NULL,'
            . ' PRIMARY KEY (`id`),'
            . ' KEY `media_id` (`media_id`),'
            . ' KEY `playlist_id` (`playlist_id`),'
            . ' CONSTRAINT `players_log_ibfk_1` FOREIGN KEY (`media_id`) REFERENCES `media` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,'
            . ' CONSTRAINT `players_log_ibfk_2` FOREIGN KEY (`playlist_id`) REFERENCES `playlists` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,'
            . ' CONSTRAINT `players_log_ibfk_3` FOREIGN KEY (`player_id`) REFERENCES `players` (`id`) ON DELETE CASCADE ON UPDATE CASCADE);');
        if ($this->db->error()) {
            return false;
        }

        return true;
    }
}
