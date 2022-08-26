<?php

/*
    Copyright 2012-2020 OpenBroadcaster, Inc.

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
 * Model for .
 *
 * @package Model
 */
class UpdatesModel extends OBFModel
{
    // get an array of update classes.
    public function update_required()
    {
        $update_files = scandir(OB_LOCAL.'/updates', SCANDIR_SORT_DESCENDING);
        $latest_version = null;
        foreach ($update_files as $update_file) {
            if (preg_match('/^[0-9]{8}\.php$/', $update_file)) {
                $latest_version = (int) substr($update_file, 0, 8);
                break;
            }
        }

        return $latest_version > $this('db_version');
    }

    public function db_version()
    {
        $this->db->where('name', 'dbver');
        $row = $this->db->get_one('settings');
        if (!$row) {
            return false;
        }
        return (int) $row['value'];
    }
}
