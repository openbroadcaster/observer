<?php

// Copyright 2012-2024 OpenBroadcaster, Inc.
// SPDX-License-Identifier: AGPL-3.0-or-later

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
