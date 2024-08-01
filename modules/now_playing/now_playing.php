<?php

/*     
    Copyright 2012-2024 OpenBroadcaster, Inc.

    This file is part of OpenBroadcaster Server.

    OpenBroadcaster Server is free software: you can redistribute it and/or modify
    it under the terms of the GNU Affero General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    OpenBroadcaster Server is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU Affero General Public License for more details.

    You should have received a copy of the GNU Affero General Public License
    along with OpenBroadcaster Server.  If not, see <http://www.gnu.org/licenses/>.
*/

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
$thumbnail = OB_THUMBNAILS.'/'.$file_location[0].'/'.$file_location[1].'/'.$data['media']['id'].'.jpg';
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
