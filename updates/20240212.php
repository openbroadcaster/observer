<?php

class OBUpdate20240212 extends OBUpdate
{
    public function items()
    {
        $updates = [];

        $updates[] = "Add properties column to playlists.";

        return $updates;
    }

    public function run()
    {
        $this->db->query("ALTER TABLE playlists ADD COLUMN properties TEXT;");
        if ($this->db->error()) {
            echo $this->db->error();
            return false;
        }

        return true;
    }
}