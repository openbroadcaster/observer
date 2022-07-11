<?php

/*
    Copyright 2012-2020 OpenBroadcaster, Inc.

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

require('components.php');

$db = OBFDB::get_instance();

// set cron last run
$db->where('name', 'cron_last_run');
$cron_last_run = $db->get('settings');
if (!$cron_last_run) {
    $db->insert('settings', array('name'=>'cron_last_run', 'value'=>time()));
} else {
    $db->where('name', 'cron_last_run');
    $db->update('settings', array('value'=>time()));
}

// delete expired uploads
$db->where('expiry', time(), '<');
$uploads = $db->get('uploads');

foreach ($uploads as $upload) {
    unlink(OB_ASSETS.'/uploads/'.$upload['id']);
    $db->where('id', $upload['id']);
    $db->delete('uploads');
}

// remove cached schedule data for shows which stopped longer than 1 week ago (+/- some variablity due to timezones)
$db->query('DELETE FROM shows_cache where DATE_ADD(start, INTERVAL duration SECOND) < "'.date('Y-m-d H:i:s', strtotime('-1 week')).'"');

// send out player-last-connect warnings if appropriate. will not send out warnings if player has never connected.
// TODO maybe put this into a notice model for similar re-use elsewhere.
$cutoff = strtotime('-1 hour'); // connection must be made at least once/hour. this should be a setting at some point, maybe in player settings?
$connect_types = array('schedule','playlog','emergency');
foreach ($connect_types as $type) {
    $db->where('last_connect_'.$type, $cutoff, '<=');
    $players = $db->get('players');

    foreach ($players as $player) {
        $id = $player['id'];

        $db->where('player_id', $id);
        $db->where('event', 'player_last_connect_'.$type.'_warning');
        $db->where('toggled', 0);
        $notices = $db->get('notices');

        foreach ($notices as $notice) {
            $mailer = new PHPMailer\PHPMailer\PHPMailer();
            $mailer->Body='This is a warning that player "'.$player['name'].'" has not connected for "'.$type.'" in the last hour.

Please take steps to ensure this player is functioning properly.';
            $mailer->From=OB_EMAIL_REPLY;
            $mailer->FromName=OB_EMAIL_FROM;
            $mailer->Subject='Player Warning';
            $mailer->AddAddress($notice['email']);
            $mailer->Send();

            $db->where('id', $notice['id']);
            $db->update('notices', array('toggled'=>1));
        }
    }
}

// optimize tables if required
$db->query('show table status');
$tables = $db->assoc_list();
if (!empty($tables)) {
    foreach ($tables as $nfo) {
        if (empty($nfo['Data_free'])) {
            continue;
        }
        $fragmentation = $nfo['Data_free']*100 / $nfo['Data_length'];

        if ($fragmentation>10) {
            $db->query('OPTIMIZE TABLE `'.$nfo['Name'].'`');
        }
    }
}
