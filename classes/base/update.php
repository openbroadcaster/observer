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
        $this->db = new \OBFDB();
        $this->load = \OBFLoad::get_instance();
        $this->models = \OBFModels::get_instance();
    }
}
