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
 * Model class. Defines basic OB model functionality.
 *
 * @package Class
 */
class OBFModel
{
  public $load;
  public $db;
  public $user;
  public $error;
  public $models;

  protected $helpers;
  protected $callback_handler;

  /**
   * Create an instance of OBFModel, make database (db) and base framwork (ob)
   * available.
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
   * @param name
   * @param args
   *
   * @return return_value
   */
  public function __call($name, $args)
  {
    if(!isset($this->$name))
    {
      $stack = debug_backtrace();
      trigger_error('Call to undefined method '.$name.' ('.$stack[0]['file'].':'.$stack[0]['line'].')', E_USER_ERROR);
    }

    $obj = $this->$name;

    return call_user_func_array($obj, $args);
  }

  /**
   * Invoke is used for all method calls in order to handle callbacks appropriately.
   */
  public function __invoke()
  {

    // copied/modified from: http://drupal.org/node/353494 ... this is ugly.
    // hopefully the future will bring a more elegant solution.
    $stack = debug_backtrace();
    $args = array();
    $eval_args = array();

    // make sure our method name is specified.  determine our method name.
    if(!isset($stack[0]['args']) || count($stack[0]['args'])<1) return;
    $method = $stack[0]['args'][0];

    // make sure method exists.
    if(!method_exists($this, $method)) return;

    // get our args by reference.
    if(count($stack[0]['args'])>1){
      for($i=1; $i < count($stack[0]["args"]); $i++){
        $args[] = &$stack[0]["args"][$i];
        $eval_args[] = '$args['.($i-1).']';
      }
    }

    $eval_args = implode(',', $eval_args);

    // call our 'init' callbacks.
    $retval = null;
    $cb_name = get_class($this).'.'.$method;
    $cb_return = $this->callback_handler->fire($cb_name, 'init', $args);

    // a callback is forcing an early return.
    if(!empty($cb_return->r)) return $cb_return->v;

    // call our requested method
    eval('$retval = $this->$method('.$eval_args.');');
    $this->callback_handler->store_retval($cb_name, $cb_name, $retval);

    // call our 'return' callbacks;
    $cb_return = $this->callback_handler->fire($cb_name, 'return', $args);

    // reset our return value list for this process chain
    $this->callback_handler->reset_retvals($cb_name);

    // a callback is forcing an early return (taking over return value)
    if(!empty($cb_return->r)) return $cb_return->v;

    return $retval;

  }

  /**
   * Get or set error.  If error is set to NULL (the default value), this will
   * return the error . If error is set to FALSE, it'll reset the error.
   *
   * @param error
   *
   * @return error
   */
  public function error($error=null) {
    if($error===null) return $this->error;
    else $this->error=$error;
  }

}
