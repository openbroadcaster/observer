<?php

class OBUpdate20240630 extends OBUpdate
{
    public function items()
    {
        $updates = [];
        $updates[] = 'Add "documents" media type support.';

        return $updates;
    }

    public function run()
    {
        $this->db->query("alter table uploads change `type` `type` enum('audio','image','video','document') null default null;");
        if ($this->db->error()) {
            echo 'Failed to update uploads table';
            return false;
        }

        $this->db->query("alter table media change `type` `type` enum('audio','image','video','document') NOT NULL DEFAULT 'audio';");
        if ($this->db->error()) {
            echo 'Failed to update media table';
            return false;
        }

        return true;
    }
}
