<?php

namespace OpenBroadcaster\Routes;

class RouteClass
{
    public $name;
    public $description;
    public $package;
    public $module;

    private $methods;

    public function __construct($name, $description = [], $package = "NoPak", $module = null)
    {
        $this->name = $name;
        $this->description = $description;
        $this->package = $package;
        $this->module = $module;

        $this->methods = array();
    }

    public function getMethods()
    {
        return $this->methods;
    }

    public function addMethod(RouteMethod $method)
    {
        $this->methods[] = $method;
    }

    public function sort()
    {
        // Sort methods alphabetically first.
        usort($this->methods, fn($a, $b) => strcmp($a->name, $b->name));

        // Sort by visible/hidden methods.
        usort($this->methods, function ($a, $b) {
            if ($a->hidden[0] === $b->hidden[0]) {
                return 0;
            } elseif ($a->hidden[0] === true) {
                return 1;
            } else {
                return -1;
            }
        });

        return $this;
    }
}
