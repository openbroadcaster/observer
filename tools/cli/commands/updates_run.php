<?php

namespace ob\tools\cli;

if (!defined('OB_CLI')) {
    die('Command line access only.');
}

// only run update if check passes
Helpers::requireValid();

require_once('updates/updates.php');
$list = $u->updates();
foreach ($list as $update) {
    if ($update->needed) {
        if (!$u->run($update)) {
            echo 'Update failed, exiting.' . PHP_EOL;
            exit(1);
        }
        echo 'Update ' . $update->version . ' installed.' . PHP_EOL;
    }
}

exit(0);
