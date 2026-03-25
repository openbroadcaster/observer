<?php

namespace OpenBroadcaster\Modules\Logger\Updates;

use OpenBroadcaster\Base\Update;

class Update20230828 extends Update
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