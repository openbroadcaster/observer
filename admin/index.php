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
</head>
<body>
    <main>
        <div id="cli-options">
            <button onclick="cliCheck()">Check installation for errors</button>
            <button onclick="cliCronRun()">Run scheduled tasks</button>
            <button onclick="cliUpdatesList()">List available updates</button>
            <button onclick="cliUpdatesRun()">Run available updates</button>
        </div>
        <div id="cli-output" class="ansi_color_bg_black"></div>
    </main>

    <style>
        .ansi_color_fg_black { color: #073642 }
        .ansi_color_bg_black { background-color: #073642 }
        .ansi_color_fg_red { color: #dc322f }
        .ansi_color_bg_red { background-color: #dc322f }
        .ansi_color_fg_green { color: #859900 }
        .ansi_color_bg_green { background-color: #859900 }
        .ansi_color_fg_yellow { color: #b58900 }
        .ansi_color_bg_yellow { background-color: #b58900 }
        .ansi_color_fg_blue { color: #268bd2 }
        .ansi_color_bg_blue { background-color: #268bd2 }
        .ansi_color_fg_magenta { color: #d33682 }
        .ansi_color_bg_magenta { background-color: #d33682 }
        .ansi_color_fg_cyan { color: #2aa198 }
        .ansi_color_bg_cyan { background-color: #2aa198 }
        .ansi_color_fg_white { color: #eee8d5 }
        .ansi_color_bg_white { background-color: #eee8d5 }
        .ansi_color_fg_brblack { color: #002b36 }
        .ansi_color_bg_brblack { background-color: #002b36 }
        .ansi_color_fg_brred { color: #cb4b16 }
        .ansi_color_bg_brred { background-color: #cb4b16 }
        .ansi_color_fg_brgreen { color: #586e75 }
        .ansi_color_bg_brgreen { background-color: #586e75 }
        .ansi_color_fg_bryellow { color: #657b83 }
        .ansi_color_bg_bryellow { background-color: #657b83 }
        .ansi_color_fg_brblue { color: #839496 }
        .ansi_color_bg_brblue { background-color: #839496 }
        .ansi_color_fg_brmagenta { color: #6c71c4 }
        .ansi_color_bg_brmagenta { background-color: #6c71c4 }
        .ansi_color_fg_brcyan { color: #93a1a1 }
        .ansi_color_bg_brcyan { background-color: #93a1a1 }
        .ansi_color_fg_brwhite { color: #fdf6e3 }
        .ansi_color_bg_brwhite { background-color: #fdf6e3 }        

        #cli-options {
            margin-bottom: 0.5rem;
        }

        #cli-output {
            width: 800px;
            height: 500px;
            overflow-y: scroll;
        }

        #cli-output p {
            white-space: pre-line;
            font-family: monospace;
            margin: 0;
            margin-bottom: 1rem;
        }

        #cli-output p.error {
            color: red;
        }

    </style>

    <script>
        async function run(data)
        {
            const json = JSON.stringify({
                authUser: "<?=$_SERVER['PHP_AUTH_USER']?>",
                authPass: "<?=$_SERVER['PHP_AUTH_PW']?>",
                ...data
            });

            const response = await fetch("/admin/run.php", {
                method: "POST",
                body: json
            });

            const output = document.querySelector("#cli-output");
            
            response.json().then((data) => {
                if (! response.ok) {
                    output.innerHTML += '<p class="error">' + data.message + '</p>';
                } else {
                    output.innerHTML += '<p>' + data.result + '</p>';
                }

                document.querySelector("#cli-output p:last-of-type").scrollIntoView({ behavior: "smooth" });
            });
        }

        async function cliCheck()
        {
            const data = {
                command: "check"
            };

            run(data);
        }

        async function cliCronRun()
        {
            const data = {
                command: "cron run"
            };

            run(data);
        }

        async function cliUpdatesList()
        {
            const data = {
                command: "updates list"
            };

            run(data);
        }

        async function cliUpdatesRun()
        {
            const data = {
                command: "updates run"
            };

            run(data);
        }
    </script>
</body>
</html>