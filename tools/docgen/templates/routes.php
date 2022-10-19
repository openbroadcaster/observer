<div class="doc-file-name"><code>routes</code></div>
<div class="doc-route-container">
  <div class="doc-route-helpers">
    <h2>Routes</h2>
    <button onclick="routesExpandAll(this)" data-is-expanded="false">Expand All</button>
  </div>

<?php

echo recursive_route_details($routes);

function recursive_route_details($routes)
{
    if ($routes === array_filter($routes, 'is_string')) {
        $controller = $routes[0];
        $method     = $routes[1];

        return '<!--<div class="doc-route-connection"><code>' . $controller . '/' . $method . '</code></div>-->';
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
<script>
  function routesExpandAll(elem)
  {
      if (jQuery(elem).attr('data-is-expanded') === 'false') {
        jQuery(elem).attr('data-is-expanded', 'true').text('Collapse All');
        jQuery('details').attr('open', '');
      } else {
        jQuery(elem).attr('data-is-expanded', 'false').text('Expand All');
        jQuery('details').removeAttr('open');
      }
  }
</script>
