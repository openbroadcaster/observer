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

header('Content-type: audio/ogg');

if (!isset($_GET['t'])) {
    die();
}

$festival = [
  ['pipe','r'],
  ['pipe','w'],
  ['file','/dev/null','a']
];

$process = proc_open('text2wave | oggenc - -o -', $festival, $pipes);

fwrite($pipes[0], $_GET['t']);
fclose($pipes[0]);

echo stream_get_contents($pipes[1]);
fclose($pipes[1]);
