<?php

class OBUpdate20200909 extends OBUpdate
{
    public function items()
    {
        $updates   = array();
        $updates[] = "Update timeslots permission in users_permissions to be more clear.";
        $updates[] = "Update timeslots table names to be more clear.";
        return $updates;
    }

    public function run()
    {
        // Update timeslots permission in users_permissions to be more clear.
        $this->db->query("UPDATE `users_permissions` SET `name` = 'manage_timeslots' WHERE `users_permissions`.`name` = 'manage_schedule_permissions';");

        // Update timeslots table names to be more clear.
        $this->db->query("ALTER TABLE `schedules_permissions` RENAME TO `timeslots`;");
        $this->db->query("ALTER TABLE `schedules_permissions_recurring` RENAME TO `timeslots_recurring`;");
        $this->db->query("ALTER TABLE `schedules_permissions_recurring_expanded` RENAME TO `timeslots_recurring_expanded`");

        return true;
    }
}
