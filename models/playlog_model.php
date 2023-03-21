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

    public function get($id, $start, $end)
    {
        // intval should take care of any funny business, but escaping 
        // just in case (and so it doesn't get forgotten if code is updated
        // in the future).
        $id    = $this->db->escape($id);
        $start = $this->db->escape($start);
        $end   = $this->db->escape($end);

        $query = 'SELECT * FROM `players_log` WHERE '
        . '(`player_id` = ' . intval($id) . ') AND ('
        . '`timestamp` >= ' . intval($start) . ' OR '
        . '`media_end` >= ' . intval($start) . ' OR '
        . '`playlist_end` >= ' . intval($start) . ')';

        if ($end) {
            $query .= ' AND ('
            . '`timestamp` <= ' . intval($end) . ' OR '
            . '`media_end` <= ' . intval($end) . ' OR '
            . '`playlist_end` <= ' . intval($end) . ')';
        }

        $query .= ';';

        $this->db->query($query);
        $result = $this->db->assoc_list();

        return $result;
    }
}
