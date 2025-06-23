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
 * Manages module installation and uninstallation.
 *
 * @package Controller
 */
class Modules extends OBFController
{
    public function __construct()
    {
        parent::__construct();
        $this->user->require_permission('manage_modules');
    }

    /**
     * Return a list of currently installed and available (= uninstalled) modules.
     *
     * @return [installed, available]
     *
     * @route GET /v2/modules
     */
    public function search()
    {
        $modules = [];
        $modules['installed'] = $this->models->modules('get_installed');
        $modules['available'] = $this->models->modules('get_not_installed');

        return [true,'Modules',$modules];
    }

    /**
     * Install a module. Requires a page refresh after installation.
     *
     * @param name
     *
     * @route PUT /v2/modules/(:name:)
     */
    public function install()
    {
        $module = $this->data('name');

        $install = $this->models->modules('install', $module);

        if ($install) {
            return [true,'Module installed. Refreshing the page may be required to update the user interface.'];
        } else {
            return [false,'An error occurred while attempting to install this module.'];
        }
    }

    /**
     * Uninstall a module. Requires a page refresh after uninstallation.
     *
     * @param name
     *
     * @route DELETE /v2/modules/(:name:)
     */
    public function uninstall()
    {
        $module = $this->data('name');

        $uninstall = $this->models->modules('uninstall', $module);

        if ($uninstall) {
            return [true,'Module uninstalled. Refreshing the page may be required to update the user interface.'];
        } else {
            return [false,'An error occurred while attempting to uninstall this module.'];
        }
    }

    /**
     * Purge the data from a module. Requires a page refresh after purging.
     * Note that this method will first attempt to uninstall the module.
     *
     * @param name
     *
     * @route DELETE /v2/modules/purge/(:name:)
     */
    public function purge()
    {
        $module = $this->data('name');

        $this->db->where('directory', $module);
        $installed = $this->db->get('modules');

        if ($installed) {
            $uninstall = $this->models->modules('uninstall', $module);
            if (! $uninstall) {
                return [false,'An error occurred while attempting to uninstall this module.'];
            }
        }

        $purge = $this->models->modules('purge', $module);

        if ($purge) {
            return [true,'Module data purged. Refreshing the page may be required to update the user interface.'];
        } else {
            return [false,'An error occurred while attempting to purge this module.'];
        }
    }
}
