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
 * Manages modules.
 *
 * @package Class
 */
class OBFModule
{
    public $db;
    public $callback_handler;

    /**
     * Create instance of OBFModules, makes database (db) and base framwork (ob)
     * available.
     */
    public function __construct()
    {
        $this->db = OBFDB::get_instance();
        $this->callback_handler = OBFCallbacks::get_instance();
    }

    /**
     * Placeholder for module to override.
     */
    public function callbacks()
    {
    }

    /**
     * Placeholder for module to override.
     */
    public function install()
    {
        return true;
    }

    /**
     * Placeholder for module to override.
     */
    public function uninstall()
    {
        return true;
    }
}
