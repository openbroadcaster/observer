<?php

namespace OpenBroadcaster\Modules\SampleData\Updates;

use OpenBroadcaster\Base\Update;

class Update20260505 extends Update
{
    public function items()
    {
        return ['Initial release of the Sample Data module.'];
    }

    public function run()
    {
        return true;
    }
}
