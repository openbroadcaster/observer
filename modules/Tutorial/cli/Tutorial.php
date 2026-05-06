<?php

namespace OpenBroadcaster\CLI;

use OpenBroadcaster\Base\CLI;

class Tutorial extends CLI
{
     protected array|string $help = [
        ['<args>', 'module example, echoes arguments back to user as string'],
     ];

     public function run(array $args): bool
     {
        if (count($args) < 1) {
            (new OBCLI())->help();
            return false;
        }

        echo implode(' ', $args) . PHP_EOL;

        return true;
     }
}