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
?>

<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="utf-8" />
	<title>Now Playing!</title>
	<link rel="stylesheet" href="now_playing.css" type="text/css" />
	<script type="text/javascript" src="https://ajax.googleapis.com/ajax/libs/jquery/1.7.1/jquery.min.js"></script>
	<script type="text/javascript" src="/modules/now_playing/now_playing.js"></script>
</head>

<body class="player-<?=$player['id']?>">

<?php 
$player_description = ($player['description'] ? $player['description'] : $player['name']);
?>

<?php if(empty($data['show_time_left']) || $data['show_time_left']<1 || empty($data['media']['time_left']) || $data['media']['time_left']<1) { ?>
<div id="now_playing" class="error">An error occurred while trying to determine what's playing.  Perhaps nothing is playing.</p>
<?php } else { ?>
<div id="now_playing_container">
<table id="now_playing">
<tr>
	<td colspan="2" id="now_playing_thumbnail"></td>
</tr>
<tr>
	<td class="label">Show:</td>
	<td>
			<span id="now_playing_show_name"><?=htmlspecialchars($data['show_name'])?></span>
			<span id="now_playing_show_countdown_container">(<span id="now_playing_show_countdown">time loading...</span>)</span>
	</td>
</tr>
<tr>
	<td class="label">Track:</td>
	<td>
		<span id="now_playing_track_name"><?=htmlspecialchars($data['media']['artist'].' - '.$data['media']['title'])?></span>
		<span id="now_playing_track_countdown_container">(<span id="now_playing_track_countdown">time loading...</span>)</span>
	</td>
</tr>
<tr class="powered_by">
	<td colspan="2">Powered by <a href="https://openbroadcaster.com/">OpenBroadcaster</a></td>
</tr>
</table>
</div>
<?php } ?>

</body>

</html>
