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
