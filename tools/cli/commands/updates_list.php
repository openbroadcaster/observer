<?php

namespace ob\tools\cli;

if (!defined('OB_CLI')) {
    die('Command line access only.');
}

// only show update list if check passes
Helpers::requireValid();

require_once('updates/updates.php');
$list = $u->updates();
$rows = [];

$installed = 0;
$pending = 0;

foreach ($list as $update) {
    if ($update->needed) {
        $pending++;
        $formatting = "\033[33m";
    } else {
        $installed++;
        $formatting = "\033[32m";
    }
    $rows[] = [[$formatting, $update->version], [$formatting, implode(' ', $update->items())]];
}

echo Helpers::table(spacing: 3, rows: $rows);

echo PHP_EOL .
"\033[32m" . str_pad($installed, 2, ' ', STR_PAD_LEFT) . " installed\033[0m    " .
"\033[33m" . str_pad($pending, 2, ' ', STR_PAD_LEFT) . " pending\033[0m" . PHP_EOL;
