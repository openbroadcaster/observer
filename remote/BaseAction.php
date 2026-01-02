<?php

namespace OpenBroadcaster\Remote;

abstract class BaseAction
{
    protected $player;
    protected $request;
    protected $error;

    protected $io;
    protected $load;
    protected $user;
    protected $db;

    public function __construct(object $player, object $request)
    {
        $this->io = \OBFIO::get_instance();
        $this->load = \OBFLoad::get_instance();
        $this->user = \OBFUser::get_instance();
        $this->db = \OBFDB::get_instance();

        $this->player = $player;
        $this->request = $request;
    }

    // return error from last execute
    public function error(): string
    {
        return $this->error;
    }

    // run the action
    // return false on failure
    // return true on success with no data to output
    // return object on success with data to output
    abstract public function run(): bool|object;

    // shortcut to use $this->ModelName('method',arg1,arg2,...).
    public function __call($name, $args)
    {
        if (!isset($this->$name)) {
            $stack = debug_backtrace();
            trigger_error('Call to undefined method ' . $name . ' (' . $stack[0]['file'] . ':' . $stack[0]['line'] . ')', E_USER_ERROR);
        }

        $obj = $this->$name;

        return call_user_func_array($obj, $args);
    }
}
