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
 * Manages low-level API functionality, such as calls to controllers, logging in,
 * and uploads.
 *
 * @package Model
 */
class ApiModel extends OBFModel
{
    private $api_url;
    private $api_user;
    private $api_pass;

    private $api_auth_id;
    private $api_auth_key;

    /**
     * Set API URL, used in calls and uploads.
     *
     * @param url
     */
    public function set_url($args = [])
    {
        OBFHelpers::require_args($args, ['url']);

        $this->api_url = $args['url'];
    }

    /**
     * Set API user. Used in login call.
     *
     * @param user
     */
    public function set_user($args = [])
    {
        OBFHelpers::require_args($args, ['user']);

        $this->api_user = $args['user'];
    }

    /**
     * Set API password. Used in login call.
     *
     * @param pass
     */
    public function set_pass($args = [])
    {
        OBFHelpers::require_args($args, ['pass']);

        $this->api_pass = $args['pass'];
    }

    /**
     * Upload a file to the server. Requires being logged in with login function
     * defined in the API code, as well as an API URL being set to locate the
     * upload script.
     *
     * @param file
     *
     * @return curl_response
     */
    public function upload($args = [])
    {
        OBFHelpers::require_args($args, ['file']);

        // uploads can take a while...
        set_time_limit(3600);

        if (!$this->api_url) {
            return false;
        }

        // login required for file upload
        if (!$this->api_auth_id) {
            $login_response = $this->login();
            if ($login_response->status==false) {
                return $login_response;
            }
        }

        $ch = curl_init($this->api_url.'upload.php');

        // we have login information. provide as cookie.  (ob_auth_id, ob_auth_key)
        curl_setopt($ch, CURLOPT_COOKIE, 'ob_auth_id='.$this->api_auth_id.'; ob_auth_key='.$this->api_auth_key);

        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_PUT, true);
        curl_setopt($ch, CURLOPT_INFILE, fopen($args['file'], 'r'));
        curl_setopt($ch, CURLOPT_INFILESIZE, filesize($args['file']));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_LOW_SPEED_LIMIT, 512); // lower speed limit of 0.5KB/s
    curl_setopt($ch, CURLOPT_LOW_SPEED_TIME, 10); // cancels if going this slow for 10s or more.
    curl_setopt($ch, CURLOPT_HTTPHEADER, array("Expect:  "));

        $response = curl_exec($ch);
        curl_close($ch);

        return json_decode($response);
    }

    /**
     * Call a controller method, sending data along and potentially requiring
     * a user to be logged in.
     *
     * @param controller
     * @param action
     * @param data NULL by default.
     * @param login_required TRUE by default.
     *
     * @return json_response
     */
    public function call($args = [])
    {
        OBFHelpers::require_args($args, ['controller', 'action']);
        OBFHelpers::default_args($args, ['data' => null, 'login_required' => true]);

        if (!$this->api_url) {
            return false;
        }

        if ($args['login_required'] && !$this->api_auth_id) {
            $login_response = $this->login();
            if ($login_response->status==false) {
                return $login_response;
            }
        }

        $post = array();

        $post['c'] = $args['controller'];
        $post['a'] = $args['action'];
        $post['d'] = json_encode($args['data']);

        $post['i'] = $this->api_auth_id;
        $post['k'] = $this->api_auth_key;

        $ch = curl_init($this->api_url.'api.php');

        // we have login information. provide as cookie.  (ob_auth_id, ob_auth_key)
        if ($this->api_auth_id) {
            curl_setopt($ch, CURLOPT_COOKIE, 'ob_auth_id='.$this->api_auth_id.'; ob_auth_key='.$this->api_auth_key);
        }

        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $response = curl_exec($ch);
        curl_close($ch);

        return json_decode($response);
    }

    /**
     * Login with username and password set in this class instance. If login is
     * successful, set the auth ID and key in this instance, which can be then be
     * used by the other methods requiring a logged in user. Returns the response
     * from the Account controller's login method.
     *
     * @return response
     */
    public function login($args = [])
    {
        $response = $this->call(['controller' => 'account', 'action' => 'login', 'data' => ['username' => $this->api_user, 'password' => $this->api_pass], 'login_required' => false]);

        if ($response->status==true) {
            $this->api_auth_id = $response->data->id;
            $this->api_auth_key = $response->data->key;
        }

        return $response;
    }
}
