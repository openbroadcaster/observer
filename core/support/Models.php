<?php

// Copyright 2012-2024 OpenBroadcaster, Inc.
// SPDX-License-Identifier: AGPL-3.0-or-later

/**
 * Models class. Provides access to all models.
 *
 * @package Class
 */
namespace OpenBroadcaster\Support;

class Models
{
    public $load;
    private $models;

    public function __construct()
    {
        $this->load = Load::get_instance();
        $this->models = new \stdClass();
    }

    // NOTE / TODO: Now that modules are separately namespaced, there is currently no way to use Models to call methods from models
    // in modules, since it uses Load under the hood, and has no way of passing on the second optional module name.
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

        $instance = new Models();

        return $instance;
    }
}
