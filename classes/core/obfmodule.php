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

    /**
     * Placeholder for module to override.
     */
    public function purge()
    {
        return true;
    }

    final protected function permission_enable(string $category, string $name, string $description)
    {
        $this->db->where('name', $name);
        $this->db->get('users_permissions');
        if ($this->db->num_rows() > 0) {
            $this->db->where('name', $name);
            $this->db->update('users_permissions', [
                'enabled' => 1
            ]);
        } else {
            $this->db->insert('users_permissions', [
                'name' => $name,
                'description' => $description,
                'category' => $category
            ]);
        }
    }

    final protected function permission_disable(string $name)
    {
        $this->db->where('name', $name);
        $this->db->update('users_permissions', [
            'enabled' => 0
        ]);
    }

    final protected function permission_delete(string $name)
    {
        $this->db->where('name', $name);
        $this->db->delete('users_permissions');
    }
}
