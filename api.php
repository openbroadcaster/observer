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

require_once('components.php');

class OBFAPI
{
    private $load;
    private $user;
    private $io;
    private $callback_handler;
    private $routes;

    public function __construct()
    {
        if (str_starts_with($_SERVER['REQUEST_URI'], '/api/v2/')) {
            // we have routes for this request method? find the regex pattern to match with, and variables to extract.
            $routes = json_decode(file_get_contents('routes.json'));
            if ($routes && is_object($routes) && property_exists($routes, $_SERVER['REQUEST_METHOD'])) {
                $this->routes = $routes->{$_SERVER['REQUEST_METHOD']};
                foreach ($this->routes as &$route) {
                    $variables = [];
                    $route_parts = explode('/', preg_replace('/^\/api\/v2\//', '', $route[0]));
                    foreach ($route_parts as &$route_part) {
                        if (str_starts_with($route_part, '(:') && str_ends_with($route_part, ':)')) {
                            $variables[] = substr($route_part, 2, -2);
                            $route_part = '(\d+)';
                        } else {
                            $route_part = preg_quote($route_part, '/');
                        }
                    }
                    $route[3] = [
                        'pattern' => '/^\/api\/v2\/' . implode('\/', $route_parts) . '\/?$/',
                        'variables' => $variables
                    ];
                }
            }

            if ($this->routes) {
                if (preg_match('#^/api/v2/ping/?$#', $_SERVER['REQUEST_URI'])) {
                    echo json_encode('pong');
                    exit();
                }

                $matches = null;
                $found = false;
                unset($route);
                foreach ($this->routes as $route) {
                    if (preg_match($route[3]['pattern'], parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), $matches)) {
                        // request data for requests to v2 api
                        if (!isset($_POST['d']) && !empty($_SERVER['HTTP_AUTHORIZATION'])) {
                            // json body
                            if ($_SERVER['REQUEST_METHOD'] === 'GET') {
                                parse_str(parse_url($_SERVER['REQUEST_URI'], PHP_URL_QUERY), $_POST['d']);
                            } else {
                                $_POST['d'] = json_decode(file_get_contents('php://input'), true);
                            }
                        }

                        // we need our array of request data
                        if (!isset($_POST['d'])) {
                            $_POST['d'] = [];
                        }

                        // first match is entire endpoint, disregard.
                        array_shift($matches);

                        // any matches remaining should be matched up with variables
                        foreach ($matches as $index => $match) {
                            if (isset($route[3]['variables'][$index])) {
                                // if request data not an array, something is wrong.
                                if (is_array($_POST['d'])) {
                                    $_POST['d'][$route[3]['variables'][$index]] = $match;
                                }
                            }
                        }

                        // provided expected data for v1 behavior below
                        $_POST['d'] = json_encode($_POST['d']);
                        $_POST['c'] = $route[1];
                        $_POST['a'] = $route[2];

                        // found
                        $found = true;

                        // match found, don't need to look further.
                        break;
                    }
                }

                if (!$found) {
                    http_response_code(400);
                    header('Content-Type: text/plain');
                    echo 'Not found or invalid request type for this URL.';
                    exit();
                }
            } else {
                http_response_code(500);
                header('Content-Type: text/plain');
                echo 'API v2 not supported.';
                exit();
            }
        }

        // API v1 with rewrite mode
        if (str_starts_with($_SERVER['REQUEST_URI'], '/api/v1/')) {
            if (preg_match('#^/api/v1/ping/?$#', $_SERVER['REQUEST_URI'])) {
                echo json_encode('pong');
                exit();
            }

            $request = explode('/', substr($_SERVER['REQUEST_URI'], 8), 2);
            $_POST['c'] = $request[0] ?? null; // controller
            $_POST['a'] = $request[1] ?? null; // action/method
        }

        $this->io = OBFIO::get_instance();
        $this->load = OBFLoad::get_instance();
        $this->user = OBFUser::get_instance();
        $this->callback_handler = OBFCallbacks::get_instance();

        $auth_id = null;
        $auth_key = null;

        // we might get a post, or multi-post. standardize to multi-post.
        if (isset($_POST['m']) && is_array($_POST['m'])) {
            $requests = $_POST['m'];
        } elseif (isset($_POST['c']) && isset($_POST['a']) && isset($_POST['d'])) {
            $requests = array( array($_POST['c'],$_POST['a'],$_POST['d']) );
        } else {
            $this->io->error(OB_ERROR_BAD_POSTDATA);
            return;
        }

        // preliminary request validity check
        foreach ($requests as $request) {
            if (!is_array($request) || count($request) != 3) {
                $this->io->error(OB_ERROR_BAD_POSTDATA);
                return;
            }
        }

        // try to get an ID/key pair for user authorization.
        if (!empty($_POST['i']) && !empty($_POST['k'])) {
            $auth_id = $_POST['i'];
            $auth_key = $_POST['k'];
        }

        if (empty($_SERVER['HTTP_AUTHORIZATION']) && !isset($_POST['appkey'])) {
            // authorize our user (from post data, cookie data, whatever.)
            $this->user->auth($auth_id, $auth_key);
        } else {
            // appkey should be set in either POST appkey (for v1 API) or HTTP authorization
            // header (for v2); throw an error if invalid key from either of those (needs
            // to be done explicitly since above relies on controllers figuring out
            // permission variables aren't set internally).)
            $key = (isset($_POST['appkey']) ? 'Bearer ' . $_POST['appkey'] : $_SERVER['HTTP_AUTHORIZATION']);
            header('Content-Type: application/json');
            $valid = $this->user->auth_appkey($key, $requests);
            if (! $valid) {
                $this->io->error(OB_ERROR_DENIED);
                return;
            }
        }

        // make sure each request has a valid controller (not done above since auth required before controller load)
        foreach ($requests as $request) {
            if (!$this->load->controller($request[0])) {
                $this->io->error(OB_ERROR_BAD_POSTDATA);
                return;
            }
        }

        $responses = array();

        foreach ($requests as $request) {
            $null = null; // for passing by reference.

            $controller = $request[0];
            $action = $request[1];

            // load our controller.
            $this->controller = $this->load->controller($controller);
            $this->controller->data = json_decode($request[2], true, 512);

            // launch callbacks to be run before requested main process.
            // this is not passed to the main process (might be later if it turns out that would be useful...)
            $cb_name = get_class($this->controller) . '.' . $action; // get Cased contrller name (get_class)
            $this->callback_handler->reset_retvals($cb_name); // reset any retvals stored from last request.
            $cb_return = $this->callback_handler->fire($cb_name, 'init', $null, $this->controller->data);

            // do callbacks all main process to be run?
            if (empty($cb_return->r)) {
                // run main process.
                $output = $this->controller->handle($action);
                $this->callback_handler->store_retval($cb_name, $cb_name, $output);

                // launch callbacks to be run after requested main process.
                // callbacks can manipulate output here.
                $cb_return = $this->callback_handler->fire($cb_name, 'return', $null, $this->controller->data);

                // callback changes output.
                if (!empty($cb_return->r)) {
                    $output = $cb_return->v;
                }
            } else {
                // init callbacks requested an early return.
                $output = $cb_return->v;
            }

            // output our response from the controller.
            if (!isset($output[2])) {
                $output[2] = null;
            }
            // $this->io->output(array('status'=>$output[0],'msg'=>$output[1],'data'=>$output[2]));
            $responses[] = array('status' => $output[0],'msg' => $output[1],'data' => $output[2]);
        }

        // return first responce if we just had a single request. if multi-request, we return array of responses.
        if (!isset($_POST['m'])) {
            $this->io->output($responses[0]);
        } else {
            $this->io->output($responses);
        }
    }
}

$api = new OBFAPI();
