<?php

// Copyright 2012-2024 OpenBroadcaster, Inc.
// SPDX-License-Identifier: AGPL-3.0-or-later

/**
 * Models class. Provides access to all models.
 *
 * @package Class
 */
class OBFModels
{
    public $load;
    private $models;

    public function __construct()
    {
        $this->load = OBFLoad::get_instance();
        $this->models = new stdClass();
    }

    public function __call($name, $args)
    {
        if (!isset($this->models->$name)) {
            $model = $this->load->model($name);
            if (!$model) {
                $stack = debug_backtrace();
                trigger_error('Call to undefined model ' . $name . ' (' . $stack[0]['file'] . ':' . $stack[0]['line'] . ')', E_USER_ERROR);
                die();
            }

            $this->models->$name = $model;
        }

        return call_user_func_array($this->models->$name, $args);
    }

    public static function &get_instance()
    {
        static $instance;

        if (isset($instance)) {
            return $instance;
        }

        $instance = new OBFModels();

        return $instance;
    }
}
