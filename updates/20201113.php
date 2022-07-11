<?php

class OBUpdate20201113 extends OBUpdate
{
    public function items()
    {
        $updates   = array();
        $updates[] = "Add AppKey permissions.";
        return $updates;
    }

    public function run()
    {
        $this->db->query("ALTER TABLE `users_appkeys` ADD `permissions` TEXT NOT NULL DEFAULT '' AFTER `key`;");
        if ($this->db->error()) {
            return false;
        }
        return true;
    }
}
