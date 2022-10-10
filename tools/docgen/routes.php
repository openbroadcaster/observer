<?php

// Check all routes for any duplicates, to make sure there is no ambiguity when
// accessing a specific URL with a specific HTTP method.
function routes_contain_duplicates(array $routes): bool
{
    $dupes = false;

    // Check all HTTP methods separately. Duplication is fine as long as they
    // use different methods.
    foreach ($routes as $method => $routes_for_method) {
        $route_urls = [];

        foreach ($routes_for_method as $route) {
            // Save all URLs, replacing any arguments with the _ARG_ wildcard, since
            // duplication can still happen with differently named parameters: it's the
            // number of parameters that matters.
            $route_urls[] = preg_replace("/(\(:)(.*?)(:\))/", "_ARG_", $route[0]);
        }

        if (count($route_urls) !== count(array_unique($route_urls))) {
            $dupes = true;

            echo "[E] Duplicate routes detected for " . $method . " method!\n";
            foreach (array_diff_assoc($route_urls, array_unique($route_urls)) as $dupe_route) {
                echo "\t[E] " . $dupe_route . "\n";
            }
        }
    }

    return $dupes;
}

// Get all routes sorted by endpoints instead of by method.
function routes_by_endpoint(array $routes): array
{
    $endpoints = [];
    foreach ($routes as $method => $method_routes) {
        foreach ($method_routes as $route) {
            $parts = explode('/', trim($route[0], '/'));
            build_endpoint($endpoints, $parts, $method, [$route[1], $route[2]]);
        }
    }

    return $endpoints;
}

function build_endpoint(&$endpoints, $parts_remaining, $method, $values)
{
    if ($parts_remaining == []) {
        $endpoints['/'][$method] = $values;
    } else {
        build_endpoint($endpoints[$parts_remaining[0]], array_slice($parts_remaining, 1), $method, $values);
    }
}
