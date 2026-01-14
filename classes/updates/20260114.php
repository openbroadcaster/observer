<?php

namespace OpenBroadcaster\Classes\Updates;

use OpenBroadcaster\Classes\Base\Update;

class OBUpdate20260114 extends Update
{
    public function items()
    {
        return ['Dummy update to test new update namespacing and file locations.'];
    }

    public function run()
    {
        return true;
    }
}