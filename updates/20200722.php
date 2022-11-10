<?php

class OBUpdate20200722 extends OBUpdate
{
    public function items()
    {
        $updates   = array();
        $updates[] = "CASCADE updates to devices, users, or media items to emergencies table. Clean up table first. Fix typing on device_id.";
        $updates[] = "CASCADE updates to devices to notices table. Clean up table first.";
        return $updates;
    }

    public function run()
    {
        // CASCADE updates to devices, users, or media items to emergencies table. Clean up table first. Fix typing on device_id.
        $this->db->query('DELETE FROM `emergencies` WHERE `user_id` NOT IN (SELECT `id` FROM `users`);');
        $this->db->query('DELETE FROM `emergencies` WHERE `item_id` NOT IN (SELECT `id` FROM `media`);');
        $this->db->query('DELETE FROM `emergencies` WHERE `device_id` NOT IN (SELECT `id` FROM `devices`);');

        $this->db->query('ALTER TABLE `emergencies` CHANGE `device_id` `device_id` INT(10) UNSIGNED NOT NULL;');

        $this->db->query('ALTER TABLE `emergencies` ADD INDEX (`user_id`);');
        $this->db->query('ALTER TABLE `emergencies` ADD INDEX (`item_id`);');

        $this->db->query('ALTER TABLE `emergencies` ADD FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE ON UPDATE CASCADE;');
        $this->db->query('ALTER TABLE `emergencies` ADD FOREIGN KEY (`item_id`) REFERENCES `media`(`id`) ON DELETE CASCADE ON UPDATE CASCADE;');
        $this->db->query('ALTER TABLE `emergencies` ADD FOREIGN KEY (`device_id`) REFERENCES `devices`(`id`) ON DELETE CASCADE ON UPDATE CASCADE;');

        // CASCADE updates to devices to notices table. Clean up table first.
        $this->db->query('DELETE FROM `notices` WHERE `device_id` NOT IN (SELECT `id` FROM `devices`)');
        $this->db->query('ALTER TABLE `notices` ADD FOREIGN KEY (`device_id`) REFERENCES `devices`(`id`) ON DELETE CASCADE ON UPDATE CASCADE;');

        return true;
    }
}
