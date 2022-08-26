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
  }

  /**
   * Login using the provided username and password.
   *
   * @param username
   * @param password
   * @return [id, key, key_expiry]
   */
  public function login()
  {
    $username = trim($this->data('username'));
    $password = trim($this->data('password'));

    $login = $this->user->login($username,$password);

    if($login==false) return array(false,'Login Failed');
    elseif(is_array($login) && $login[0]===false) return array(false,$login[1]);

    else return array(true,'Login Successful',$login[2]);
  }

  /**
   * Return currently logged in username and user id.
   *
   * @return [id, username]
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
   * @return permission_array
   */
  public function permissions()
  {
    $this->user->require_authenticated();
    
    $permissions = $this->models->permissions('get_user_permissions',$this->user->param('id'));
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
   * @return group_names_array
   */
  public function groups()
  {
    $this->user->require_authenticated();
    
    $groups = $this->models->permissions('get_user_groups',$this->user->param('id'));
    //T Groups
    return array(true,'Groups',$groups);
  }

  /**
   * Logout currently logged in user.
   */
  public function logout()
  {
    $this->user->require_authenticated();
    $this->user->disallow_appkey();
    
    $logout = $this->user->logout();

    //T Logged Out
    if($logout) return array(true,'Logged Out');
    else return array(false,'Unable to log out, an unknown error occurred.');
  }

  /**
   * Return userdata (except for sensitive information) for currently logged in
   * user.
   *
   * @return user_fields_array
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
   * @param dyslexia_friendly_font Boolean set to TRUE for using a dyslexia-friendly font.
   * @param sidebar_display_left Boolean set to TRUE when displaying the sidebar on the left side.
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
    $data['appkeys'] = $this->data('appkeys');

    $validation = $this->models->users('settings_validate',$user_id,$data);

    if($validation[0]==false) return [false,$validation[1]];

    $this->models->users('settings_update',$user_id,$data);

    //T Settings have been updated. User interface setting changes may require you refresh the application to take effect.
    return [true,'Settings have been updated. User interface setting changes may require you refresh the application to take effect.'];
  }

  /**
   * Send message to provided email to aid in recovering account with forgotten
   * password.
   *
   * @param email
   */
  public function forgotpass()
  {
    $email = trim($this->data('email'));

    $validation = $this->models->users('forgotpass_validate',$email);

    if($validation[0]==false) return $validation;

    $this->models->users('forgotpass_process',$email);

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
    if(!$this->models->users('user_registration_get'))
    {
      return array(false,'New account registration is currently disabled.');
    }

    $data = array();
    $data['name'] = trim($this->data('name'));
    $data['email'] = trim($this->data('email'));
    $data['username'] = trim($this->data('username'));

    $validation = $this->models->users('newaccount_validate',$data);
    if($validation[0]==false) return $validation;

    $this->models->users('newaccount_process',$data);

    return array(true,'A new account has been created.  A randomly generated password has been emailed to you.');
  }

  /**
   * Generate a new App Key for the logged in user. Requires the 'manage_appkeys'
   * permission. Connects to the users model.
   *
   * @return [id, name, key]
   */
   public function key_new () {
     $this->user->require_permission('manage_appkeys');

     $id = $this->user->param('id');
     if (empty($id)) return array(false, 'Invalid user ID.');

     $result = $this->models->users('user_manage_key_new', $id);
     if ($result) return array(true, 'Created new user App Key.', $result);

     return array(false, 'Failed to create new user App Key.');
   }

  /**
   * Delete App Key for currently logged in user. Requires 'manage_appkeys'
   * permission. Connects to users model.
   *
   * @param id
   *
   * @return is_deleted?
   */
   public function key_delete () {
     $this->user->require_permission('manage_appkeys');

     $id = $this->data('id');
     $user_id = $this->user->param('id');

     if (empty($id)) return array(false, 'Invalid key ID.');
     if (empty($user_id)) return array(false, 'Invalid user ID.');

     $result = $this->models->users('user_manage_key_delete', $id, $user_id);
     if ($result) return array(true, 'Successfully deleted App Key.');

     return array(false, 'Failed to delete App Key.');
   }
   
  /**
   * Save App Key permissions. Requires 'manage_appkeys' permission. Connects to users model.
   * 
   * @param id
   * @param permissions
   */
   public function key_permissions_save()
   {
     $this->user->require_permission('manage_appkeys');

     $id = $this->data('id');
     $permissions = trim($this->data('permissions'));
     $user_id = $this->user->param('id');
     
     return $this->models->users('user_manage_key_permissions_save', $id, $permissions, $user_id);
   }

  /**
   * Load App Keys for currently logged in user. Requires 'manage_appkeys'
   * permission. Connects to users model.
   *
   * @return appkeys
   */
   public function key_load () {
     $this->user->require_permission('manage_appkeys');
     
     $id = $this->user->param('id');
     if (empty($id)) return array(false, 'Invalid user ID.');

     $result = $this->models->users('user_manage_key_load', $id);
     return array(true, 'Successfully loaded App Keys.', $result);
   }
   
   /**
    * Get or save arbitrary string data associated with the logged in account. Used for UI/client settings, etc.
    *
    * @param key
    * @param value
    */
    /*
    public function store()
    {
      $data['user_id'] = $this->user->param('id');
      if (empty($data['user_id'])) return array(false, 'Invalid user ID.');
      
      $data['name'] = $this->data('name');
      $data['value'] = $this->data('value');
      $data['user_id'] = $this->user->param('id');
      
      var_dump($data);  
        
      if($data['value']) return $this->models->userstorage('save', $data);
      else return $this->models->userstorage('save', $data);
    }
    */
}