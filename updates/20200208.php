<?php

class OBUpdate20200208 extends OBUpdate
{
    public function items()
    {
        $updates   = array();
        $updates[] = 'Merge is_public and status media fields.';
        $updates[] = 'Note that any media allowing public access will now be visible to all users.';
        $updates[] = 'Add public option to playlists.';

        return $updates;
    }

    public function run()
    {

    // new media status column
        $this->db->query('ALTER TABLE `media` ADD `new_status` ENUM(\'private\',\'visible\',\'public\') CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL DEFAULT \'private\' AFTER `status`;');

        // old status public becomes new status visible
        $this->db->where('status', 'public');
        $this->db->update('media', ['new_status' => 'visible']);

        // if media is_public, new status public (regardless of old status)
        $this->db->where('is_public', 1);
        $this->db->update('media', ['new_status' => 'public']);

        // drop old columns
        $this->db->query('ALTER TABLE `media` DROP COLUMN `is_public`;');
        $this->db->query('ALTER TABLE `media` DROP COLUMN `status`;');

        // rename new status
        $this->db->query('ALTER TABLE `media` CHANGE `new_status` `status` ENUM(\'private\',\'visible\',\'public\') CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL DEFAULT \'private\';');

        // update playlist status column
        $this->db->query('ALTER TABLE `playlists` CHANGE `status` `status` ENUM(\'private\',\'visible\',\'public\') CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL DEFAULT \'private\';');

        // playlist public becomes visible
        $this->db->where('status', 'public');
        $this->db->update('playlists', ['status' => 'visible']);

        return true;
    }
}
