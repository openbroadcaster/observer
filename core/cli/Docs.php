<?php

namespace OpenBroadcaster\CLI;

use OpenBroadcaster\Base\CLI;

class Docs extends CLI
{
    protected array|string $help = [
        ['<target_dir>', 'generate documentation and store in target directory'],
    ];

    public function run(array $args): bool
    {
        if (count($args) < 1) {
            (new OBCLI())->help();
            return false;
        }

        $targetDir = $args[0];

        if (! is_dir($targetDir)) {
            mkdir($targetDir, 0777, true);
        }

        if (! is_writable($targetDir)) {
            echo "[E] Target directory isn't writable." . PHP_EOL;
            return false;
        }

        if (count(scandir($targetDir)) > 2) {
            echo "[E] Target directory isn't empty. Script won't overwrite previous documentation automatically." . PHP_EOL;
            return false;
        }

        $routes = new \OpenBroadcaster\Support\Routes();
        $result = $routes->genDocs($targetDir);

        return $result;
    }
}
