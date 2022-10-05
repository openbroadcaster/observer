<div class="doc-file-name"><code>routes</code></div>
<div class="doc-class">
  <h2>Routes</h2>

<?php

echo recursive_route_details($routes);

function recursive_route_details($routes)
{
    if ($routes === array_filter($routes, 'is_string')) {
        $controller = $routes[0];
        $method     = $routes[1];

        return '<div class="doc-route-connection"><code>' . $controller . '/' . $method . '</code></div>';
    } elseif (in_array(array_keys($routes)[0], ['GET', 'POST', 'DELETE', 'PATCH', 'PUT'])) {
        $result = '';
        foreach ($routes as $method => $connections) {
            $result .= '<div class="doc-route-details"><code class="doc-route-method doc-route-method-' . $method . '">' . $method . '</code>' . recursive_route_details($connections) . '</div>';
        }
        return $result;
    } else {
        $result = '';
        foreach ($routes as $routes_key => $sub_routes) {
            if ($routes_key === '/') {
                $result .= '<div class="doc-route-body">' . recursive_route_details($sub_routes) . '</div>';
                continue;
            }

            $open = (count($routes) == 1) ? 'open' : '';
            $result .= '<details ' . $open . ' class="doc-route"><summary><code>' . $routes_key . '</code></summary>'
                     . recursive_route_details($sub_routes)
                     . '</details>';
        }
        return $result;
    }
}

?>

</div>
