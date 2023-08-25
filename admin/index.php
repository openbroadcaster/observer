<?php

/*
    Copyright 2012-2023 OpenBroadcaster, Inc.

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