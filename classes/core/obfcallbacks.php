<?php

// Copyright 2012-2024 OpenBroadcaster, Inc.
// SPDX-License-Identifier: AGPL-3.0-or-later

/**
 * Manages callbacks. Returns an OBFCallbackReturn when firing, which is also
 * defined in the same file.
 *
 * @package Class
 */
class OBFCallbacks
{
    private $callbacks;
    private $load;
    private $retvals;

    public function __construct()
    {
        $this->callbacks = [];
        $this->retvals = [];
    }

    /**
     * Create an instance of OBFCallbacks or return the already created instance.
     *
     * @return instance
     */
    public static function &get_instance()
    {
        static $instance;
        if (isset($instance)) {
            return $instance;
        }
        $instance = new OBFCallbacks();
        return $instance;
    }

    /**
     * Reset the return values for the associated hook.
     *
     * @param hook Hook string in Class.method format.
     */
    public function reset_retvals($hook)
    {
        if (!isset($this->retvals[$hook])) {
            return false;
        }
        $this->retvals[$hook] = [];
    }

    /**
     * Store the return values for the associated hook from the provided callback
     * and value.
     *
     * @param hook Hook string in Class.method format.
     * @param callback Callback string in Class.method format.
     * @param value Return values to store.
     */
    public function store_retval($hook, $callback, $value)
    {
        if (!isset($this->retvals[$hook])) {
            $this->retvals[$hook] = [];
        }
        $this->retvals[$hook][$callback] = $value;
    }

    /**
     * Get the return values from a hook.
     *
     * @param hook Hook string in Class.method format.
     *
     * @return retvals
     */
    public function get_retvals($hook)
    {
        if (!isset($this->retvals[$hook])) {
            return false;
        }
        return $this->retvals[$hook];
    }

    /**
     * Register callback hooks.
     *
     * Available positions: init (run before the controller); return (run after
     * the controller).
     *
     * @param callback Callback string in Class.method format.
     * @param hook Hook string in Class.method format.
     * @param position Position in the method the callback is run, e.g. 'return'.
     * @param weight Lower numbers are run first. Can be negative. Default 0.
     *
     */
    public function register_callback($callback, $hook, $position, $weight = 0)
    {

    /*
      callback: what to call back?  must be model or controller method... in "SomeModel.method" or "ControllerName.method" format.
      hook: what to hook into? Class.method.
      position: where in this method is our callback run?
      weight: lower numbers are run first.   can be negative.
      return: whether the callback should hijack the method's return (will prevent further execution of method or additional callbacks)

      standard positions for models (might not be available, others will be used too):

        init: found before the method.
        return: found before a data return.

      standard positions for controllers (these are always available, more might be available)

        controller callbacks are chained together with the last callback's return array provided to the next callback.
          non-callback controller methods are not provided with the previous return.

        init: run before the controller
        return: run after the controller
    */

        if (!isset($this->callbacks[$hook])) {
            $this->callbacks[$hook] = [];
        }
        if (!isset($this->callbacks[$hook][$position])) {
            $this->callbacks[$hook][$position] = [];
        }

        $cb = new stdClass();
        $cb->callback = $callback;
        $cb->hook = $hook;
        $cb->position = $position;
        $cb->weight = $weight;

        $this->callbacks[$hook][$position][] = $cb;

        usort($this->callbacks[$hook][$position], [$this,'callbacks_sort']);

        return true;
    }

    /**
     * Sort two callbacks by their weight. Returns -1 if a takes priority, 1 if
     * b takes priority.
     *
     * @param a Callback 1.
     * @param b Callback 2.
     *
     * @return -1 | 1
     */
    private function callbacks_sort($a, $b)
    {
        return ($a->weight < $b->weight) ? -1 : 1;
    }

    /**
     * Fire a callback. Returns a new instance of OBFCallbackReturn.
     *
     * @param hook Hook string in Class.method format.
     * @param position Position in the method the callback is run, e.g. 'return'.
     * @param args Reference to arguments. NULL by default.
     * @param data Reference to data. NULL by default.
     *
     * @return obfcallback_return
     */
    public function fire($hook, $position, &$args = null, &$data = null)
    {

    // get our OBFLoader.  (Loading in construct creates a loop/php-crash).
        if (empty($this->load)) {
            $this->load = OBFLoad::get_instance();
        }

        // return early if no registered callbacks.
        if (!isset($this->callbacks[$hook])) {
            return new OBFCallbackReturn();
        }
        if (!isset($this->callbacks[$hook][$position])) {
            return new OBFCallbackReturn();
        }

        foreach ($this->callbacks[$hook][$position] as $cb) {
            $cbname_explode = explode('.', $cb->callback);
            $callback_is_model = (strtolower(substr($cbname_explode[0], -5)) == 'model' ? true : false);

            if ($callback_is_model) {
                $model = $this->load->model(substr($cbname_explode[0], 0, -5));
                $cb_return = $model->{$cbname_explode[1]}($hook, $position, $args);
            } else {
                $controller = $this->load->controller($cbname_explode[0]);
                if ($data) {
                    $controller->data = &$data;
                }
                $cb_return = $controller->handle($cbname_explode[1], $hook, $position);
            }

            if (isset($cb_return->v)) {
                $this->store_retval($hook, $cb->callback, $cb_return->v);
            } else {
                $this->store_retval($hook, $cb->callback, null);
            }

            // callback is forcing an early return.
            if (!empty($cb_return) && $cb_return->r) {
                return $cb_return;
            }
        }

        return new OBFCallbackReturn();
    }
}

/**
 * OBFCallbackReturn instances are created when firing callbacks. It contains two
 * public variables, $r and $v. $r is the first argument passed when creating a
 * new instance, and $v is set to TRUE if more arguments have been passed to it,
 * FALSE otherwise.
 */
class OBFCallbackReturn
{
    public $r;
    public $v;

    public function __construct()
    {
        $args = func_get_args();

        if (isset($args[0])) {
            $this->v = $args[0];
        } else {
            $this->v = null;
        }

        if (isset($args[1]) && !empty($args[1])) {
            $this->r = true;
        } else {
            $this->r = false;
        }
    }
}
