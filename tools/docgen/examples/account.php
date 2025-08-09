<?php

// Copyright 2012-2024 OpenBroadcaster, Inc.
// SPDX-License-Identifier: AGPL-3.0-or-later

/**
 * Manages everything account related. Covers logging and logging out,
 * permissions, groups, account settings, creating new accounts, and recovering
 * passwords. Specifically to be used by individual accounts; for managing lists
 * of permissions, users, and groups, use the Users controller.
 *
 * @package Controller
 */
class Account extends OBFController
{
    public function __construct()
    {
        parent::__construct();

        $this->UsersModel = $this->load->model('Users');
        $this->PermissionsModel = $this->load->model('Permissions');
    }

    /**
     * Login using the provided username and password.
     *
     * @param username Test description for username parameter
     * @param password Test description for password parameter
     * @return [id,key,key_expiry]
     */
    public function login($somearg, $anotherarg, $weirdspacing, $defaultval=null)
    {
        $username = trim($this->data('username'));
        $password = trim($this->data('password'));

        $login = $this->user->login($username, $password);

        if ($login==false) {
            return array(false,'Login Failed');
        } elseif (is_array($login) && $login[0]===false) {
            return array(false,$login[1]);
        } else {
            return array(true,'Login Successful',$login[2]);
        }
    }

    /**
     * Return currently logged in username and user id.
     *
     * @return [id,username]
     */
    public function uid()
    {
        $data['id']=$this->user->param('id');
        $data['username']=$this->user->param('username');

        return array(true,'UID/Username',$data);
    }

    /**
     * Return currently logged in user permissions.
     *
     * @return [permission,...]
     */
    public function permissions()
    {
        $permissions = $this->PermissionsModel('get_user_permissions', $this->user->param('id'));
        //T Permissions
        return array(true,'Permissions',$permissions);

        /*
        $permissions = array();
        $permission_list = $this->db->get('users_permissions');

        foreach($permission_list as $check) $permissions[$check['name']]=$this->user->check_permission($check['name']);

        return array(true,'Permissions',$permissions);
        */
    }

    /**
     * Return currently logged in user groups.
     *
     * @return [group_name,...]
     */
    public function groups()
    {
        $groups = $this->PermissionsModel('get_user_groups', $this->user->param('id'));
        //T Groups
        return array(true,'Groups',$groups);
    }

    /**
     * Logout currently logged in user.
     *
     */
    public function logout()
    {
        $logout = $this->user->logout();

        //T Logged Out
        if ($logout) {
            return array(true,'Logged Out');
        } else {
            return array(false,'Unable to log out, an unknown error occurred.');
        }
    }

    /**
     * DocBlock description of the file somewhere in the middle of nowhere.
     */

    /**
     * Return userdata (except for sensitive information) for currently logged in
     * user.
     *
     * This description has multiple paragraphs.
     *
     * @return [user_field,...]
     *
     */
    public function settings()
    {
        $this->user->require_authenticated();

        $userdata = $this->user->userdata;
        unset($userdata['password']);
        unset($userdata['key']);
        unset($userdata['key_expiry']);
        unset($userdata['enabled']);

        return array(true,null,$userdata);
    }

    /**
     * Update currently logged in user settings.
     *
     * @param name
     * @param password
     * @param password_again
     * @param email
     * @param display_name
     * @param language
     * @param theme
     * @param dyslexia_friendly_font
     * @param sidebar_display_left
     */
    public function update_settings()
    {
        $this->user->require_authenticated();

        $user_id = $this->user->param('id');

        $data = array();
        $data['name'] = trim($this->data('name'));
        $data['password'] = trim($this->data('password'));
        $data['password_again'] = trim($this->data('password_again'));
        $data['email'] = trim($this->data('email'));
        $data['display_name'] = trim($this->data('display_name'));
        $data['language'] = trim($this->data('language'));
        $data['theme'] = trim($this->data('theme'));
        $data['dyslexia_friendly_font'] = trim($this->data('dyslexia_friendly_font'));
        $data['sidebar_display_left'] = trim($this->data('sidebar_display_left'));

        $validation = $this->UsersModel('settings_validate', $user_id, $data);

        if ($validation[0]==false) {
            return [false,$validation[1]];
        }

        $this->UsersModel('settings_update', $user_id, $data);

        //T Settings have been updated. User interface setting changes may require you refresh the application to take effect.
        return [true,'Settings have been updated. User interface setting changes may require you refresh the application to take effect.'];
    }

    /**
     * Send message to provided email to aid in recovering account with forgotten
     * password.
     *
     * @param email
     *
     */
    public function forgotpass()
    {
        $email = trim($this->data('email'));

        $validation = $this->UsersModel('forgotpass_validate', $email);

        if ($validation[0]==false) {
            return $validation;
        }

        $this->UsersModel('forgotpass_process', $email);

        return array(true,'A new password has been emailed to you.');
    }

    /**
     * Create a new account using the provided fields if user registration is
     * currently enabled, and all the fields are validated.
     *
     * @param name
     * @param email
     * @param username
     */
    public function newaccount()
    {
        if (!$this->UsersModel->user_registration_get()) {
            return array(false,'New account registration is currently disabled.');
        }

        $data = array();
        $data['name'] = trim($this->data('name'));
        $data['email'] = trim($this->data('email'));
        $data['username'] = trim($this->data('username'));

        $validation = $this->UsersModel('newaccount_validate', $data);
        if ($validation[0]==false) {
            return $validation;
        }

        $this->UsersModel('newaccount_process', $data);

        return array(true,'A new account has been created.  A randomly generated password has been emailed to you.');
    }
}
