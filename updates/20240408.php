<?php

class OBUpdate20240408 extends OBUpdate
{
    public function items()
    {
        $updates = [];
        $updates[] = 'Add new countries table and import ISO-3166 into it.';
        $updates[] = 'Add any countries in old media_countries table that aren\'t in the new table to the new table.';
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
            `alpha2` VARCHAR(2),
            `alpha3` VARCHAR(3) NOT NULL UNIQUE,
            `code` VARCHAR(3),
            `region` VARCHAR(255),
            `region_sub` VARCHAR(255),
            `region_intermediate` VARCHAR(255),
            PRIMARY KEY (`country_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;");
        if ($this->db->error()) {
            echo 'Failed to create countries table.';
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
                echo 'Failed to insert country ' . $country['name'] . ' into countries table.';
                return false;
            }
        }

        // Add any countries in old media_countries table that aren't in the new table to the new table.
        $countriesOld = $this->db->get('media_countries');
        if ($this->db->error()) {
            echo 'Failed to query media_countries table.';
            return false;
        }

        $alpha3 = 'QMA';
        foreach ($countriesOld as $country) {
            $this->db->where('name', $country['name']);
            $countryNew = $this->db->get('countries');
            if ($this->db->error()) {
                echo 'Failed to query countries table.';
                return false;
            }

            if (empty($countryNew)) {
                // Country name couldn't be found in ISO-3166, add it to the new table.
                $this->db->query("INSERT INTO `countries` (`name`, `alpha3`) VALUES ('"
                    . $this->db->escape($country['name']) . "', '"
                    . $alpha3
                    . "');");
                if ($this->db->error()) {
                    echo 'Failed to insert country ' . $country['name'] . ' into countries table.';
                    return false;
                }

                $alpha3 = $this->incrementAlpha3($alpha3);
            }
        }

        // Add new country column to media table.
        $this->db->query("ALTER TABLE `media` ADD COLUMN `country` INT(10) UNSIGNED DEFAULT NULL AFTER `country_id`;");
        if ($this->db->error()) {
            echo 'Failed to add country column to media table.';
            return false;
        }

        $this->db->query("ALTER TABLE `media` ADD FOREIGN KEY (`country`) REFERENCES `countries`(`country_id`) ON DELETE SET NULL ON UPDATE CASCADE;");
        if ($this->db->error()) {
            echo 'Failed to add foreign key to media table.';
            return false;
        }

        // Map existing country IDs on media to new country table.
        // TODO

        return true;
    }

    // Same function as in 20230418 (languages table update)
    private function incrementAlpha3($str)
    {
        $str = strrev($str); // Reverse the string for easier iteration
        $len = strlen($str);
        $carry = 1; // Start with a carry to increment the first character

        for ($i = 0; $i < $len; $i++) {
            $char = ord($str[$i]) - ord('a') + $carry; // Get the base-26 value of the character

            if ($char >= 26) { // If there is a carry
                $char %= 26;
                $carry = 1;
            } else {
                $carry = 0;
            }

            $str[$i] = chr(ord('a') + $char); // Update the character in the string

            if ($carry == 0) { // No further carry, break the loop
                break;
            }
        }

        if ($carry) { // If there's still a carry after the loop
            $str .= 'a'; // Append an 'a' to the string
        }

        return strrev($str); // Reverse the string back to its original order
    }
}
