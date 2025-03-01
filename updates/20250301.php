<?php

class OBUpdate20250301 extends OBUpdate
{
    public function items()
    {
        $updates = [];
        $updates[] = 'Extend nonce/token functionality with auto-remove and expiry settings.';
        return $updates;
    }

    public function run()
    {
        $this->db->query('
            ALTER TABLE users_nonces ADD COLUMN expiry INT NULL DEFAULT NULL;
        ');

        $this->db->query('
            ALTER TABLE users_nonces ADD COLUMN delete_after_use BOOLEAN NOT NULL DEFAULT 1;
        ');

        echo $this->db->error();

        return true;
    }
}
