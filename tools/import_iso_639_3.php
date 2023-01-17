#!/usr/bin/php
<?php

if (php_sapi_name()!='cli') {
    die('cli only');
}

require(__DIR__ . '/../config.php');

$iso = fopen(__DIR__ . '/iso-639-3.tab', 'r');
if (!$iso) {
    die('Couldn\'t load ISO 639-3 tab file.');
}

$conn = mysqli_connect(OB_DB_HOST, OB_DB_USER, OB_DB_PASS);
mysqli_select_db($conn, OB_DB_NAME);

fgets($iso); // skip header line.
while (($line = fgets($iso)) !== false) {
    $values = array_map(fn($x) => "'" . addcslashes(trim($x), "'") . "'", array_map('trim', explode("\t", $line)));

    mysqli_query($conn, "INSERT INTO languages (id, part2b, part2t, part1, scope, language_type, ref_name, comment) "
      . "VALUES (" . implode(",", $values) . ");");
    echo "Inserted language: " . $values[6] . "\n";
}

fclose($iso);
