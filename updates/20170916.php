<?php

class OBUpdate20170916 extends OBUpdate
{
    public function items()
    {
        $updates = array();
        $updates[] = 'Media table index updates.';
        return $updates;
    }

    public function run()
    {
        $this->db->query("ALTER TABLE `media` DROP INDEX artist");
        $this->db->query("ALTER TABLE `media` DROP INDEX title");

        $this->db->query("ALTER TABLE `media` DROP INDEX owner_id");
        $this->db->query("ALTER TABLE `media` DROP INDEX language_id");
        $this->db->query("ALTER TABLE `media` DROP INDEX country_id");
        $this->db->query("ALTER TABLE `media` DROP INDEX category_id");
        $this->db->query("ALTER TABLE `media` DROP INDEX genre_id");

        $this->db->query("ALTER TABLE `media` ADD INDEX(`owner_id`)");
        $this->db->query("ALTER TABLE `media` ADD INDEX(`language_id`)");
        $this->db->query("ALTER TABLE `media` ADD INDEX(`country_id`)");
        $this->db->query("ALTER TABLE `media` ADD INDEX(`category_id`)");
        $this->db->query("ALTER TABLE `media` ADD INDEX(`genre_id`)");

        return true;
    }
}
