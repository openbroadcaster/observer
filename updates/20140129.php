<?php

class OBUpdate20140129 extends OBUpdate
{
    public function items()
    {
        $updates = [];
        $updates[] = 'Live assist playlist type.';
        return $updates;
    }

    public function run()
    {
        $this->db->query("ALTER TABLE  `playlists` CHANGE  `type`  `type` ENUM(  'standard',  'advanced',  'live_assist' ) 
                        CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL DEFAULT  'standard'");

        $this->db->query("ALTER TABLE  `playlists_items` CHANGE  `item_type`  `item_type` ENUM(  'media',  'dynamic',  'station_id',  'breakpoint' ) 
                        CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL DEFAULT  'media'");

        return true;
    }
}
