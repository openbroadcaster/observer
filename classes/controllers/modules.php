<?php

// Copyright 2012-2025 OpenBroadcaster, Inc.
// SPDX-License-Identifier: AGPL-3.0-or-later

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
