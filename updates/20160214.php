<?php

class OBUpdate20160214 extends OBUpdate
{
    public function items()
    {
        $updates = array();
        $updates[] = 'Database tweaks for performance.';
        return $updates;
    }

    public function run()
    {

    // ADD TIMESTAMP INDEX TO PLAYLOG TABLE

        $this->db->query('SHOW INDEX FROM `playlog`');
        $indexes = $this->db->assoc_list();

        $has_index = false;

        foreach ($indexes as $index) {
            if ($index['Key_name']=='timestamp' && $index['Column_name']=='timestamp') {
                $has_index = true;
                break;
            }
        }

        if (!$has_index) {
            $this->db->query('ALTER TABLE `playlog` ADD INDEX `timestamp` (`timestamp`)');
        }


        // ADD START INDEX TO SCHEDULES_MEDIA_CACHE TABLE

        $this->db->query('SHOW INDEX FROM `schedules_media_cache`');
        $indexes = $this->db->assoc_list();

        $has_index = false;

        foreach ($indexes as $index) {
            if ($index['Key_name']=='start' && $index['Column_name']=='start') {
                $has_index = true;
                break;
            }
        }

        if (!$has_index) {
            $this->db->query('ALTER TABLE `schedules_media_cache` ADD INDEX `start` (`start`)');
        }


        // CONVERT OB TABLES TO INNODB

        $tables = array('client_storage','devices','devices_station_ids','emergencies','media','media_categories','media_countries',
                    'media_genres','media_languages','media_searches','modules','notices','playlists','playlists_items',
                    'playlists_liveassist_buttons','playlog','schedules','schedules_media_cache','schedules_permissions',
                    'schedules_permissions_recurring','schedules_permissions_recurring_expanded','schedules_recurring',
                    'schedules_recurring_expanded','settings','uploads','users','users_groups','users_permissions',
                    'users_permissions_to_groups','users_settings','users_to_groups');

        foreach ($tables as $table) {
            $this->db->query('SHOW TABLE STATUS WHERE Name = \''.$table.'\'');
            $status = $this->db->assoc_row();
            if (strtolower($status['Engine'])=='innodb') {
                continue;
            }

            $this->db->query('ALTER TABLE '.$table.' ENGINE=InnoDB');
        }


        return true;
    }
}
