<?php

class OBUpdate20200706 extends OBUpdate
{
    public function items()
    {
        $updates   = array();
        $updates[] = "CASCADE user updates to client_storage table. Clean out any non-existing users first.";
        return $updates;
    }

    public function run()
    {
        $this->db->query('START TRANSACTION;');

        // CASCADE user updates to client_storage table. Clean out any non-existing users first.
        $this->db->query('DELETE FROM `client_storage` WHERE `user_id` NOT IN (SELECT `id` FROM `users`);');
        $this->db->query('ALTER TABLE `client_storage` ADD FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE ON UPDATE CASCADE;');

        $this->db->query('COMMIT;');
        if ($this->db->error()) {
            return false;
        }

        return true;
    }
}
