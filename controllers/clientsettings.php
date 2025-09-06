<?php

// Copyright 2012-2024 OpenBroadcaster, Inc.
// SPDX-License-Identifier: AGPL-3.0-or-later

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
     *
     * @route POST /v2/clientsettings/login-message
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
     *
     * @route GET /v2/clientsettings/login-message
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
     *
     * @route POST /v2/clientsettings/welcome-page
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
    *
    * @route GET /v2/clientsettings/welcome-page
    */
    public function get_welcome_page()
    {
        $this->user->require_authenticated();
        return $this->models->settings('setting_get', 'client_welcome_page');
    }
}
