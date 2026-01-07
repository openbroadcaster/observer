<?php

class OBUpdate20200718 extends OBUpdate
{
    public function items()
    {
        $updates   = [];
        $updates[] = "CASCADE device updates to devices table. Clean out any non-existing devices first (this shouldn't be possible in normal use, but make sure anyway so the update runs). Also fix data type inconsistencies.";
        $updates[] = "CASCADE device and media updates to devices_station_ids table. Clean out any rows with device or media IDs that no longer exist first.";
        return $updates;
    }

    public function run()
    {
        // CASCADE device updates to devices table. Clean out any non-existing devices first (this shouldn't be possible in normal use, but make sure anyway so the update runs). Also fix data type inconsistencies.
        $this->db->query('DELETE FROM `devices` WHERE `parent_device_id` NOT IN (SELECT `id` FROM `devices`);');

        $this->db->query('ALTER TABLE `devices` ADD INDEX (`parent_device_id`);');
        $this->db->query('ALTER TABLE `devices` CHANGE `id` `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT;');
        $this->db->query('ALTER TABLE `devices` ADD FOREIGN KEY (`parent_device_id`) REFERENCES `devices`(`id`) ON DELETE CASCADE ON UPDATE CASCADE;');

        // CASCADE device and media updates to devices_station_ids table. Clean out any rows with device or media IDs that no longer exist first.
        $this->db->query('DELETE FROM `devices_station_ids` WHERE `device_id` NOT IN (SELECT `id` FROM `devices`);');
        $this->db->query('DELETE FROM `devices_station_ids` WHERE `media_id` NOT IN (SELECT `id` FROM `media`);');

        $this->db->query('ALTER TABLE `devices_station_ids` ADD INDEX (`media_id`);');
        $this->db->query('ALTER TABLE `devices_station_ids` ADD FOREIGN KEY (`device_id`) REFERENCES `devices`(`id`) ON DELETE CASCADE ON UPDATE CASCADE;');
        $this->db->query('ALTER TABLE `devices_station_ids` ADD FOREIGN KEY (`media_id`) REFERENCES `media`(`id`) ON DELETE CASCADE ON UPDATE CASCADE;');

        return true;
    }
}
