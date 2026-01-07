<?php

class OBUpdate20221028 extends OBUpdate
{
    public function items()
    {
        $updates   = [];
        $updates[] = 'Add uniqueness constraint to combined user ID and name for users storage.';
        return $updates;
    }

    public function run()
    {
        $this->db->query('ALTER TABLE `users_storage` ADD UNIQUE `userid_name_index`(`user_id`, `name`);');
        if ($this->db->error()) {
            return false;
        }

        return true;
    }
}
