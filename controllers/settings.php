<?php

// Copyright 2012-2024 OpenBroadcaster, Inc.
// SPDX-License-Identifier: AGPL-3.0-or-later

/**
 * Manages media settings. Note that this specifically does NOT manage user-related
 * and global settings in the settings table. User settings are managed in the
 * Accounts controller, and there is no (as of 2020-02-25) specific controller
 * for managing global settings.
 *
 * @package Controller
 */
class Settings extends OBFController
{
    public function __construct()
    {
        parent::__construct();
        $this->user->require_authenticated();
    }

    /**
     * Return OpenBroadcaster version information.
     *
     * @return version
     *
     * @route GET /v2/settings/version
     */
    public function get_ob_version()
    {
        if (file_exists('VERSION')) {
            $version = trim(file_get_contents('VERSION'));
        } else {
            $version = '';
        }

        return [true,'OpenBroadcaster Version',$version];
    }
}
