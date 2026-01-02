<?php

// Copyright 2012-2024 OpenBroadcaster, Inc.
// SPDX-License-Identifier: AGPL-3.0-or-later

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
