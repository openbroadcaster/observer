#!/usr/bin/php
<?php

if (php_sapi_name()!='cli') {
    die('cli only');
}

require(__DIR__ . '/../components.php');

$db = OBFDB::get_instance();

$db->query('SELECT language_id, ref_name FROM languages');
$languages = $db->assoc_list();

foreach ($db->get('media_languages') as $remaining_lang) {
    $distance = array_map(fn($lang) => [
        'id'   => $lang['language_id'],
        'name' => $lang['ref_name'],
        'dist' => levenshtein($remaining_lang['name'], $lang['ref_name'])
    ], $languages);
    // $distance = array_values(array_filter($distance, fn($new) => $new['dist'] <= 2));
    usort($distance, fn($a, $b) => $a['dist'] <=> $b['dist']);
    $distance = array_slice($distance, 0, 5);
    $distance = array_map(fn($dist) => $dist['name'] . " (" . $dist['dist'] . ")", $distance);

    echo "Remaining language '" . $remaining_lang['name'] . "' closest new languages: "
      . implode(", ", $distance) . ".\n";
}
