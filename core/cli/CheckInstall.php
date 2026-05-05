<?php

namespace OpenBroadcaster\CLI;

use OpenBroadcaster\Base\CLI;

class CheckInstall extends CLI
{
    protected array|string $help = 'check installation for errors';

    public function run(array $args): bool
    {
        $checker = new \OpenBroadcaster\Support\Checker();
        $methods = get_class_methods($checker);
        $methods = array_filter($methods, fn($x) => $x !== '__construct');
        $rows = [];
        $errors = 0;
        $warnings = 0;
        $pass = 0;

        $check_fatal_error = false;

        foreach ($methods as $method) {
            $result = $checker->$method();

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

            return false;
        } else {
            echo PHP_EOL .
            "\033[32m" . str_pad($pass, 2, ' ', STR_PAD_LEFT) . " pass\033[0m    " .
            "\033[33m" . str_pad($warnings, 2, ' ', STR_PAD_LEFT) . " warnings\033[0m    " .
            "\033[31m" . str_pad($errors, 2, ' ', STR_PAD_LEFT) . " errors\033[0m" . PHP_EOL;
        }

        return true;
    }
}
