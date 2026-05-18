<?php

namespace OpenBroadcaster\Routes;

class RouteFile
{
    public $name;
    public $dir;
    public $description;

    private $class;

    public function __construct($name, $dir, $description = [])
    {
        $this->name = $name;
        $this->dir  = $dir;
        $this->description = $description;

        $this->class = new RouteClass(null);
    }

    public function getClass(): RouteClass
    {
        return $this->class;
    }

    public function setClass(RouteClass $class)
    {
        $this->class = $class;
    }
}