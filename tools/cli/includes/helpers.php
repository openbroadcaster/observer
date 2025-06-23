<?php

namespace ob\tools\cli;

class Helpers
{
    // require a valid installation, exit with error code if not
    public static function requireValid()
    {
        $result_code = null;
        exec(command: 'tools/cli/ob check', result_code: $result_code);
        if ($result_code == 1) {
            echo 'OpenBroadcaster installation is not valid. Run "ob check" for more information.' . PHP_EOL;
            exit(1);
        }
    }

    // output a formatted table
    public static function table(array $rows, int $spacing = 1)
    {
        $cols = [];

        $longest_col1 = 0;
        foreach ($rows as $row) {
            if (is_array($row[0])) {
                $text = $row[0][1];
            } else {
                $text = $row[0];
            }
            $longest_col1 = max(strlen($text), $longest_col1);
        }
        $longest_col1 = min(40, $longest_col1);
        $longest_col1 += $spacing;

        $cols = [
            ['length' => $longest_col1],
            ['length' => 80 - $longest_col1]
        ];

        foreach ($rows as $row) {
            $col_start = 0;
            foreach ($row as $index => $output) {
                if (is_array($output)) {
                    $formatting = $output[0];
                    $output = $output[1];
                } else {
                    $formatting = null;
                }
                $output = trim(preg_replace('/\s+/', ' ', $output));
                $output = str_pad($output, $cols[$index]['length']);
                $output = wordwrap($output, $cols[$index]['length'], PHP_EOL . str_pad('', $col_start));
                if ($formatting) {
                    echo $formatting;
                }
                echo $output;
                if ($formatting) {
                    echo "\033[0m";
                }
                $col_start += $cols[$index]['length'];
            }

            echo PHP_EOL;
        }
    }

    public static function bold(string $text)
    {
        return "\033[1m{$text}\033[0m";
    }
}
