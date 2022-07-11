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
 * Manages global OpenBroadcaster settings. Does NOT manage settings for
 * individual users, but is called Client Settings to differentiate from Settings,
 * which manages media-related settings.
 *
 * @package Controller
 */
class ClientSettings extends OBFController
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Set a login message.
     *
     * @param client_login_message
     *
     * @return setting_result
     */
    public function set_login_message()
    {
        $this->user->require_permission('manage_global_client_storage');
        $data = $this->data('client_login_message');
        return $this->models->settings('setting_set', 'client_login_message', $data);
    }

    /**
     * Get the login message.
     *
     * @return client_login_message
     */
    public function get_login_message()
    {
        return $this->models->settings('setting_get', 'client_login_message');
    }

    /**
     * Set the welcome page.
     *
     * @param client_welcome_page The HTML welcome page to display.
     *
     * @return setting_result
     */
    public function set_welcome_page()
    {
        $this->user->require_permission('manage_global_client_storage');
        $data = $this->data('client_welcome_page');
        return $this->models->settings('setting_set', 'client_welcome_page', $data);
    }

    /**
    * Get the welcome page. Returns a string in HTML format.
    *
    * @return client_welcome_page
    */
    public function get_welcome_page()
    {
        $this->user->require_authenticated();
        return $this->models->settings('setting_get', 'client_welcome_page');
    }
}
