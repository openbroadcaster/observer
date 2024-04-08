<?php

class OBUpdate20240408 extends OBUpdate
{
    public function items()
    {
        $updates = [];
        $updates[] = 'Add new countries table and import ISO-3166 into it.';
        $updates[] = 'Add new country column to media table.';
        $updates[] = 'Map existing country IDs on media to new country table.';

        return $updates;
    }

    public function run()
    {
        // Add new countries table and import ISO-3166 into it.
        $this->db->query("CREATE TABLE IF NOT EXISTS `countries` (
            `country_id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
            `name` VARCHAR(255) NOT NULL UNIQUE,
            `alpha2` VARCHAR(2) NOT NULL,
            `alpha3` VARCHAR(3) NOT NULL UNIQUE,
            `code` VARCHAR(3) NOT NULL,
            `region` VARCHAR(255),
            `region_sub` VARCHAR(255),
            `region_intermediate` VARCHAR(255),
            PRIMARY KEY (`country_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;");
        if ($this->db->error()) {
            return false;
        }

        $json = json_decode(file_get_contents(__DIR__ . '/data/iso-3166.json'), true);
        foreach ($json as $country) {
            $this->db->query("INSERT INTO `countries` (`name`, `alpha2`, `alpha3`, `code`, `region`, `region_sub`, `region_intermediate`) 
                VALUES (
                    '" . $this->db->escape($country['name']) . "',
                    '" . $this->db->escape($country['alpha-2']) . "',
                    '" . $this->db->escape($country['alpha-3']) . "',
                    '" . $this->db->escape($country['country-code']) . "',
                    '" . $this->db->escape($country['region']) . "',
                    '" . $this->db->escape($country['sub-region']) . "',
                    '" . $this->db->escape($country['intermediate-region']) . "'
                );");
            if ($this->db->error()) {
                return false;
            }
        }

        // Add new country column to media table.
        // TODO

        // Map existing country IDs on media to new country table.
        // TODO

        return true;
    }
}
