<?php

namespace ob\tools\cli;

if (!defined('OB_CLI')) {
    die('Command line access only.');
}

// requires valid install
Helpers::requireValid();

$username = $subcommand;

require_once('components.php');

$db = new \OBFDB();

$db->where('username', $username);
$user = $db->get_one('users');
if (!$user) {
    echo 'User not found.' . PHP_EOL;
    exit(1);
}

$password = '';
$password_again = '';

exec('stty -echo');

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

$passsword_hashed = password_hash($password . OB_HASH_SALT, PASSWORD_DEFAULT);

$db->where('username', $username);
$db->update('users', ['password' => $passsword_hashed]);

echo PHP_EOL . 'Password updated.' . PHP_EOL;
exec('stty echo');
