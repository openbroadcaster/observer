<?php

// Copyright 2012-2025 OpenBroadcaster, Inc.
// SPDX-License-Identifier: AGPL-3.0-or-later

/**
 * Manages players that play the content managed on the server.
 *
 * @package Model
 */
class PlayersModel extends OBFModel
{
    /**
     * Retrieve data from a single player. ID passed as parameter, rather than in
     * a data array. Also includes all station IDs for that player.
     *
     * @param id
     *
     * @return player
     */
    public function get_one($id)
    {
        $this->db->where('id', $id);
        $player = $this->db->get_one('players');

        if ($player) {
            $player['station_ids'] = $this('get_station_ids', $id);
        }

        return $player;
    }

    /**
     * Retrieve all players.
     *
     * @return players
     */
    public function get_all()
    {
        return $this->db->get('players');
    }

    /**
     * Retrieve players filtered by parameters.
     *
     * @param params Filters used when selecting players. See controller for specifics.
     *
     * @return players
     */
    public function get($params)
    {
        foreach ($params as $name => $value) {
            $$name = $value;
        }

        if ($filters) {
            foreach ($filters as $filter) {
                $column = $filter['column'];
                $value = $filter['value'];
                $operator = (empty($filter['operator']) ? '=' : $filter['operator']);

                $this->db->where($column, $value, $operator);
            }
        }

        if ($orderby) {
            $this->db->orderby($orderby, (!empty($orderdesc) ? 'desc' : 'asc'));
        }

        if ($limit) {
            $this->db->limit($limit);
        }

        if ($offset) {
            $this->db->offset($offset);
        }

        $result = $this->db->get('players');

        if ($result === false) {
            return false;
        }

        foreach ($result as $index => $row) {
            $default_playlist = null;

            // get our default playlist name.
            if (!empty($row['default_playlist_id'])) {
                $this->db->where('id', $row['default_playlist_id']);
                $default_playlist = $this->db->get_one('playlists');
            }

            if ($default_playlist) {
                $results[$index]['default_playlist_name'] = $default_playlist['name'];
                $results[$index]['default_playlist_id'] = $default_playlist['id'];
            }

            // get our station ids
            $result[$index]['media_ids'] = [];

            $station_ids = $this('get_station_ids', $row['id']);
            foreach ($station_ids as $station_id) {
                $this->db->where('id', $station_id);
                $media = $this->db->get_one('media');

                if ($media) {
                    $result[$index]['media_ids'][] = $media;
                }
            }
        }

        return $result;
    }

    /**
     * Get station IDs for a player. ID passed as single parameter, not in data
     * array.
     *
     * @param id
     *
     * @return media_ids
     */
    public function get_station_ids($id)
    {
        $this->db->where('player_id', $id);
        $station_ids = $this->db->get('players_station_ids');

        $media_ids = [];

        foreach ($station_ids as $station_id) {
            $media_ids[] = $station_id['media_id'];
        }

        return $media_ids;
    }

    /**
     * Very unintelligently guess at a station ID duration. Since this is used in
     * playlists which are not tied to a player, and since station ID durations
     * can vary considerably, this is probably going to be a pretty terrible
     * estimate.
     *
     * @return duration
     */
    public function station_id_average_duration()
    {
        $this->db->query('select sum(media.duration) as sum, count(*) as count from players_station_ids left join media on players_station_ids.media_id = media.id where media.type!="image"');
        $data = $this->db->assoc_list();
        $sum = $data[0]['sum'];
        $sum_count = $data[0]['count'];

        $players = $this->get_all();

        foreach ($players as $player) {
            $this->db->query('select count(*) as count from players_station_ids left join media on players_station_ids.media_id = media.id where media.type="image" and players_station_ids.player_id="' . $this->db->escape($player['id']) . '"');
            $data = $this->db->assoc_list();

            $sum += $data['0']['count'] * $player['station_id_image_duration'];
            $sum_count += $data[0]['count'];
        }

        if ($sum_count == 0) {
            return 0;
        } // no station IDs? then duration is zero.
        return $sum / $sum_count;
    }

    /**
     * Validate data for updating/insert a player.
     *
     * @param data Data array. See controller for details.
     * @param id player ID. FALSE when inserting a new player.
     *
     * @return [status, msg]
     */
    public function validate($data, $id = false)
    {
        $error = false;

        if (empty($data['name'])) {
            $error = 'A player name is required.';
        } elseif (isset($data['stream_url']) && $data['stream_url'] != '' && !preg_match('|^http(s)?://[a-z0-9-]+(.[a-z0-9-]+)*(:[0-9]+)?(/.*)?$|i', $data['stream_url'])) {
            $error = 'The stream URL is not valid.  Only HTTP(s) is supported.';
        } elseif (empty($data['password']) && !$id) {
            $error = 'A player password is required.';
        } elseif (!empty($data['password']) && strlen($data['password']) < 6) {
            // only required for new players. if password not specified for existing players, no password change will occur.
            $error = 'The password must be at least 6 characters long.';
        } elseif ($id && !$this->db->id_exists('players', $id)) {
            $error = 'The player you are attempted to edit does not exist.';
        } elseif (!preg_match('/^[0-9]+$/', $data['station_id_image_duration']) || $data['station_id_image_duration'] == 0) {
            $error = 'Station ID image duration is not valid.  Enter a number to specify duration in seconds.';
        } elseif (empty($data['timezone'])) {
            // verify timezone
            $error = 'You must set a timezone for each player.';
        }

        // make sure player name is unique
        if (empty($error)) {
            if ($id) {
                $this->db->where('id', $id, '!=');
            }
            $this->db->where('name', $data['name']);
            if ($this->db->get_one('players')) {
                $error = 'player name must be unique.';
            }
        }

        if (empty($error)) {
            try {
                $tz_test = new DateTimeZone($data['timezone']);
            } catch (Exception $e) {
                $tz_test = false;
            }

            if (!$tz_test) {
                $error = 'There was an error setting the timezone.';
            }
        }

        // make sure parent player is valid.
        if (empty($error) && $data['parent_player_id']) {
            $this->db->where('id', $data['parent_player_id']);
            $parent_player = $this->db->get_one('players');

            if (!$parent_player) {
                $error = 'The specified parent player no longer exists.';
            } elseif ($parent_player['parent_player_id'] != 0) {
                $error = 'This parent player cannot be used.  players that act as child players cannot be used as parents.';
            }
        }

        // verify station IDs.
        if (is_array($data['station_ids']) && !$error) {
            foreach ($data['station_ids'] as $station_id) {
                $this->db->where('id', $station_id);
                $media_info = $this->db->get_one('media');
                if (!$media_info) {
                    $error = 'A station ID you have selected no longer exists.';
                    break;
                }
                if ($media_info['is_archived'] == 1 || $media_info['is_approved'] == 0) {
                    $error = 'Station IDs may be approved media only.';
                    break;
                }
            }
        }

        // verify playlist ID
        if (!$error && !empty($data['default_playlist_id'])) {
            if (!$this->db->id_exists('playlists', $data['default_playlist_id'])) {
                $error = 'The playlist you have selected no longer exists.';
            }
        }

        if ($error) {
            return [false,$error];
        }

        return [true,''];
    }

    /**
     * Insert or update a player.
     *
     * @param data
     * @param id
     *
     * @return id
     */
    public function save($data, $id = false)
    {
        $station_ids = $data['station_ids'];
        unset($data['station_ids']);

        if (!$data['use_parent_schedule']) {
            $data['use_parent_dynamic'] = 0;
        }

        if (!$id) {
            $data['password'] = password_hash($data['password'] . OB_HASH_SALT, PASSWORD_DEFAULT);
            $data['owner_id'] = $this->user->param('id');
            $id = $this->db->insert('players', $data);
            if (!$id) {
                return false;
            }
        } else {
      // get original player, see if we're updating default playlist.
            $this->db->where('id', $id);
            $original_player = $this->db->get_one('players');

            // do we need to clear out all the cache? (child/parent setting change)
            if (
                $original_player['use_parent_dynamic'] != $data['use_parent_dynamic']
                || $original_player['use_parent_schedule'] != $data['use_parent_schedule']
                || $original_player['use_parent_ids'] != $data['use_parent_ids']
                || $original_player['use_parent_playlist'] != $data['use_parent_playlist']
            ) {
                $this->db->where('player_id', $id);
                $this->db->delete('shows_cache');
            } elseif ($original_player['default_playlist_id'] != $data['default_playlist_id']) {
                // if we are changing the default playlist, clear the default playlist schedule cache for this player
                $this->db->where('player_id', $id);
                $this->db->where('mode', 'default_playlist');
                $this->db->delete('shows_cache');
            }

            // unset the password if empty - we don't want to change. otherwise, set as hash.
            if ($data['password'] == '') {
                unset($data['password']);
            } else {
                $data['password'] = password_hash($data['password'] . OB_HASH_SALT, PASSWORD_DEFAULT);
            }

            $this->db->where('id', $id);
            $update = $this->db->update('players', $data);

            if (!$update) {
                return false;
            }
        }

        $station_id_data['player_id'] = $id;
        if ($station_ids !== false) {
      // delete all station IDs for this player.
            $this->db->where('player_id', $id);
            $this->db->delete('players_station_ids');

            // add all the station IDs we have.
            if (is_array($station_ids)) {
                foreach ($station_ids as $station_id) {
                    $station_id_data['media_id'] = $station_id;
                    $this->db->insert('players_station_ids', $station_id_data);
                }
            }
        }

        return $id;
    }

    /**
     * Update player version.
     *
     * @param id
     * @param version
     */
    public function update_version($id, $version)
    {
        $this->db->where('id', $id);
        $this->db->update('players', ['version' => $version]);
    }

    /**
     * Update player location.
     *
     * @param id
     * @param longitude
     * @param latitude
     */
    public function update_location($id, $longitude, $latitude)
    {
        $this->db->where('id', $id);
        $this->db->update('players', ['longitude' => $longitude,'latitude' => $latitude]);
    }


    /**
     * Validate whether it is possible to delete a player. Return FALSE in cases
     * where a player has alerts associated with it, or schedule data that the
     * current user has no permission to delete.
     *
     * @param id
     *
     * @return is_deletable
     */
    public function delete_check_permission($id)
    {

    // see if there are alerts associated with this player.
        $this->db->where('player_id', $id);
        if ($this->db->get_one('alerts') && !$this->user->check_permission('manage_alerts')) {
            //T Unable to remove this player.  It has alerts that you do not have permission to delete.
            return [false,'Unable to remove this player.  It has alerts that you do not have permission to delete.'];
        }

        // this doesn't check 'able to delete own show' ability... not sure it's practically necessary..
        $schedule_fail = false;

        $this->db->where('player_id', $id);
        if ($this->db->get_one('shows') && !$this->user->check_permission('manage_timeslots')) {
            $schedule_fail = true;
        }
        $this->db->where('player_id', $id);
        if ($this->db->get_one('timeslots') && !$this->user->check_permission('manage_timeslots')) {
            $schedule_fail = true;
        }

        if ($schedule_fail) {
            //T Unable to remove this player.  It has schedule data that you do not have permission to delete.
            return [false,'Unable to remove this player.  It has schedule data that you do not have permission to delete.'];
        }

        return [true,''];
    }

    /**
     * Check whether the player is a parent of any other players.
     *
     * @param id
     *
     * @return is_parent
     */
    public function player_is_parent($id)
    {
        $this->db->where('parent_player_id', $id);
        $test = $this->db->get_one('players');

        if ($test) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Delete a player.
     *
     * @param id
     */
    public function delete($id)
    {
        $this->db->where('id', $id);
        $this->db->delete('players');
    }

    /**
     * Search the player monitor log.
     *
     * @param params
     *
     * @return [results, numrows]
     */
    public function monitor_search($params)
    {
        foreach ($params as $name => $value) {
            $$name = $value;
        }

        // get timestamps based on player timezone
        $player = $this('get_one', $player_id);
        if (!$player) {
            return [false];
        }

        $player_timezone = new DateTimeZone($player['timezone']);
        if (!$player_timezone) {
            return [false];
        }

        $start_datetime = new DateTime($date_start, $player_timezone);
        $end_datetime = new DateTime($date_end, $player_timezone);
        if (!$start_datetime || !$end_datetime) {
            return [false];
        }

        // db lookup
        $this->db->where('player_id', $player_id);
        $this->db->where('timestamp', $start_datetime->getTimestamp(), '>=');
        $this->db->where('timestamp', $end_datetime->getTimestamp(), '<');

        if ($orderby) {
            $this->db->orderby($orderby, (!empty($orderdesc) ? 'desc' : 'asc'));
        }
        if ($limit) {
            $this->db->limit($limit);
        }
        if ($offset) {
            $this->db->offset($offset);
        }

        if ($filters) {
            foreach ($filters as $filter) {
                $column = $filter['column'];
                $value = $filter['value'];
                $operator = $filter['operator'];

                if (array_search($column, ['media_id','artist','title']) === false) {
                    return [false,null];
                }
                if (array_search($operator, ['is','not','like','not_like']) === false) {
                    return [false,null];
                }

                if ($operator == 'like') {
                    $this->db->where_like($column, $value);
                } elseif ($operator == 'not_like') {
                    $this->db->where_not_like($column, $value);
                } else {
                    $this->db->where($column, $value, ($operator == 'is' ? '=' : '!='));
                }
            }
        }

        $this->db->calc_found_rows();

        $results = $this->db->get('playlog');

        foreach ($results as &$result) {
            $result['datetime'] = new DateTime('@' . round($result['timestamp']));
            $result['datetime']->setTimezone($player_timezone);
            $result['datetime'] = $result['datetime']->format('Y-m-d H:i:s');
        }

        $numrows = $this->db->found_rows();

        return [$results,$numrows];
    }

    /**
     * Convert monitor results into CSV format.
     *
     * @param results
     *
     * @return csv
     */
    public function monitor_csv($results)
    {
        if (empty($results)) {
            return false;
        }

        $fh = fopen('php://temp', 'w+');

        // get our timezone from the player id
        $player_id = $results[0]['player_id'];
        $player = $this('get_one', $player_id);

        // add our heading row
        fputcsv($fh, ['Media ID','Artist','Title','Date/Time','Context','Notes']);

        // add data rows
        foreach ($results as $data) {
            fputcsv($fh, [
            $data['media_id'],
            $data['artist'],
            $data['title'],
            $data['datetime'],
            $data['context'],
            $data['notes']
            ]);
        }

        // get csv contents
        $csv = stream_get_contents($fh, -1, 0);

        // close
        fclose($fh);

        return $csv;
    }

    /**
     * Return what's currently playing on a player.
     *
     * @param id
     *
     * @return [show_name, show_time_left, media]
     */
    public function now_playing($player_id)
    {
        $this->db->what('current_playlist_id');
        $this->db->what('current_playlist_end');
        $this->db->what('current_media_id');
        $this->db->what('current_media_end');
        $this->db->what('current_show_name');

        $this->db->where('id', $player_id);
        $player = $this->db->get_one('players');

        if (!$player) {
            return false;
        }

        $return = [];
        $return['show_name'] = $player['current_show_name'];
        $return['show_time_left'] = $player['current_playlist_end'] - time();

        $this->models->media('get_init');

        $this->db->where('media.id', $player['current_media_id']);
        $media = $this->db->get_one('media');

        $media_data = [];
        $media_data['id'] = $media['id'];
        $media_data['title'] = $media['title'];
        $media_data['album'] = $media['album'];
        $media_data['artist'] = $media['artist'];
        $media_data['year'] = $media['year'];
        $media_data['category_id'] = $media['category_id'];
        $media_data['category_name'] = $media['category_name'];
        $media_data['country'] = $media['country'];
        $media_data['country_name'] = $media['country_name'];
        $media_data['language_id'] = $media['language_id'];
        $media_data['language_name'] = $media['language_name'];
        $media_data['genre_id'] = $media['genre_id'];
        $media_data['genre_name'] = $media['genre_name'];
        $media_data['duration'] = $media['duration'];
        $media_data['time_left'] = $player['current_media_end'] - time();

        $return['media'] = $media_data;

        return $return;
    }

    /**
     * Set last connection time for the specified player and action.
     *
     * @param id
     */
    public function set_last_connect($playerId, $playerIp, $action)
    {
        $actionToColumn = [
            'schedule' => 'schedule',
            'emerg' => 'emergency',
            'playlog_status' => 'playlog',
            'playlog_post' => 'playlog',
            'media' => 'media',
            'thumbnail' => 'media'
        ];

        if (isset($actionToColumn[$action])) {
            $lastConnectColumn = 'last_connect_' . $actionToColumn[$action];
            $noticeColumn = 'player_last_connect_' . $actionToColumn[$action] . '_warning';
        } else {
            $lastConnectColumn = null;
            $noticeColumn = null;
        }


        // set last connect time and last ip address
        $updateData = [
            'last_connect' => time(),
            'last_ip_address' => $playerIp
        ];

        if ($lastConnectColumn) {
            $updateData[$lastConnectColumn] = time();
        }

        $this->db->where('id', $playerId);
        $this->db->update('players', $updateData);

        // remove connection warning
        $this->db->where('player_id', $playerId);
        $this->db->where('event', 'player_last_connect_warning');
        $this->db->update('notices', ['toggled' => 0]);

        // remove specific connection warning
        if ($noticeColumn) {
            $this->db->where('player_id', $playerId);
            $this->db->where('event', $noticeColumn);
            $this->db->update('notices', ['toggled' => 0]);
        }
    }
}
