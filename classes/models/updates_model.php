<?php

// Copyright 2012-2024 OpenBroadcaster, Inc.
// SPDX-License-Identifier: AGPL-3.0-or-later

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
        $update_files = scandir(OB_LOCAL . '/updates', SCANDIR_SORT_DESCENDING);
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
