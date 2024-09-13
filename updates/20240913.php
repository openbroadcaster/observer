<?php

class OBUpdate20240913 extends OBUpdate
{
    public function items()
    {
        $updates = [];
        $updates[] = 'Add visibility option to metadata fields.';
        return $updates;
    }

    public function run()
    {
        $this->db->query('
            ALTER TABLE `media_metadata` ADD COLUMN `visibility` ENUM("visible", "public") NOT NULL DEFAULT "public";
        ');

        return true;
    }
}
