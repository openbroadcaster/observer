<?php

/*
    Copyright 2012-2021 OpenBroadcaster, Inc.

    This file is part of OpenBroadcaster Server.

    OpenBroadcaster Server is free software: you can redistribute it and/or modify
    it under the terms of the GNU Affero General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    OpenBroadcaster Server is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU Affero General Public License for more details.

    You should have received a copy of the GNU Affero General Public License
    along with OpenBroadcaster Server.  If not, see <http://www.gnu.org/licenses/>.
*/

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
