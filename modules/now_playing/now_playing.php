<?php

// Copyright 2012-2024 OpenBroadcaster, Inc.
// SPDX-License-Identifier: AGPL-3.0-or-later

header("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1
header("Expires: Sat, 26 Jul 1997 05:00:00 GMT"); // Date in the past

require('../../components.php');

$load = OBFLoad::get_instance();
$db = OBFDB::get_instance();

// make sure this module is installed
$module_model = $load->model('Modules');
$installed_modules = $module_model('get_installed');
if(!isset($installed_modules['now_playing'])) die('The "now playing" module is not installed.');

// make sure this player is valid.
if(!isset($_GET['i']) || !preg_match('/^\d+$/',$_GET['i'])) die('Invalid player.');
$player_model = $load->model('Players');
$player = $player_model('get_one',$_GET['i']);
if(!$player) die('Invalid player.');

$data = $player_model('now_playing',$_GET['i']);

$db->what('file_location');
$db->where('id', $data['media']['id']);
$row = $db->get_one('media');
$file_location = $row['file_location'];

// media model needed
$media_model = $load->model('Media');
// get thumbnail file
$thumbnail = $media_model('thumbnail_file',['media' => $data['media']['id']]);
$data['media']['thumbnail'] = file_exists($thumbnail);

// output thumbnail if requested
if(!empty($_GET['thumbnail']))
{
    if(!file_exists($thumbnail))
    {
        http_response_code(404);
        die();
    }
    else
    {
        header('Content-Type: image/jpeg');
        readfile($thumbnail);
        die();
    }
}

// return information via JSON if requested as such.
if(!empty($_GET['json'])) { echo json_encode($data); die(); }

require('modules/now_playing/template.php');
