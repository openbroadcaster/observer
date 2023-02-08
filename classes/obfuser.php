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
 * User class. Manages user authorization, permissions, and settings.
 *
 * @package Class
 */
class OBFUser
{
    private $db;
    private $load;
    private $io;
    private $using_appkey = false;

    // begin with anonymous user.
    public $userdata = null;
    public $is_admin = false;

    /**
     * Create an instance of OBFUser. Make DB, IO, and Load classes available.
     */
    public function __construct()
    {
        $this->db = OBFDB::get_instance();
        $this->io = OBFIO::get_instance();
        $this->load = OBFLoad::get_instance();
    }

    /**
     * Create an instance of OBFUser or return the already created instance.
     *
     * @return instance
     */
    public static function &get_instance()
    {
        static $instance;

        if (isset($instance)) {
            return $instance;
        }

        $instance = new OBFUser();

        return $instance;
    }

    /**
     * Generate a random key.
     *
     * @return key
     */
    private function random_key()
    {
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';

        $key = '';

        for ($i = 1; $i < 64; $i++) {
            $key .= $chars[mt_rand(0, (strlen($chars) - 1))];
        }

        return $key;
    }

    /**
     * Hash a password.
     *
     * @param pass
     *
     * @return hash
     */
    public function password_hash($pass)
    {
        return password_hash($pass . OB_HASH_SALT, PASSWORD_DEFAULT);
    }

    /**
     * Verify a password against a hash.
     *
     * @param pass
     * @param hash
     *
     * @return is_matching
     */
    public function password_verify($pass, $hash)
    {
        // old (bad) hashing; fixed in later code + db update.
        $info = password_get_info($hash);
        if ($info['algo'] == 0) {
            return sha1(OB_HASH_SALT . $pass) == $hash;
        } else {
            // good hashing.
            return password_verify($pass . OB_HASH_SALT, $hash);
        }
    }

    /**
     * Logs in a user. Returns key if successful, FALSE otherwise. Loads userdata
     * into $this->userdata.
     *
     * @param user
     * @param pass
     *
     * @return key
     */
    public function login($user, $pass)
    {

    // check username / password.
        $this->db->where('username', $user);
        $this->db->where('enabled', 1);
        $result = $this->db->get_one('users');

        if ($result && $result['password'] == '') {
            return array(false,'Due to security updates, a password reset is required. Use "Forgot Password" to reset your password.');
        } elseif ($result && $this->password_verify($pass, $result['password'])) {
            // valid user and password verified?
            // if rehash required, do that and store in db.
            if (password_needs_rehash($result['password'], PASSWORD_DEFAULT)) {
                $new_hash = $this->password_hash($pass);
                $this->db->where('id', $result['id']);
                $this->db->update('users', array('password' => $new_hash));
            }

            // cache our userdata.
            $this->userdata = $result;

            // clear out expired sessions
            $this->db->where('user_id', $result['id']);
            $this->db->where('key_expiry', time(), '<');
            $this->db->delete('users_sessions');

            // generate random key, salted sha1 hash key, write hashed key to database.  set key expirey.
            $key = $this->random_key();
            $key_expiry = strtotime('+1 hour');
            /* $this->db->where('id',$result['id']);
            $this->db->update('users', array('key'=>$this->password_hash($key), 'key_expiry'=>$key_expiry) ); */
            $key_id = $this->db->insert('users_sessions', [
            'user_id'    => $result['id'],
            'key'        => $this->password_hash($key),
            'key_expiry' => $key_expiry
            ]);
            $this->userdata['key_id'] = $key_id;

            setcookie('ob_auth_id', $key_id, 0, '/', null, false, false);
            setcookie('ob_auth_key', $key, 0, '/', null, false, false);

            // return key data.
            return array(true,'Login successful.',array('id' => $key_id,'key' => $key, 'key_expiry' => $key_expiry));
        } else {
            return array(false,'The login or password you have provided is incorrect.');
        }
    }

    /**
     * Logs out the current user.
     */
    public function logout()
    {
        if ($this->param('id') == 0) {
            return true;
        }

        // remote key and expiry key in database.
        $this->db->where('id', $this->param('key_id'));
        $this->db->delete('users_sessions');

        // expire cookies in browser.
        setcookie('ob_auth_id', '', time() - 3600, null, null, false, true);
        setcookie('ob_auth_key', '', time() - 3600, null, null, false, true);

        return true;
    }

    /**
     * Figure out if the user is authenticated (i.e. logged in). Loads userdata
     * into $this->userdata. Also sets $this->is_admin to TRUE if the user has
     * access to the admin group.
     *
     * @param id
     * @param key
     *
     * @return is_auth
     */
    public function auth($id, $key)
    {

    // if anything missing, return false. didn't work.
        if (empty($id) || empty($key)) {
            return false;
        }

        // get salted sha1 hash of key, check database with key/user combo
        $this->db->where('id', $id);
        $this->db->where('key_expiry', time(), '>=');
        $key_results = $this->db->get('users_sessions');

        $valid_key = null;
        if ($key_results) {
            foreach ($key_results as $key_result) {
                if ($this->password_verify($key, $key_result['key'])) {
                    $valid_key = $key_result;
                    break;
                }
            }
        }

        // session exists and key match?
        if ($valid_key) {
            $this->db->where('id', $valid_key['user_id']);
            $result = $this->db->get_one('users');

            // cache our userdata.
            $this->userdata = $result;
            $this->userdata['key_id'] = $valid_key['id'];

            // add additional users settings
            $this->db->where('user_id', $result['id']);
            $settings = $this->db->get('users_settings');
            foreach ($settings as $setting) {
                $this->userdata[$setting['setting']] = $setting['value'];
            }

            // update key expirey, return id, key, key_expiry.
            $key_expiry = strtotime('+1 hour');
            $last_access = time();
            $this->db->where('id', $valid_key['id']);
            $this->db->update('users_sessions', [
            'key_expiry' => $key_expiry
            ]);
            $this->db->where('id', $result['id']);
            $this->db->update('users', [
            'last_access' => $last_access
            ]);

            // see if user is admin
            $this->db->where('user_id', $result['id']);
            $this->db->where('group_id', 1);
            if ($this->db->get_one('users_to_groups')) {
                $this->is_admin = true;
            }

            return true;
        }

        return false;
    }

    /**
     * Instead of using the regular authorization, authorize a user using an
     * App Key.
     *
     * @param appkey
     *
     * @return is_auth
     */
    public function auth_appkey($appkey, $requests)
    {
        // Make sure an App Key has been provided.
        if (empty(trim($appkey))) {
            return false;
        }

        // Make sure Authorization header uses the proper format
        [$bearer, $appkey] = [substr($appkey, 0, 7), substr($appkey, 7)];
        if ($bearer !== 'Bearer ') {
            return false;
        }

        $key_row = base64_decode(explode(':', $appkey)[0]);
        $key_val = explode(':', $appkey)[1];

        $this->db->where('id', $key_row);
        $result = $this->db->get_one('users_appkeys');
        $valid  = password_verify($key_val, $result['key'] ?? '');

        // see if we have permission for all requests
        if ($valid) {
       // Allow requests from remote locations
            header("Access-Control-Allow-Origin: *");

            $permissions = preg_split('/\r\n|\r|\n/', $result['permissions']);
            foreach ($requests as $request) {
                $controller = $request[0];
                $method = $request[1];

                if (!preg_match('/^[A-Z0-9_]+$/i', $controller) || !preg_match('/^[A-Z0-9_]+$/i', $method)) {
                    $valid = false;
                    break;
                }

                $request_valid = false;
                foreach ($permissions as $permission) {
                    if ($permission == $controller . '/' . $method) {
                        $request_valid = true;
                    }
                }

                if (!$request_valid) {
                    $valid = false;
                    break;
                }
            }
        }

        if ($valid) {
            $this->db->where('id', $result['user_id']);
            $user = $this->db->get_one('users');

            // Failed to find the user ID linked to the App Key.
            if (!$user) {
                return false;
            }

            $this->userdata = $user;

            $this->db->where('user_id', $user['id']);
            $settings = $this->db->get('users_settings');
            foreach ($settings as $setting) {
                $this->userdata[$setting['setting']] = $setting['value'];
            }

            $this->db->where('user_id', $user['id']);
            $this->db->where('group_id', 1);
            if ($this->db->get_one('users_to_groups')) {
                $this->is_admin = true;
            }

            // Update last_access in App Keys table.
            $this->db->where('id', $key_row);
            $this->db->update('users_appkeys', [
            'last_access' => time()
            ]);

            $this->using_appkey = true;

            return true;
        }

        // No valid App Key found or some other error occurred.
        return false;
    }

    /**
     * Get a parameter from the userdata. Returns FALSE if no user is logged in.
     *
     * @param param
     *
     * @return value
     */
    public function param($param)
    {
        if (empty($this->userdata)) {
            if ($param == 'id') {
                return 0;
            } else {
                // anonymous user ID.
                return false;
            }
        }

        if (isset($this->userdata[$param])) {
            return $this->userdata[$param];
        }
        return false;
    }

    /**
     * Function to put on top of controller methods that require authentication.
     * This throws an error and  kills the call if no user is logged in.
     */
    public function require_authenticated()
    {
        if ($this->param('id') == 0) {
            $this->io->error(OB_ERROR_DENIED);
            die();
        }
    }

    /**
     * Deny access if using an API key.
     */
    public function disallow_appkey()
    {
        if ($this->using_appkey) {
            $this->io->error(OB_ERROR_DENIED);
            die();
        }
    }

    /**
     * Check if the logged in user has a specific permission.
     *
     * @param permission
     *
     * @return has_permission
     */
    public function check_permission($permission)
    {
        if ($this->is_admin) {
            return true;
        }
        if (php_sapi_name() == 'cli') {
            return true;
        }

        $permissions = $this->load->model('Permissions');

        return $permissions('check_permission', $permission, $this->param('id'));
    }

    /**
     * Require a specific permission. Like require_authenticated(), but for a
     * specific permission, will throw an error and abort the call if the user
     * doesn't have it.
     *
     * @param permission
     */
    public function require_permission($permission)
    {
        if ($this->is_admin) {
            return true;
        }

        if ($this->check_permission($permission) === false) {
            $this->io->error(OB_ERROR_DENIED);
            die();
        }
    }

    /**
     * Get the current user's group IDs, return FALSE if no user is logged in.
     *
     * @return group_ids
     */
    public function get_group_ids()
    {
        if (!$this->param('id')) {
            return false;
        }

        $ids = [];

        $this->db->where('user_id', $this->param('id'));
        $rows = $this->db->get('users_to_groups');

        foreach ($rows as $row) {
            $ids[] = (int) $row['group_id'];
        }

        return $ids;
    }

    /**
     * Get a user setting by name, return FALSE if no user is logged in or the
     * setting can't be found.
     *
     * @param name
     *
     * @return setting
     */
    public function get_setting($name)
    {
        if (!$this->param('id')) {
            return false;
        }

        $this->db->where('user_id', $this->param('id'));
        $this->db->where('setting', $name);
        $setting = $this->db->get_one('users_settings');

        if (!$setting) {
            return false;
        } else {
            return $setting['value'];
        }
    }

    /**
     * Set a user setting. Returns FALSE if no user is logged in.
     *
     * @param name
     * @param value
     *
     * @return status
     */
    public function set_setting($name, $value)
    {
        if (!$this->param('id')) {
            return false;
        }

        $this->db->where('user_id', $this->param('id'));
        $this->db->where('setting', $name);
        if ($setting = $this->db->get_one('users_settings')) {
            $this->db->where('id', $setting['id']);
            $this->db->update('users_settings', ['value' => $value]);
        } else {
            $this->db->insert('users_settings', [
            'user_id' => $this->param('id'),
            'setting' => $name,
            'value' => $value
            ]);
        }

        return true;
    }
}
