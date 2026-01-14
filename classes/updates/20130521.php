<?php

namespace OpenBroadcaster\Classes\Updates;

use OpenBroadcaster\Classes\Base\Update;

class OBUpdate20130521 extends Update
{
    public function items()
    {
        $updates = [];
        $updates[] = 'Update devices table to support station ID image duration setting.';
        return $updates;
    }

    public function run()
    {
        $this->db->query('ALTER TABLE `devices` ADD `station_id_image_duration` MEDIUMINT UNSIGNED NOT NULL DEFAULT \'15\' AFTER `default_playlist_id`');
        return true;
    }
}
