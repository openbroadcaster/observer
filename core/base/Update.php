<?php

namespace OpenBroadcaster\Base;

class Update
{
    protected $error;
    protected $db;
    protected $load;
    protected $models;

    public function __construct()
    {
        $this->error = false;
        $this->db = new \OpenBroadcaster\Support\DB();
        $this->load = \OpenBroadcaster\Support\Load::get_instance();
        $this->models = \OpenBroadcaster\Support\Models::get_instance();
    }
}
