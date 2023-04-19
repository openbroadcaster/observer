<?php

/*
 * The ISO 639-3 code set is managed by SIL International. Please see https://iso639-3.sil.org/
 * for terms, code set download, and other information.
 *
 * The code set can be downloaded at https://iso639-3.sil.org/code_tables/download_tables.
 */

class OBUpdate20230418 extends OBUpdate
{
    public function items()
    {
        $updates = [];
        $updates[] = 'Add new languages table and import ISO-639-3 into it. NOTE: This may take a minute or so.';
        $updates[] = 'Backup old media_languages table.';
        $updates[] = 'Add language field to media for new table.';
        $updates[] = 'Go through old language table, match against ISO-693-3, update media items, and delete old language rows.';
        $updates[] = 'Rename field in core metadata setting.';

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

        // NOTE: TODO: This can lead to confusion as language_id is no longer used in media,
        // HOWEVER the languages table itself has a language_id field as its primary key. Please
        // keep this in mind and drop the language_id column from media as soon as possible.
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
            // Only copy language over if there's an exact match between it and
            // ISO 639-3. Levenshtein distance can result in the wrong language
            // even with a distance of 1.
            $matches = array_values(array_filter($new_langs, fn($new) => $new['ref_name'] == $old['name']));

            if (empty($matches)) {
              continue;
            }

            // Find all media items using the old language item, adding the new
            // closest language to its language field instead. Deleting the old
            // language after takes care of setting the language_id field to NULL
            // (so this doesn't need to be done manually).
            $this->db->where('language_id', $old['id']);
            $this->db->update('media', [
              'language' => $matches[0]['id']
            ]);

            $this->db->where('id', $old['id']);
            $this->db->delete('media_languages');
        }

        // For remaining languages, use custom JSON data to map to new languages table.
        //
        // Data initially generated by ChatGPT and afterwards edited by hand. Note that
        // a large number of 639-2T and 639-2B fields are filled when they should be null,
        // so we only go by 639-3 value. Assumptions have to be made in cases where
        // a name can cover multiple languages (in cases of an entire group of languages
        // they will be part of the qaa-qtz reserved list). As much as possible,
        // languages that are more specific than what ChatGPT assumes without a fitting
        // 639-3 code will be given their own new code.
        //
        // NOTE: I am not a linguist. A reasonable effort has been made to make sure the
        // languages map to the correct codes, but ultimately the author of this code
        // is not an expert on the distinctions between various languages, and as a result
        // media items may still end up with an inaccurate language designation and require
        // some further manual input.
        $remaining_json = json_decode(file_get_contents(__DIR__ . '/data/languages_remaining.json'), true);
        $iso_new_code = 'qaa';
        foreach ($remaining_json as $remain_lang) {
            // Get IDs of remaining languages in media_languages table.
            $this->db->where('name', $remain_lang['name']);
            $old_langs = $this->db->get('media_languages') ?? [];

            if (empty($old_langs)) continue;

            // We have an ISO-639-3 code: get the new ID from the languages table, then
            // iterate over all the media items with old language IDs and set the language
            // to the ones in the new table.
            $iso_lang = null;
            if ($remain_lang['iso639-3']) {
                $this->db->where('id', $remain_lang['iso639-3']);
                $iso_lang = $this->db->get('languages')[0]['language_id'] ?? null;

                if (! $iso_lang) die('Failed to find language for ISO 639-3 code ' . $remain_lang['iso639-3']);

                // Add comment if available even for pre-existing language codes.
                if (isset($remain_lang['comment'])) {
                    $this->db->where('language_id', $iso_lang);
                    $this->db->update('languages', [
                        'comment' => $remain_lang['comment']
                    ]);
                }
            // No ISO-639-3 code in table. Create a new code starting from 'qaa'
            // and going to 'qtz', then map all media items to the new ID before
            // deleting the old media language.
            } else {
                $iso_lang = $this->db->insert('languages', [
                    'id'            => $iso_new_code,
                    'scope'         => 'I', // treat new languages as Individual
                    'language_type' => 'S', // treat new languages as Special
                    'ref_name'      => $remain_lang['name'],
                    'comment'       => $remain_lang['comment'] ?? null
                ]);

                if (! $iso_lang) die('Failed to create new language with ISO 639-3 code '. $iso_new_code);

                $iso_new_code = $this->increment_string($iso_new_code);
            }

            foreach ($old_langs as $old) {
                $this->db->where('language_id', $old['id']);
                $this->db->update('media', [
                    'language' => $iso_lang
                ]);

                $this->db->where('id', $old['id']);
                $this->db->delete('media_languages');
            }
        }

        // Rename field in core metadata setting.

        $this->db->where('name', 'core_metadata');
        $core_metadata = json_decode($this->db->get('settings')[0]['value'], true);
        if ($this->db->error()) {
            return false;
        }
        $core_metadata['language'] = $core_metadata['language_id'];
        unset($core_metadata['language_id']);
        $this->db->where('name', 'core_metadata');
        $this->db->update('settings', [
            'value' => json_encode($core_metadata)
        ]);
        if ($this->db->error()) {
            return false;
        }

        return true;
    }

    // Function created by ChatGPT
    private function increment_string($str)
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
