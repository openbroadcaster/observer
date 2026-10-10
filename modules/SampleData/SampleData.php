<?php

// Copyright 2012-2026 OpenBroadcaster, Inc.
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace OpenBroadcaster\Modules\SampleData;

use OpenBroadcaster\Base\Module;

class SampleData extends Module
{
    public $name = 'SampleData v1.0';
    public $description = 'Seed sample data profiles into a fresh Observer instance.';

    public function callbacks()
    {
    }

    public function install()
    {
        $this->permission_enable('administration', 'import_sample_data', 'import sample data profiles into Observer');

        return true;
    }

    public function uninstall()
    {
        $this->permission_disable('import_sample_data');

        return true;
    }

    public function purge()
    {
        $this->permission_delete('import_sample_data');

        return true;
    }
}
