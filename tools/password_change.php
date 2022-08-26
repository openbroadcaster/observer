#!/usr/bin/php
<?php

if (php_sapi_name()!='cli') {
    die('cli only');
}

if (empty($argv[1]) || empty($argv[2])) {
    die('Invalid args.'.PHP_EOL);
}

// argv[1] = username
// argv[2] = password

require(__DIR__.'/../config.php');

$hashed = password_hash($argv[2].OB_HASH_SALT, PASSWORD_DEFAULT);

$conn = mysqli_connect(OB_DB_HOST, OB_DB_USER, OB_DB_PASS);
mysqli_select_db($conn, OB_DB_NAME);
mysqli_query($conn, "UPDATE users SET password='$hashed' WHERE username='$argv[1]';");
if (mysqli_affected_rows($conn)) {
    echo 'Password changed.'.PHP_EOL;
} else {
    echo 'Error updating password.'.PHP_EOL;
}
