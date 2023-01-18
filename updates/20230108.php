<?php

/*
 * The ISO 639-3 code set is managed by SIL International. Please see https://iso639-3.sil.org/
 * for terms, code set download, and other information.
 *
 * The code set can be downloaded at https://iso639-3.sil.org/code_tables/download_tables.
 */

class OBUpdate20230108 extends OBUpdate
{
    public function items()
    {
        $updates = [];
        $updates[] = 'Add new languages table and import ISO-639-3 into it. NOTE: This may take a minute or so.';
        $updates[] = 'Backup old media_languages table.';
        $updates[] = 'Add language field to media for new table.';
        $updates[] = 'Go through old language table, match against ISO-693-3, update media items, and delete old language rows.';

        return $updates;
    }

    public function run()
    {
        // Add new languages table and import ISO-639-3 into it. NOTE: This may take a minute or so.

        $this->db->query("CREATE TABLE IF NOT EXISTS `languages` (
            `language_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
            `id` varchar(3) NOT NULL,
            `part2b` varchar(3) DEFAULT NULL,
            `part2t` varchar(3) DEFAULT NULL,
            `part1` varchar(2) DEFAULT NULL,
            `scope` set('I','M','S') NOT NULL,
            `language_type` set('A','C','E','H','L','S') NOT NULL,
            `ref_name` varchar(150) NOT NULL,
            `comment` varchar(150) DEFAULT NULL,
            PRIMARY KEY (`language_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;");
        if ($this->db->error()) {
            return false;
        }

        $iso = fopen(__DIR__ . '/data/iso-639-3.tab', 'r');
        if (!$iso) {
            die('Couldn\'t load ISO 639-3 tab file.');
        }

        fgets($iso); // skip header line.
        while (($line = fgets($iso)) !== false) {
            $values = array_map(fn($x) => "'" . addcslashes(trim($x), "'") . "'", array_map('trim', explode("\t", $line)));

            // This could be more efficient by adding all the values in a single query
            // but there aren't *that* many languages that it's worth workshopping
            // this, probably.
            $this->db->query("INSERT INTO languages (id, part2b, part2t, part1, scope, language_type, ref_name, comment) "
                . "VALUES (" . implode(",", $values) . ");");
            // echo "Inserted language: " . $values[6] . "\n";

            // Quit on a single error because that genuinely shouldn't happen, and
            // indicates the ISO tab file needs to be checked at the least.
            if ($this->db->error()) {
                return false;
            }
        }
        fclose($iso);

        // Backup old media_languages table.

        $this->db->query("CREATE TABLE _media_languages AS SELECT * FROM media_languages;");
        if ($this->db->error()) {
            return false;
        }
        $this->db->query("ALTER TABLE _media_languages ADD PRIMARY KEY(id);");
        if ($this->db->error()) {
            return false;
        }
        $this->db->query("ALTER TABLE _media_languages MODIFY id INT UNSIGNED NOT NULL AUTO_INCREMENT;");
        if ($this->db->error()) {
            return false;
        }

        // Add language field to media for new table.

        $this->db->query("ALTER TABLE media ADD language INT UNSIGNED AFTER language_id;");
        if ($this->db->error()) {
            return false;
        }

        $this->db->query("ALTER TABLE media ADD FOREIGN KEY (language) REFERENCES languages(language_id) ON DELETE SET NULL ON UPDATE CASCADE;");
        if ($this->db->error()) {
            return false;
        }

        // Go through old language table, match against ISO-693-3, update media items, and delete old language rows.
        $old_langs = $this->db->get('media_languages');
        $new_langs = $this->db->get('languages');

        foreach ($old_langs as $old) {
            // First, get the Levenshtein distance from all new languages to the
            // current old language in an array. Then filter to only those with
            // a distance of 2 or lower. Finally, sort the distances so the
            // closest language comes first.
            $distance = array_map(fn($new) => [
                'id'   => $new['language_id'],
                'name' => $new['ref_name'],
                'dist' => levenshtein($old['name'], $new['ref_name'])
            ], $new_langs);
            $distance = array_values(array_filter($distance, fn($new) => $new['dist'] <= 2));
            usort($distance, fn($a, $b) => $a['dist'] <=> $b['dist']);

            // No appropiate language found. Skip.
            if (empty($distance)) {
                continue;
            }

            // Find all media items using the old language item, adding the new
            // closest language to its language field instead. Deleting the old
            // language after takes care of setting the language_id field to NULL
            // (so this doesn't need to be done manually).
            $this->db->where('language_id', $old['id']);
            $this->db->update('media', [
              'language' => $distance[0]['id']
            ]);

            $this->db->where('id', $old['id']);
            $this->db->delete('media_languages');
        }

        return true;
    }
}
