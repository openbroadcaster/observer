<?php

/*
    Copyright 2012-2023 OpenBroadcaster, Inc.

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
 * Model for playlog.
 *
 * @package Model
 */
class PlaylogModel extends OBFModel
{
    public function __construct()
    {
        parent::__construct();
    }

    public function get($start, $end)
    {
        $query = 'SELECT * FROM `players_log` WHERE ('
        . '(`timestamp` >= ' . intval($start) . ') OR '
        . ' (`media_end` >= ' . intval($start) . ' AND `media_end` IS NOT NULL) OR '
        . ' (`playlist_end` >= ' . intval($start) . ' AND `playlist_end` IS NOT NULL))';

        if ($end) {
            $query .= ' AND ('
            . '(`timestamp` <= ' . intval($end) . ') OR '
            . '(`media_end` <= ' . intval($end) . ' AND `media_end` IS NOT NULL) OR '
            . '(`playlist_end` <= ' . intval($end) . ' AND `playlist_end` IS NOT NULL))';
        }

        $this->db->query($query);
        $result = $this->db->assoc_list();

        return $result;
    }
}
