<?php

namespace OpenBroadcaster\CLI;

use OpenBroadcaster\Base\CLI;

class Passwd extends CLI
{
    public function run(array $args): bool
    {
        // requires valid install
        Helpers::requireValid();

        $username = $args[0] ?? null;
        if (! $username) {
            (new OBCLI())->help();
            return false;
        }

        $this->db->where('username', $username);
        $user = $this->db->get_one('users');
        if (!$user) {
            echo 'User not found.' . PHP_EOL;
            exit(1);
        }

        $cli_specified_password = $args[1] ?? '';
        if (trim($cli_specified_password)) {
            if (strlen($cli_specified_password) < 6) {
                echo 'Password must be at least 6 characters long.' . PHP_EOL;
                return false;
            }
            $password = $cli_specified_password;
        } else {
            exec('stty -echo');

            $password = '';
            $password_again = '';

            $valid = false;
            do {
                echo 'New password: ';
                $password = trim(readline());
                echo PHP_EOL . 'New password (again): ';
                $password_again = trim(readline());

                if ($password != $password_again) {
                    echo PHP_EOL . 'Passwords do not match.' . PHP_EOL;
                } elseif (strlen($password) < 6) {
                    echo PHP_EOL . 'Password must be at least 6 characters long.' . PHP_EOL;
                } else {
                    $valid = true;
                }
            } while (!$valid);

            exec('stty echo');
        }

        $passsword_hashed = password_hash($password . OB_HASH_SALT, PASSWORD_DEFAULT);

        $this->db->where('username', $username);
        $this->db->update('users', ['password' => $passsword_hashed]);

        echo PHP_EOL . 'Password updated.' . PHP_EOL;

        return true;
    }
}