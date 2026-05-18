<?php

namespace OpenBroadcaster\Routes;

class RouteMethod
{
    public $name;
    public $description;
    public $visibility;
    public $param;
    public $return;
    public $route;
    public $args;
    public $hidden;

    public function __construct(
        $name,
        $description = [],
        $visibility = "public",
        $args = [],
        $param = [],
        $return = "",
        $routes = [],
        $hidden = [false, ""]
    ) {
        $this->name = $name;
        $this->description = $description;
        $this->visibility = $visibility;
        $this->args = $args;
        $this->param = $param;
        $this->return = $return;
        $this->routes = $routes;
        $this->hidden = $hidden;
    }
}
