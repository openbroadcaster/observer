<?php

class OBUpdate20240617 extends OBUpdate
{
    public function items()
    {
        $updates = [];
        $updates[] = 'Modify deprecated media column names to avoid confusion.';
        $updates[] = 'Rename old countries and languages tables to avoid confusion.';

        return $updates;
    }

    public function run()
    {
        // Modify deprecated media column names to avoid confusion.
        $this->db->query("ALTER TABLE media CHANGE country_id _deprecated_country_id INT UNSIGNED AFTER thumbnail_version;");
        if ($this->db->error()) {
            echo 'Failed to update country_id column in media table.';
            return false;
        }

        $this->db->query("ALTER TABLE media CHANGE language_id _deprecated_language_id INT UNSIGNED AFTER _deprecated_country_id;");
        if ($this->db->error()) {
            echo 'Failed to update language_id column in media table.';
            return false;
        }

        // Rename old countries and languages tables to avoid confusion.
        $this->db->query("RENAME TABLE media_countries TO _deprecated_media_countries;");
        if ($this->db->error()) {
            echo 'Failed to rename media_countries table.';
            return false;
        }

        $this->db->query("RENAME TABLE media_languages TO _deprecated_media_languages;");
        if ($this->db->error()) {
            echo 'Failed to rename media_languages table.';
            return false;
        }

        return true;
    }
}
