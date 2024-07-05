<?php

class OBUpdate20130519 extends OBUpdate
{
    public function items()
    {
        $updates = [];
        $updates[] = 'Update devices table to support storing of remote version.';
        return $updates;
    }

    public function run()
    {
        $this->db->query('ALTER TABLE  `devices` ADD  `version` VARCHAR( 255 ) NOT NULL DEFAULT  \'\' AFTER  `last_connect_media`');
        return true;
    }
}
