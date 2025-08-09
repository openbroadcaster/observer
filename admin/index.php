<?php

// Copyright 2012-2024 OpenBroadcaster, Inc.
// SPDX-License-Identifier: AGPL-3.0-or-later

require_once __DIR__ . '/../config.php';

if (!isset($_SERVER['PHP_AUTH_USER']) || $_SERVER['PHP_AUTH_USER'] !== OB_UPDATES_USER || (! password_verify($_SERVER['PHP_AUTH_PW'], OB_UPDATES_PW))) {
    header('WWW-Authenticate: Basic realm="OpenBroadcaster Updates"');
    header('HTTP/1.0 401 Unauthorized');

    exit();
}

?>

<!DOCTYPE html>
<html>
<head>
    <title>OpenBroadcaster Admin</title>

    <link rel="stylesheet" href="style.css">
    <script src="script.js"></script>
</head>
<body>
    <main>
        <form>
            <input type="hidden" id="authUser" value="<?=$_SERVER['PHP_AUTH_USER']?>">
            <input type="hidden" id="authPass" value="<?=$_SERVER['PHP_AUTH_PW']?>">
        </form>
        <div id="cli-options">
            <button onclick="cliCheck()">Check installation for errors</button>
            <button onclick="cliCronRun()">Run scheduled tasks</button>
            <button onclick="cliUpdatesList()">List available updates</button>
            <button onclick="cliUpdatesRun()">Run available updates</button>
        </div>
        <div id="cli-output" class="ansi_color_bg_black"></div>
    </main>
</body>
</html>