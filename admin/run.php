<?php

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

switch ($json->command) {
    case 'check':
        $output = [];
        exec(__DIR__ . "/../tools/cli/ob check", $output);

        $output = $converter->convert(implode($output));

        echo json_encode([
            'message' => 'Running check command.',
            'result' => $output,
            'theme' => $theme->asCss()
        ]);

        exit();
        break;
    default:
        http_response_code(400);
        echo json_encode(['message' => 'Invalid command provided.']);

        exit();
}
