<?php

use OpenBroadcaster\Base\Update;

class LoggerUpdate20230828 extends Update
{
    public function items()
    {
        $updates = ['This is an example module update.'];

        return $updates;
    }

    public function run()
    {
        return true;
    }
}