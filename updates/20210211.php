<?php

class OBUpdate20210211 extends OBUpdate
{
    public function items()
    {
        $updates   = [];
        $updates[] = "Drop foreign keys for schedules_media_cache, then re-add the constraint to player_id. (Quickest way to make sure there is no constraint to schedule_id, which can actually reference multiple tables)";
        return $updates;
    }

    public function run()
    {
        /* Drop foreign keys for schedules_media_cache, then re-add the constraint to
           player_id. (Quickest way to make sure there is no constraint to schedule_id,
           which can actually reference multiple tables) */

        // Get all foreign keys to schedules media cache.
        $result = $this->db->query("SELECT `CONSTRAINT_NAME` FROM information_schema.TABLE_CONSTRAINTS
      WHERE information_schema.TABLE_CONSTRAINTS.CONSTRAINT_TYPE = 'FOREIGN KEY'
      AND information_schema.TABLE_CONSTRAINTS.TABLE_SCHEMA = '" . OB_DB_NAME . "'
      AND information_schema.TABLE_CONSTRAINTS.TABLE_NAME = 'schedules_media_cache';");

        // Remove all the foreign keys.
        foreach ($this->db->assoc_list() as $foreign_key) {
            $this->db->query("ALTER TABLE `schedules_media_cache` DROP FOREIGN KEY " . $foreign_key['CONSTRAINT_NAME'] . ";");
        }

        // Add the constraint to player ID back in.
        $this->db->query('ALTER TABLE `schedules_media_cache` ADD FOREIGN KEY (`player_id`) REFERENCES `players`(`id`) ON DELETE CASCADE ON UPDATE CASCADE;');

        return true;
    }
}
