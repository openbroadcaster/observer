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
namespace OpenBroadcaster\Controllers;

use OpenBroadcaster\Base\Controller;

class ClientSettings extends Controller
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
     * @route POST /clientsettings/login-message
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
     * @route GET /clientsettings/login-message
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
     * @route POST /clientsettings/welcome-page
     */
    public function set_welcome_page()
    {
        $this->user->require_permission('manage_global_client_storage');
        $data = $this->sanitize_welcome_page($this->data('client_welcome_page'));
        return $this->models->settings('setting_set', 'client_welcome_page', $data);
    }

    /**
     * Strip the welcome page HTML down to what the editor's toolbar can actually
     * produce (bold, italic, links, paragraphs/line breaks). This is rendered
     * unescaped for every user on login, so it can't be trusted as-is: the editor
     * widget is just a UI, nothing stops a request from setting arbitrary HTML
     * directly through this endpoint.
     *
     * @param html
     *
     * @return sanitized_html
     */
    private function sanitize_welcome_page($html)
    {
        $config = \HTMLPurifier_Config::createDefault();
        $config->set('HTML.Allowed', 'p,br,b,strong,i,em,a[href]');
        $config->set('URI.AllowedSchemes', ['http' => true, 'https' => true, 'mailto' => true]);
        $config->set('Cache.SerializerPath', OB_CACHE);

        $purifier = new \HTMLPurifier($config);

        return $purifier->purify($html);
    }

    /**
    * Get the welcome page. Returns a string in HTML format.
    *
    * @return client_welcome_page
    *
    * @route GET /clientsettings/welcome-page
    */
    public function get_welcome_page()
    {
        $this->user->require_authenticated();
        return $this->models->settings('setting_get', 'client_welcome_page');
    }
}
