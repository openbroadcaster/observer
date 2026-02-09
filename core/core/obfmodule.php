<?php

// Copyright 2012-2025 OpenBroadcaster, Inc.
// SPDX-License-Identifier: AGPL-3.0-or-later

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
