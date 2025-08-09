<?php

// Copyright 2012-2024 OpenBroadcaster, Inc.
// SPDX-License-Identifier: AGPL-3.0-or-later

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config.php';

use SensioLabs\AnsiConverter\AnsiToHtmlConverter;
use SensioLabs\AnsiConverter\Theme\SolarizedTheme;

$theme = new SolarizedTheme();
$converter = new AnsiToHtmlConverter($theme, false);

header('Content-type: application-json');

$json = json_decode(file_get_contents('php://input'));

$authUser = $json->authUser ?? null;
$authPass = $json->authPass ?? null;
if ($authUser !== OB_UPDATES_USER || (! password_verify($authPass, OB_UPDATES_PW))) {
    http_response_code(401);
    echo json_encode(['message' => 'Invalid username or password provided for running commands.']);

    exit();
}

if (! $json || ! isset($json->command)) {
    http_response_code(400);
    echo json_encode(['message' => 'Invalid JSON data provided or no command given.']);

    exit();
}

$validCommands = ['check', 'cron run', 'updates list all', 'updates run all'];
if (in_array($json->command, $validCommands)) {
    $output = [];
    $resultCode = 0;
    exec(__DIR__ . "/../tools/cli/ob {$json->command}", $output);

    $output = $converter->convert(implode(PHP_EOL, $output));
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
