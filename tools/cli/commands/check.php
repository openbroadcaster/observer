<?php

namespace ob\tools\cli;

require('updates/checker.php');

$checker = new \OBFChecker();
$methods = get_class_methods($checker);
$results = [];
$rows = [];
$errors = 0;
$warnings = 0;
$pass = 0;

$check_fatal_error = false;

foreach ($methods as $method) {
    // directories valid needs to be run via web. use includes/web.php to do that.
    if ($method == 'directories_valid') {
        $ob_site = OB_SITE;
        if (!str_ends_with($ob_site, '/')) {
            $ob_site .= '/';
        }

        $web_check_result = json_decode(file_get_contents($ob_site . 'tools/cli/includes/web.php'), true);
        $result = $web_check_result['directories_valid'] ?? ['Directories', 'Unable to check directory permissions.', 1];
    } else {
        $result = $checker->$method();
    }
    $results[] = $result;

    $formatting1 = '';
    $formatting2 = '';

    switch ($result[2]) {
        case 0:
            $formatting = "\033[32m";
            $pass++;
            break;
        case 1:
            $formatting = "\033[33m";
            $warnings++;
            break;
        case 2:
            $formatting = "\033[31m";
            $errors++;
    }

    // sometimes we get multiple strings in an array that needs imploding.
    if (is_array($result[1])) {
        $result[1] = implode(' ', $result[1]);
    }

    $rows[] = [[$formatting,$result[0]], [$formatting, $result[1]]];

    if ($result[2] > 1) {
        $check_fatal_error = true;
        break;
    }
}

Helpers::table(rows: $rows);

if ($check_fatal_error) {
    echo "\033[31m";
    echo PHP_EOL . 'Error detected, testing stopped . Correct the above error then run again . ' . PHP_EOL;
    echo "\033[0m";
} else {
    echo PHP_EOL .
    "\033[32m" . str_pad($pass, 2, ' ', STR_PAD_LEFT) . " pass\033[0m    " .
    "\033[33m" . str_pad($warnings, 2, ' ', STR_PAD_LEFT) . " warnings\033[0m    " .
    "\033[31m" . str_pad($errors, 2, ' ', STR_PAD_LEFT) . " errors\033[0m" . PHP_EOL;
}
