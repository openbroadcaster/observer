<?php

// Copyright 2012-2024 OpenBroadcaster, Inc.
// SPDX-License-Identifier: AGPL-3.0-or-later

require_once('components.php');

if (!empty($_COOKIE['ob_auth_id']) && !empty($_COOKIE['ob_auth_key'])) {
    $auth_id = $_COOKIE['ob_auth_id'];
    $auth_key = $_COOKIE['ob_auth_key'];

    $user = OBFUser::get_instance();
    $user->auth($auth_id, $auth_key);
}

$models = OBFModels::get_instance();
$strings  = $models->ui('strings');
$language = $models->ui('get_user_language');

header('Content-type: text/javascript');

echo 'OB.UI.strings = ' . json_encode($strings) . ';';

if (!empty($language['code'])) {
    echo "\n$(document).ready(function() { $('html').attr('lang','" . $language['code'] . "'); });";
}
