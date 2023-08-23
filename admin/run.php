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

require_once __DIR__ . '/../vendor/autoload.php';

use SensioLabs\AnsiConverter\AnsiToHtmlConverter;
use SensioLabs\AnsiConverter\Theme\SolarizedTheme;

$theme = new SolarizedTheme();
$converter = new AnsiToHtmlConverter($theme, false);

header('Content-type: application-json');

$json = json_decode(file_get_contents('php://input'));

if (! $json || ! isset($json->command)) {
    http_response_code(400);
    echo json_encode(['message' => 'Invalid JSON data provided or no command given.']);

    exit();
}

$validCommands = ['check', 'cron run', 'updates list', 'updates run'];
if (in_array($json->command, $validCommands)) {
    $output = [];
    $resultCode = 0;
    exec(__DIR__ . "/../tools/cli/ob {$json->command}", $output);

    $output = $converter->convert(implode($output));
    if ($output === "" && $resultCode === 0) {
        $output = $converter->convert('No output. Command ran successfully.');
    } elseif ($output === "") {
        $output = $converter->convert('An error occurred running the command but no output was provided.');
    }

    echo json_encode([
        'message' => "Running {$json->command} command.",
        'result' => $output,
        'theme' => $theme->asCss()
    ]);

    exit();
}

http_response_code(400);
echo json_encode(['message' => 'Invalid command provided.']);
