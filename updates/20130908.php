<?php

class OBUpdate20130908 extends OBUpdate
{
    public function items()
    {
        $updates = array();
        $updates[] = 'Hash device passwords in database.';
        $updates[] = 'Add support for child/parent device relationships.';
        return $updates;
    }

    public function run()
    {
        // hash device passwords.
        $devices = $this->db->get('devices');
        foreach ($devices as $device) {
            $pw_hash = sha1(OB_HASH_SALT . $device['password']);
            $this->db->where('id', $device['id']);
            $this->db->update('devices', array('password' => $pw_hash));
        }

        // support for child/parent device relationships.
        $this->db->query('ALTER TABLE  `devices` ADD  `parent_device_id` INT UNSIGNED NULL DEFAULT NULL AFTER  `description` ,
ADD  `use_parent_schedule` BOOLEAN NOT NULL DEFAULT  \'0\' AFTER  `parent_device_id` ,
ADD  `use_parent_ids` BOOLEAN NOT NULL DEFAULT  \'0\' AFTER  `use_parent_schedule` ,
ADD  `use_parent_dynamic` BOOLEAN NOT NULL DEFAULT  \'0\' AFTER  `use_parent_ids` ,
ADD  `use_parent_playlist` BOOLEAN NOT NULL DEFAULT  \'0\' AFTER  `use_parent_dynamic` ,
ADD  `use_parent_emergency` BOOLEAN NOT NULL DEFAULT  \'0\' AFTER  `use_parent_playlist`');

        return true;
    }
}
