<?php

// Copyright 2012-2024 OpenBroadcaster, Inc.
// SPDX-License-Identifier: AGPL-3.0-or-later


if (php_sapi_name()!='cli') {
    die('cli only');
}

$dirs = scandir(__DIR__.'/../themes/');

foreach ($dirs as $dir) {
    $fulldir = realpath(__DIR__.'/../themes/'.$dir);
    if ($dir[0]=='.' || !is_dir($fulldir) || !is_file($fulldir.'/style.scss')) {
        continue;
    }

    $command = 'sass -s compressed '.escapeshellarg($fulldir.'/style.scss').' '.escapeshellarg($fulldir.'/style.css');
    passthru($command);
}
