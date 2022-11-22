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
 * Manages users, registering new ones, returning and updating lists of user
 * information, user settings, and forgotten passwords.
 *
 * @package Model
 */
class UsersModel extends OBFModel
{
    /**
     * Set to TRUE or FALSE in the settings table depending on whether new users
     * can be registered.
     *
     * @param value Boolean determing whether new users can register.
     */
    public function user_registration_set($value)
    {
        // figure out if we have the setting row already
        $this->db->where('name', 'new_user_registration');
        $setting = $this->db->get_one('settings');

        $value = $value ? 1 : 0;

        if ($setting) {
            $this->db->where('name', 'new_user_registration');
            $this->db->update('settings', ['value' => $value]);
        } else {
            $this->db->insert('settings', ['name' => 'new_user_registration','value' => $value]);
        }

        return true;
    }

    /**
     * Return the user registration settings.
     *
     * @return can_register
     */
    public function user_registration_get()
    {
        $this->db->where('name', 'new_user_registration');
        $setting = $this->db->get_one('settings');

        // default is true/enabled if not set.
        if (!$setting || $setting['value'] == 1) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * List all users' display names, IDs, and associated email addresses.
     *
     * @return users
     */
    public function user_list()
    {
        $this->db->what('display_name');
        $this->db->what('id');
        $this->db->what('email');
        $rows = $this->db->get('users');

        return $rows;
    }

    /**
     * Return a filtered list of users and their associated groups.
     *
     * @param sort_col Column to sort users by. 'display_name' by default.
     * @param sort_dir Direction to sort. 'asc' by default.
     *
     * @return users
     */
    public function user_manage_list($sort_col = 'display_name', $sort_dir = 'asc')
    {
        $this->db->what('id');
        $this->db->what('name');
        $this->db->what('username');
        $this->db->what('email');
        $this->db->what('display_name');
        $this->db->what('enabled');
        $this->db->what('created');
        $this->db->what('last_access');

        $this->db->orderby($sort_col, $sort_dir);

        $rows = $this->db->get('users');

        // get user groups
        foreach ($rows as $index => $row) {
            $rows[$index]['groups'] = array();

            $this->db->where('user_id', $row['id']);
            $this->db->leftjoin('users_groups', 'users_to_groups.group_id', 'users_groups.id');
            $groups = $this->db->get('users_to_groups');

            foreach ($groups as $group) {
                $rows[$index]['groups'][] = array('id' => $group['id'],'name' => $group['name']);
            }
        }

        return $rows;
    }

    /**
     * Update settings table with sorting settings for users list.
     *
     * @param sort_col Column to sort users list by.
     * @param sort_dir Direction to sort users list.
     */
    public function user_manage_list_set_sort($sort_col, $sort_dir)
    {
        // make sure sort col is value.
        if (array_search($sort_col, array('display_name','email','created','last_access')) === false) {
            return false;
        }

        // force sort dir to be value
        $sort_dir = (bool) $sort_dir;

        $setting_value = json_encode([$sort_col,$sort_dir]);
        $this->user->set_setting('user_manage_list_sort', $setting_value);
        return true;
    }

    /**
     * Get the user list sorting settings from the settings table.
     *
     * @return [sort_col, sort_dir]
     */
    public function user_manage_list_get_sort()
    {
        $sort = $this->user->get_setting('user_manage_list_sort');
        if (!$sort) {
            return ['display_name',false];
        } else {
            return json_decode($sort);
        }
    }

    /**
     * Generate a new App Key for the provided user ID.
     *
     * @param id
     *
     * @return [id, name, key, created, last_access]
     */
    public function user_manage_key_new($id)
    {
        $name    = 'new key';
        $key     = base64_encode(openssl_random_pseudo_bytes(32));
        $created = time();

        $keyid = $this->db->insert('users_appkeys', [
        'user_id'     => $id,
        'name'        => $name,
        'key'         => password_hash($key, PASSWORD_DEFAULT),
        'created'     => $created,
        'last_access' => 0
        ]);

        $full_key = base64_encode($keyid) . ':' . $key;
        return [
        'id'          => $keyid,
        'name'        => $name,
        'key'         => $full_key,
        'created'     => $created,
        'last_access' => 0
        ];
    }

    /**
     * Validate App Key permisisons.
     *
     * @param id
     * @param permissions
     * @param user_id
     */
    public function user_manage_key_permissions_validate($id, $permissions, $user_id)
    {
        // make sure this user_id owns this app key.
        $this->db->where('user_id', $user_id);
        $this->db->where('id', $id);
        $appkey = $this->db->get_one('users_appkeys');
        if (empty($appkey)) {
            return [false, 'AppKey not found.'];
        }

        // make sure permissions are valid
        if ($permissions !== '') {
            $permissions = preg_split('/\r\n|\r|\n/', $permissions);
            foreach ($permissions as $permission) {
                $controller_method = explode('/', $permission);
                if (count($controller_method) != 2 || !preg_match('/^[A-Z0-9_]+$/i', $controller_method[0]) || !preg_match('/^[A-Z0-9_]+$/i', $controller_method[1])) {
                    return [false, 'One or more controller/methods are not valid.'];
                }
            }
        }

        // got this far, valid.
        return [true, 'Valid'];
    }

    /**
     * Save App Key permisisons (will validate first).
     *
     * @param id
     * @param permissions
     * @param user_id
     */
    public function user_manage_key_permissions_save($id, $permissions, $user_id)
    {
        $validation = $this->user_manage_key_permissions_validate($id, $permissions, $user_id);
        if ($validation[0] == false) {
            return $validation;
        }

        $this->db->where('id', $id);
        $this->db->update('users_appkeys', ['permissions' => $permissions]);

        return [true, 'Updated.'];
    }

    /**
     * Delete an App Key associated with a specified user.
     *
     * @param id
     * @param user_id
     *
     * @return is_deleted?
     */
    public function user_manage_key_delete($id, $user_id)
    {
        $this->db->where('id', $id);
        $this->db->where('user_id', $user_id);
        if (!$this->db->get_one('users_appkeys')) {
            return false;
        }

        $this->db->where('id', $id);
        $this->db->where('user_id', $user_id);
        $this->db->delete('users_appkeys');

        return true;
    }

    /**
     * Load all App Keys associated with a user.
     *
     * @param id
     *
     * @return appkeys
     */
    public function user_manage_key_load($id)
    {
        $this->db->where('user_id', $id);
        $this->db->what('id');
        $this->db->what('user_id');
        $this->db->what('name');
        $this->db->what('created');
        $this->db->what('last_access');
        $this->db->what('permissions');
        return $this->db->get('users_appkeys');
    }

    /**
     * Validate user fields before updating.
     *
     * @param data
     * @param id User ID. NULL by default if inserting a new user.
     *
     * @return [is_valid, msg]
     */
    public function user_validate($data, $id = null)
    {
        foreach ($data as $key => $value) {
            $$key = $value;
        }

        // basic validation
        //T One or more required fields were not filled.
        if (empty($name) || empty($email) || empty($username) || empty($display_name)) {
            return array(false, 'One or more required fields were not filled.');
        }

        //T One or more required fields were not filled.
        if (empty($id) && (empty($password) || empty($password_confirm))) {
            return array(false, 'One or more required fields were not filled.');
        }

        // email validation
        //T The email address you have provided is not valid.
        if (!PHPMailer\PHPMailer\PHPMailer::ValidateAddress($email)) {
            return array(false, 'The email address you have provided is not valid.');
        }

        // make sure email not in use
        $this->db->where('email', $email);
        if (!empty($id)) {
            $this->db->where('id', $id, '!=');
        }
        //T The email address you have provided is already in use by another account.
        if ($this->db->get_one('users')) {
            return array(false, 'The email address you have provided is already in use by another account.');
        }

        // make sure username not in use.
        $this->db->where('username', $username);
        if (!empty($id)) {
            $this->db->where('id', $id, '!=');
        }
        //T The username you have selected is already in use.
        if ($this->db->get_one('users')) {
            return array(false, 'The username you have selected is already in use.');
        }

        // make sure passwords match.
        //T The passwords do not match.
        if (!empty($password) && $password != $password_confirm) {
            return array(false, 'The passwords do not match.');
        }
        //T The password must be at least 6 characters.
        if (!empty($password) && strlen($password) < 6) {
            return array(false, 'The password must be at least 6 characters.');
        }

        foreach ($appkeys as $appkey) {
            // make sure all App Keys have a name
            //T App Key name cannot be empty.
            if (empty(trim($appkey[1]))) {
                return array(false, 'App Key name cannot be empty.');
            }

            // make sure all App Keys exist and are associated with the user being edited
            $this->db->where('id', $appkey[0]);
            $this->db->where('user_id', $id);
            //T Could not find one or more App Keys in database.
            if (!$this->db->get_one('users_appkeys')) {
                return array(false, 'Could not find one or more App Keys in database.');
            }
        }

        return array(true,'Valid');
    }

    /**
     * Udate or save a new user.
     *
     * @param data
     * @param id User ID. NULL by default if inserting a new user.
     */
    public function user_save($data, $id = null)
    {
        // add/edit now.
        $dbdata['name'] = $data['name'];
        $dbdata['username'] = $data['username'];
        $dbdata['email'] = $data['email'];
        $dbdata['display_name'] = $data['display_name'];
        $dbdata['enabled'] = $data['enabled'];
        if (!empty($data['password'])) {
            $dbdata['password'] = $this->user->password_hash($data['password']);
        }

        if (!empty($id)) {
            $this->db->where('id', $id);
            $this->db->update('users', $dbdata);
        } else {
            $dbdata['created'] = time();
            $insert_id = $this->db->insert('users', $dbdata);
        }

        // handle groups
        if (!empty($id)) {
            $this->db->where('user_id', $id);
            $this->db->delete('users_to_groups');
        } else {
            $id = $insert_id;
        }

        $group_data = array();
        $group_data['user_id'] = $id;

        foreach ($data['group_ids'] as $group_id) {
            $group_data['group_id'] = $group_id;
            $this->db->insert('users_to_groups', $group_data);
        }

        foreach ($data['appkeys'] as $appkey) {
            $this->db->where('id', $appkey[0]);
            $this->db->update('users_appkeys', [
            'name' => $appkey[1]
            ]);
        }

        return true;
    }

    /**
     * Delete a user.
     *
     * @param id
     */
    public function user_delete($id)
    {
        $this->db->where('id', $id);
        $this->db->delete('users');

        return true;
    }

    /**
     * Get all groups.
     *
     * @param hide_permissions By default this gets all of a groups associated permissions. Set to TRUE to hide these.
     *
     * @return groups
     */
    public function group_list($hide_permissions = false)
    {
        $groups = $this->db->get('users_groups');

        if (!$hide_permissions) {
            foreach ($groups as $index => $group) {
                if ($group['id'] == 1) {
                    continue;
                } // administrator has all permissions and not found in table.

                $groups[$index]['permissions'] = array();

                $this->db->what('users_permissions.name');
                $this->db->what('users_permissions.category');
                $this->db->what('users_permissions_to_groups.item_id');

                $this->db->where('group_id', $group['id']);

                $this->db->leftjoin('users_permissions', 'users_permissions_to_groups.permission_id', 'users_permissions.id');

                $permissions = $this->db->get('users_permissions_to_groups');

                foreach ($permissions as $permission) {
                    $groups[$index]['permissions'][] = $permission['name'] . ($permission['item_id'] ? ':' . $permission['item_id'] : '');
                }
            }
        }

        return $groups;
    }

    /**
     * Get all users permissions as well as the ones linked to each player.
     *
     * @return permissions
     */
    public function permissions_list()
    {
        $this->db->orderby('category');
        $permissions = $this->db->get('users_permissions');

        $return = array();

        $players = $this->models->players('get_all');

        foreach ($permissions as $permission) {
            if ($permission['category'] == 'player') {
                foreach ($players as $player) {
                    if (!isset($return['player: ' . $player['name']])) {
                        $return['player: ' . $player['name']] = array();
                    }

                    $new_permission = $permission;
                    $new_permission['name'] .= ':' . $player['id'];

                    $return['player: ' . $player['name']][] = $new_permission;
                }
                continue;
            }

            if (!isset($return[$permission['category']])) {
                $return[$permission['category']] = array();
            }
            $return[$permission['category']][] = $permission;
        }

        return $return;
    }


    /**
     * Delete a group.
     *
     * @param id
     */
    public function group_delete($id)
    {
        $this->db->where('id', $id);
        $this->db->delete('users_groups');

        $this->db->where('group_id', $id);
        $this->db->delete('users_permissions_to_groups');

        $this->db->where('group_id', $id);
        $this->db->delete('users_to_groups');

        return true;
    }

    /**
     * Validate a group before updating.
     *
     * @param data
     * @param id Group ID. NULL by default when inserting a new group.
     *
     * @return [is_valid, msg]
     */
    public function group_validate($data, $id = null)
    {
        foreach ($data as $key => $value) {
            $$key = $value;
        }

        // we require a name
        //T A group name is required.
        if ($name == '') {
            return array(false,['Permissions Edit','A group name is required.']);
        }

        // we require valid permissions
        //T One or more permissions is invalid.
        if (!is_array($permissions)) {
            return array(false,['Permissions Edit','One or more permissions is invalid.']);
        }

        foreach ($permissions as $pname) {
            $pname_array = explode(':', $pname);
            $this->db->where('name', $pname_array[0]);
            //T One or more permissions is invalid.
            if (!$this->db->get_one('users_permissions')) {
                return array(false,['Permissions Edit','One or more permissions is invalid.']);
            }
        }

        // we can't edit the admin group
        //T You cannot edit or delete the administrator group.
        if ($id == 1) {
            return array(false,['Permissions Edit','You cannot edit or delete the administrator group.']);
        }

        return array(true,'Valid.');
    }

    /**
     * Save or insert a group.
     *
     * @param data
     * @param id Group ID. NULL by default when inserting a new group.
     */
    public function group_save($data, $id = null)
    {
        $dbdata['name'] = $data['name'];

        if (!empty($id)) {
            $this->db->where('id', $id);
            $this->db->update('users_groups', $dbdata);
        } else {
            $id = $this->db->insert('users_groups', $dbdata);
        }

        if (empty($id)) {
            return false;
        }

        // handle our permissions.  first deleting existing permissions for this group, then adding new permissions.
        $this->db->where('group_id', $id);
        $this->db->delete('users_permissions_to_groups');

        $pdata = array();
        $pdata['group_id'] = $id;
        // $pdata['value']='true';

        foreach ($data['permissions'] as $pname) {
            $pname_array = explode(':', $pname);

            $this->db->where('name', $pname_array[0]);
            $permission_info = $this->db->get_one('users_permissions');
            if (!$permission_info) {
                continue;
            }

            $pdata['permission_id'] = $permission_info['id'];

            // is permission associated with an item id? (like a player id specific permission...)
            if (count($pname_array) > 1) {
                $pdata['item_id'] = $pname_array[1];
            } else {
                $pdata['item_id'] = null;
            }

            $this->db->insert('users_permissions_to_groups', $pdata);
        }

        return true;
    }

    /**
     * Validate user settings.
     *
     * @param user_id
     * @param data
     *
     * @return [is_valid, msg]
     */
    public function settings_validate($user_id, $data)
    {

    //T One or more required fields were not filled.
        if (empty($data['name']) || empty($data['email']) || empty($data['display_name'])) {
            return array(false,'One or more required fields were not filled.');
        }
        //T The email address you have provided is not valid.
        if (!PHPMailer\PHPMailer\PHPMailer::ValidateAddress($data['email'])) {
            return array(false,'The email address you have provided is not valid.');
        }

        // make sure email not in use
        $this->db->where('id', $user_id, '!=');
        $this->db->where('email', $data['email']);
        //T The email address you have provided is already in use by another account.
        if ($this->db->get_one('users')) {
            return array(false,'The email address you have provided is already in use by another account.');
        }

        // verify password.
        if (isset($data['password']) && $data['password'] != '') {
            //T The passwords you have provided do not match.
            if ($data['password'] != $data['password_again']) {
                return array(false,'The passwords you have provided do not match.');
            } elseif (strlen($data['password']) < 6) {
                //T Your password must be at least 6 characters long.
                return array(false,'Your password must be at least 6 characters long.');
            }
        }

        // make sure language is valid
        $languages = $this->models->ui('get_languages');
        $language_codes = [];
        foreach ($languages as $language) {
            $language_codes[] = $language['code'];
        }

        //T The language selected is not valid.
        if ($data['language'] !== '' && array_search($data['language'], $language_codes) === false) {
            return array(false,'The language selected is not valid.');
        }

        // make sure theme is valid
        $themes = array_keys($this->models->ui('get_themes'));
        //T The theme selected is not valid.
        if (array_search($data['theme'], $themes) === false) {
            return array(false,'The theme selected is not valid.');
        }

        return array(true,'');
    }

    /**
     * Update user settings.
     *
     * @param user_id
     * @param data
     */
    public function settings_update($user_id, $data)
    {
        if (isset($data['password']) && $data['password'] != '') {
            $data['password'] = $this->user->password_hash($data['password']);
        } elseif (isset($data['password'])) {
            unset($data['password']);
        }

        if (isset($data['password_again'])) {
            unset($data['password_again']);
        }

        $settings = array();
        $settings['language'] = $data['language'];
        $settings['theme'] = $data['theme'];
        $settings['dyslexia_friendly_font'] = !empty($data['dyslexia_friendly_font']) ? 1 : 0;
        $settings['sidebar_display_left'] = !empty($data['sidebar_display_left']) ? 1 : 0;
        unset($data['language']);
        unset($data['theme']);
        unset($data['dyslexia_friendly_font']);
        unset($data['sidebar_display_left']);

        foreach ($data['appkeys'] as $appkey) {
            $this->db->where('id', $appkey[0]);
            $this->db->update('users_appkeys', [
            'name' => $appkey[1]
            ]);
        }
        unset($data['appkeys']);

        $this->db->where('id', $user_id);
        $this->db->update('users', $data);

        foreach ($settings as $setting => $value) {
            $this->db->where('user_id', $user_id);
            $this->db->where('setting', $setting);
            $this->db->delete('users_settings');

            $data = array();
            $data['user_id'] = $user_id;
            $data['setting'] = $setting;
            $data['value'] = $value;
            $this->db->insert('users_settings', $data);

            /*
            $this->db->query('INSERT INTO users_settings (user_id, setting, value)
              VALUES (
                "'.$this->db->escape($user_id).'",
                "'.$this->db->escape($setting).'",
                "'.$this->db->escape($value).'"
              ) ON DUPLICATE KEY UPDATE value = "'.$this->db->escape($value).'"');
            */
        }
    }

    /**
     * Validate an email address used in the forgotten password form.
     *
     * @param email
     *
     * @return [is_valid, msg]
     */
    public function forgotpass_validate($email)
    {
        if (!$email) {
            return array(false,'Email address is required.');
        }

        $this->db->where('email', $email);
        $user = $this->db->get_one('users');

        if (!$user) {
            return array(false,'The email address you have provided was not found.');
        }

        return array(true,'');
    }

    /**
     * Start the forgotten pass process for the provided email.
     *
     * @param email
     */
    public function forgotpass_process($email)
    {
        $password = $this->randpass();

        $password_hash = $this->user->password_hash($password);

        $this->db->where('email', $email);
        $user = $this->db->get_one('users');

        $this->db->where('id', $user['id']);
        $this->db->update('users', array('password' => $password_hash));

        $this('email_username_password', $email, $user['username'], $password);
    }

    /**
     * Validate data (name, email, and username) provided for a new account.
     *
     * @param data
     *
     * @return [is_valid, msg]
     */
    public function newaccount_validate($data)
    {
        $name = $data['name'];
        $email = $data['email'];
        $username = $data['username'];

        // basic validation
        if (empty($name) || empty($email) || empty($username)) {
            return array(false,'One or more required fields were not filled.');
        }
        if (!PHPMailer\PHPMailer\PHPMailer::ValidateAddress($email)) {
            return array(false,'The email address you have provided is not valid.');
        }

        // make sure email not in use
        $this->db->where('email', $email);
        if ($this->db->get_one('users')) {
            return array(false,'The email address you have provided is already in use.  Use the <a href="javascript: account.forgotpass_window();">forgot password</a> function to get a new password.');
        }

        // make sure username not in use.
        $this->db->where('username', $username);
        if ($this->db->get_one('users')) {
            return array(false,'The username you have selected is already in use.');
        }

        return array(true,'');
    }

    /**
     * Create a new user account with the provided data, generating a random
     * password and sending a confirmation email.
     *
     * @param data
     */
    public function newaccount_process($data)
    {
        $password = $this->randpass();

        $data['password'] = $this->user->password_hash($password);
        $data['enabled'] = 1;
        $data['display_name'] = $data['username'];
        $data['created'] = time();

        $this->db->insert('users', $data);

        $this('email_username_password', $data['email'], $data['username'], $password);
    }

    /**
     * Generate a random password.
     *
     * @return password
     */
    public function randpass()
    {
        $password_chars = 'abcdefghijkmnopqrstuvwxyz23456789';
        $password = '';

        while (strlen($password) < 8) {
            $password .= $password_chars[rand(0, (strlen($password_chars) - 1))];
        }

        return $password;
    }

    /**
     * Email temporary username and password to email address.
     *
     * @param email
     * @param username
     * @param password
     */
    public function email_username_password($email, $username, $password)
    {
        $mailer = new PHPMailer\PHPMailer\PHPMailer();

        if (defined('OB_EMAIL_HOST') && defined('OB_EMAIL_USER') && defined('OB_EMAIL_PASS') && defined('OB_EMAIL_TYPE') && defined('OB_EMAIL_PORT')) {
            // WRFL custom code for SMTP email
            require_once('extras/PHPMailer/src/SMTP.php');
            $mailer->isSMTP();
            $mailer->Host = OB_EMAIL_HOST;
            $mailer->SMTPAuth = true;
            $mailer->Username = OB_EMAIL_USER;
            $mailer->Password = OB_EMAIL_PASS;
            $mailer->SMTPSecure = OB_EMAIL_TYPE;
            $mailer->Port = OB_EMAIL_PORT;
        }

        $mailer->Body = 'Here is your username and new password for OpenBroadcaster.  You should immediately log in and reset your password.

Username: ' . $username . '
Password: ' . $password . '

Login at ' . OB_SITE;

        $mailer->From = OB_EMAIL_REPLY;

        $mailer->FromName = OB_EMAIL_FROM;

        $mailer->Subject = 'Your OpenBroadcaster Account';

        $mailer->AddAddress($email);

        $mailer->Send();
    }

    /**
     * Get a user by ID.
     *
     * @param id
     *
     * @return user
     */
    public function get_by_id($id)
    {
        $this->db->where('users.id', $id);
        return $this->db->get_one('users');
    }
}
