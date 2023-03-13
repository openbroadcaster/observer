<?php

/*
    Copyright 2012-2020 OpenBroadcaster, Inc.

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
 * Manages controllers. Makes frameworks available, adds associated controller
 * request handlers, and methods get called when calling methods on associated
 * models.
 *
 * @package Class
 */
class OBFController
{
    public $load;
    public $db;
    public $user;
    public $data;
    public $models;

    protected $helpers;
    protected $callback_handler;

    /**
     * Create a new instance of OBFController. Makes various classes of OB available
     * by default, such as OBFLoad, OBFDB, OBFUser, OBFCallbacks, and OBFHelpers.
     */
    public function __construct()
    {
        $this->load = OBFLoad::get_instance();
        $this->db = OBFDB::get_instance();
        $this->user = OBFUser::get_instance();
        $this->callback_handler = OBFCallbacks::get_instance();
        $this->helpers = OBFHelpers::get_instance();
        $this->models = OBFModels::get_instance();
    }

    /**
     * Shortcut to use $this->ModelName('method', arg1, arg2, ...).
     *
     * @param name Method name.
     * @param args Variable argument list.
     */
    public function __call($name, $args)
    {
        if (!isset($this->$name)) {
            $stack = debug_backtrace();
            trigger_error('Call to undefined method ' . $name . ' (' . $stack[0]['file'] . ':' . $stack[0]['line'] . ')', E_USER_ERROR);
        }

        $obj = $this->$name;

        return call_user_func_array($obj, $args);
    }

    /**
     * Default controller REQUEST handler. $action only used for direct call from
     * API. Other argument used when called as callback.
     *
     * @param action The controller method to be handled.
     * @param hook Optional hook when handled as callback. Default NULL.
     * @param position Optional position when handled as callback. Default NULL.
     */
    public function handle($action, $hook = null, $position = null)
    {
        if (method_exists($this, $action)) {
            if (!$hook) {
                // call as non-callback
                return $this->$action();
            } else {
                // call as callback
                return $this->$action($hook, $position);
            }
        }
    }

    /**
     * Grab an argument from the data variable when a controller is called from the
     * API.
     *
     * @param key
     *
     * @return value
     */
    public function data($key)
    {
        if (isset($this->data[$key]) && is_array($this->data)) {
            return $this->data[$key];
        } else {
            return false;
        }
    }

    /**
     * Get the API version used in the request.
     * 
     * @return version
     */
    public function api_version()
    {
        if (str_starts_with($_SERVER['REQUEST_URI'], '/api/v2/')) {
            return 2;
        } elseif (str_starts_with($_SERVER['REQUEST_URI'], '/api/v1/')) {
            return 1;
        } else {
            return false;
        }
    }

    /**
     * Get the request method used.
     */
    public function api_request_method()
    {
        return $_SERVER['REQUEST_METHOD'];
    }
}
