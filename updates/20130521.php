<?php

class OBUpdate20130521 extends OBUpdate
{
    public function items()
    {
        $updates = array();
        $updates[] = 'Update devices table to support station ID image duration setting.';
        return $updates;
    }

    public function run()
    {
        $this->db->query('ALTER TABLE `devices` ADD `station_id_image_duration` MEDIUMINT UNSIGNED NOT NULL DEFAULT \'15\' AFTER `default_playlist_id`');
        return true;
    }
}
