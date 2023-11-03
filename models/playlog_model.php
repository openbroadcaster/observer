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

        $id_intval = intval($id);
        $start_intval = intval($start);
        $end_intval = intval($end);

        $query = "  SELECT
                        players_log.*,
                        media.artist as media_artist,
                        media.title AS media_title,
                        playlists.name AS playlist_name,
                        playlists.description AS playlist_description
                    FROM `players_log`
                    LEFT JOIN media ON players_log.media_id = media.id
                    LEFT JOIN playlists ON players_log.playlist_id = playlists.id
                    WHERE
                        players_log.player_id = $id_intval AND
                        players_log.media_end > $start_intval";

        // add end condition if specified
        if ($end) {
            $query .= " AND players_log.timestamp < $end_intval";
        }

        $this->db->query($query);
        $result = $this->db->assoc_list();

        $now = time();
        foreach ($result as &$item) {
            if ($item['playlist_end']) {
                $item['playlist_end'] = max(0, $item['playlist_end'] - $now);
            }

            if ($item['media_end']) {
                $item['media_end'] = max(0, $item['media_end'] - $now);
            }
        }

        return $result;
    }
}
