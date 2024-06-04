<?php

class OBUpdate20240604 extends OBUpdate
{
    public function items()
    {
        $updates = [];
        $updates[] = 'Add a mode field to the alerts table.';

        return $updates;
    }

    public function run()
    {
        $this->db->query("ALTER TABLE alerts ADD COLUMN mode ENUM('Interrupt', 'Voicetrack') NOT NULL AFTER frequency;");
        if ($this->db->error()) {
            echo "Failed to add mode field.";
            return false;
        }

        return true;
    }
}
