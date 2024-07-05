<?php

/*
    Copyright 2012-2024 OpenBroadcaster, Inc.

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
