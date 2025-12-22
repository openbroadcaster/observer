<?php

// Copyright 2012-2024 OpenBroadcaster, Inc.
// SPDX-License-Identifier: AGPL-3.0-or-later

/**
 * Settings model. Used with CLIENT settings controller. Unlike the media
 * settings, which have a number of separate models, the regular settings model
 * is used for managing OpenBroadcaster-wide settings in the database.
 *
 * @package Model
 */
class SettingsModel extends OBFModel
{
    /**
     * Update a setting.
     *
     * @param name
     * @param value
     *
     * @return [status, msg, result]
     */
    public function setting_set($name, $value)
    {
        $this->db->where('name', $name);
        $this->db->delete('settings');
        $result = $this->db->insert('settings', [
        'name'  => $name,
        'value' => $value
        ]);

        return ($result)
        ? [true, 'Successfully set setting.', $result]
        : [false, 'Failed to update setting.'];
    }

    /**
     * Get a setting.
     *
     * @param name
     *
     * @return [status, msg, value]
     */
    public function setting_get($name)
    {
        $this->db->where('name', $name);
        $result = $this->db->get_one('settings');

        return ($result)
        ? [true, 'Successfully loaded setting.', $result['value']]
        : [false, 'Failed to load setting.'];
    }
}
