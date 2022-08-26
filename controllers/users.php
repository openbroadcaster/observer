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
 * Manages users more globally. Where the Account controller is used by a single
 * account, the Users controller is for administrating users in a more global way.
 *
 * @package Controller
 */
class Users extends OBFController
{
    public function __construct()
    {
        parent::__construct();
        $this->user->require_authenticated();
    }

    /**
     * Set user registration in the global settings. This is set to
     * 1 (or TRUE) when users can register from the login screen, or to 0 (FALSE)
     * if not. Requires 'manage_users' permission.
     *
     * @param user_registration
     *
     * @route PUT /v2/users/can_register
     */
    public function user_registration_set()
    {
        $this->user->require_permission('manage_users');

        $this->models->users('user_registration_set', $this->data('user_registration'));
        return array(true,'User registration set.');
    }

    /**
     * Return the user registration settings.
     *
     * @return user_registration
     *
     * @route GET /v2/users/can_register
     */
    public function user_registration_get()
    {
        return array(true,'User registration.',$this->models->users('user_registration_get'));
    }

    /**
     * Return a list of all users.
     *
     * @return [display_name, id, email]
     *
     * @route GET /v2/users/all
     */
    public function user_list()
    {
        $users = $this->models->users('user_list');
        return array(true,'User list.',$users);
    }

    /**
     * Return a sorted list of all users with more detailed information. Unlike
     * user_list(), this requires the 'manage_users' permission.
     *
     * @param sort_col Column to sort by.
     * @param sort_desc Whether or not to sort descendingly.
     *
     * @return users
     *
     * @route GET /v2/users
     */
    public function user_manage_list()
    {
        $this->user->require_permission('manage_users');

        $sort_col = $this->data('sort_col');
        $sort_desc = $this->data('sort_desc');

        // store sort settings for future display.
        if ($sort_col) {
            $this->models->users('user_manage_list_set_sort', $sort_col, $sort_desc);
        }

        // get sort settings (even if we already have them since the above will validate)
        $sort = $this->models->users('user_manage_list_get_sort');
        $sort_col = $sort[0];
        $sort_desc = $sort[1];

        $users = $this->models->users('user_manage_list', $sort_col, ($sort_desc ? 'desc' : 'asc'));
        return array(true,'User list.',[$users,$sort_col,$sort_desc]);
    }

    /**
     * Update or save a new user. Requires the 'manage_users' permission.
     *
     * @param id
     * @param name
     * @param username
     * @param email
     * @param display_name
     * @param enabled
     * @param password
     * @param password_confirm
     * @param group_ids
     * @param appkeys
     *
     * @route POST /v2/users
     */
    public function user_manage_addedit()
    {
        $this->user->require_permission('manage_users');

        $data = array();

        $data['name'] = trim($this->data('name'));
        $data['username'] = trim($this->data('username'));
        $data['email'] = trim($this->data('email'));
        $data['display_name'] = trim($this->data('display_name'));
        $data['enabled'] = trim($this->data('enabled'));

        $data['password'] = trim($this->data('password'));
        $data['password_confirm'] = trim($this->data('password_confirm'));

        $id = trim($this->data('id'));

        $data['group_ids'] = $this->data('group_ids');

        $data['appkeys'] = $this->data('appkeys');

        $validation = $this->models->users('user_validate', $data, $id);
        if ($validation[0]==false) {
            return $validation;
        }

        $this->models->users('user_save', $data, $id);

        return array(true,'User has been saved.');
    }

    /**
     * Delete a user. Requires the 'manager_users' permission.
     *
     * @param id
     *
     * @route DELETE /v2/users/(:id:)
     */
    public function user_manage_delete()
    {
        $this->user->require_permission('manage_users');

        $id = $this->data('id');

        if (empty($id)) {
            return array(false,'Invalid User ID.');
        }

        $this->models->users('user_delete', $id);

        return array(true,'User deleted.');
    }

    /**
     * Generate a new App Key for the user. Done through the addedit modal, and
     * requires the 'manager_users' permission for that reason.
     *
     * @param id
     *
     * @return [id, name, key]
     *
     * @route POST /v2/users/appkeys
     */
    public function user_manage_key_new()
    {
        $this->user->require_permission('manage_users');

        $id = $this->data('id');
        if (empty($id)) {
            return array(false, 'Invalid user ID.');
        }

        $result = $this->models->users('user_manage_key_new', $id);
        if ($result) {
            return array(true, 'Created new user App Key.', $result);
        }

        return array(false, 'Failed to create new user App Key.');
    }

    /**
     * Delete an App Key associated with a user. Done through the addedit modal,
     * and requires the 'manager_users' permission for that reason.
     *
     * @param id
     * @param user_id
     *
     * @return is_deleted?
     *
     * @route DELETE /v2/users/appkeys/(:user_id:)/(:id:)
     */
    public function user_manage_key_delete()
    {
        $this->user->require_permission('manage_users');

        $id = $this->data('id');
        $user_id = $this->data('user_id');

        if (empty($id)) {
            return array(false, 'Invalid key ID.');
        }
        if (empty($user_id)) {
            return array(false, 'Invalid user ID.');
        }

        $result = $this->models->users('user_manage_key_delete', $id, $user_id);
        if ($result) {
            return array(true, 'Successfully deleted App Key.');
        }

        return array(false, 'Failed to delete App Key.');
    }

    /**
     * Loads all App Keys associated with a user. Done through the addedit modal,
     * and requires the 'manager_uesrs' permission for that reason.
     *
     * @param id
     *
     * @return appkeys
     *
     * @route GET /v2/users/appkeys/(:id:)
     */
    public function user_manage_key_load()
    {
        $this->user->require_permission('manage_users');

        $id = $this->data('id');
        if (empty($id)) {
            return array(false, 'Invalid user ID.');
        }

        $result = $this->models->users('user_manage_key_load', $id);
        return array(true, 'Successfully loaded App Keys', $result);
    }

    /**
     * List all groups. Requires 'manage_users' or 'manage_permissions' permissions
     * to show more than the basic set of permissions.
     *
     * @return groups
     *
     * @route GET /v2/users/groups
     */
    public function group_list()
    {
        $hide_permissions = !$this->user->check_permission('manage_users or manage_permissions');
        $groups = $this->models->users('group_list', $hide_permissions);
        return array(true,'Group list.',$groups);
    }


    /**
     * Delete a user permissions group. Requires 'manage_permissions' permission.
     *
     * @param id
     *
     * @route DELETE /v2/users/permissions/(:id:)
     */
    public function permissions_manage_delete()
    {
        $this->user->require_permission('manage_permissions');

        $id = trim($this->data('id'));

        if (!empty($id)) {
            $this->models->users('group_delete', $id);
        }

        return array(true,'Group deleted.');
    }

    /**
     * Edit or create a new user permissions group. Requires 'manage_permissions' permission.
     *
     * @param name
     * @param id
     * @param permissions
     *
     * @route POST /v2/users/permissions
     */
    public function permissions_manage_addedit()
    {
        $this->user->require_permission('manage_permissions');

        $data['name'] = trim($this->data('name'));
        $id = trim($this->data('id'));
        $data['permissions'] = $this->data('permissions');

        $validation = $this->models->users('group_validate', $data, $id);
        if ($validation[0]==false) {
            return $validation;
        }

        // proceed with add/edit.
        $this->models->users('group_save', $data, $id);

        return array(true,'Group saved.');
    }

    /**
     * List all permissions by category as well as the permissions linked to each
     * player. Requires 'manage_permissions' permission.
     *
     * @return permissions
     *
     * @route GET /v2/users/permissions
     */
    public function permissions_manage_list()
    {
        $this->user->require_permission('manage_permissions');
        $permissions = $this->models->users('permissions_list');
        return array(true,'Permisisons list.',$permissions);
    }
}
