<?php

class OBUpdate20161206 extends OBUpdate
{
    public function items()
    {
        $updates = array();
        $updates[] = 'Support last ip address of a device.';
        return $updates;
    }

    public function run()
    {
        $this->db->query("ALTER TABLE  `devices` ADD  `last_ip_address` VARCHAR(48) DEFAULT NULL  AFTER  `owner_id`");

        return true;
    }
}
