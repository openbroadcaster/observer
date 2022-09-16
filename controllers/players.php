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
 * Manages players that play the content managed on the server.
 *
 * @package Controller
 */
class Players extends OBFController
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Return a list of players filtered by user-provided info. By default, hide
     * sensitive information unless the user has the 'manage_players' permission.
     *
     * @param filters Filter by arbitrary values in the players table.
     * @param orderby Order players by column in database.
     * @param orderdesc Order descending (set/TRUE) or ascending (unset).
     * @param limit Limit number of players returned, useful for paging.
     * @param offset Get only the player after offset, useful for paging.
     *
     * @return players_array
     *
     * @route GET /v2/players
     */
    public function search()
    {
        $this->user->require_authenticated();

        $params['filters'] = $this->data('filters');
        $params['orderby'] = $this->data('orderby');
        $params['orderdesc'] = $this->data('orderdesc');
        $params['limit'] = $this->data('limit');
        $params['offset'] = $this->data('offset');

        if ($params['orderby'] == false) {
            $params['orderby'] = 'name';
        }

        $players = $this->models->players('get', $params);

        if ($players === false) {
            return array(false,'An unknown error occurred while fetching players.');
        }

        foreach ($players as $index => $player) {
            unset($players[$index]['password']); // never need to share this.

            if (!$this->user->check_permission('manage_players')) { // hide sensitive info unless we are a player manager.
                unset($players[$index]['ip_address']);
                $players[$index]['last_ip_address'] = '******';
            }
        }

        return array(true,'Player list.',$players);
    }

    /**
     * Edit a player. Requires 'manage_players' permission.
     *
     * @param id
     * @param station_ids IDs of stations played by this player.
     * @param name
     * @param description
     * @param stream_url
     * @param password
     * @param ip_address
     * @param support_audio
     * @param support_video
     * @param support_images
     * @param support_linein
     * @param timezone
     * @param station_id_image_duration Duration static images are displayed in seconds.
     * @param default_playlist
     * @param parent_player_id Parent player ID. If set, it can use certain values of the parent player depending on which use_parent parameters are set.
     * @param use_parent_dynamic
     * @param use_parent_schedule
     * @param use_parent_ids If TRUE, 'station_ids' is emptied and 'station_id_image_duration' will be ignored.
     * @param use_parent_playlist If TRUE, 'default_playlist_id' will be set to FALSE.
     * @param use_parent_emergency
     *
     * @return player_id
     *
     * @route POST /v2/players
     */
    public function save()
    {
        $this->user->require_permission('manage_players');

        $id = trim($this->data('id'));
        $data['station_ids'] = $this->data('station_ids');

        $data['name'] = trim($this->data('name'));
        $data['description'] = $this->data('description');
        $data['stream_url'] = $this->data('stream_url');
        $data['password'] = $this->data('password');
        $data['ip_address'] = $this->data('ip_address');

        $data['support_audio'] = $this->data('support_audio');
        $data['support_video'] = $this->data('support_video');
        $data['support_images'] = $this->data('support_images');
        $data['support_linein'] = $this->data('support_linein');

        $data['timezone'] = trim($this->data('timezone'));
        $data['station_id_image_duration'] = trim($this->data('station_id_image_duration'));
        $data['default_playlist_id'] = $this->data('default_playlist');

        $data['parent_player_id'] = $this->data('parent_player_id');
        if (!empty($data['parent_player_id'])) {
            $data['use_parent_dynamic'] = (int) $this->data('use_parent_dynamic');
            $data['use_parent_schedule'] = (int) $this->data('use_parent_schedule');
            $data['use_parent_ids'] = (int) $this->data('use_parent_ids');
            $data['use_parent_playlist'] = (int) $this->data('use_parent_playlist');
            $data['use_parent_emergency'] = (int) $this->data('use_parent_emergency');

            if ($data['use_parent_ids']) {
                $data['station_ids'] = array();
                $data['station_id_image_duration'] = 15; // this field will be hidden, set to default. it doesn't matter but needs to be valid.
            }

            if ($data['use_parent_playlist']) {
                $data['default_playlist_id'] = false;
            }
        } else {
            $data['parent_player_id'] = null;
            $data['use_parent_dynamic'] = 0;
            $data['use_parent_schedule'] = 0;
            $data['use_parent_ids'] = 0;
            $data['use_parent_playlist'] = 0;
            $data['use_parent_emergency'] = 0;
        }

        $validation = $this->models->players('validate', $data, $id);
        if ($validation[0] == false) {
            return $validation;
        }

        $id = $this->models->players('save', $data, $id);

        //T Player saved.
        return array(true, 'Player saved.', $id);
    }

    /**
     * Delete a player. Requires the 'manage_players' permission. Note that the
     * method returns false when trying to delete a parent player, since this would
     * result in invalid settings for child players.
     *
     * @param id
     *
     * @route DELETE /v2/players/(:id:)
     */
    public function delete()
    {
        $this->user->require_permission('manage_players');

        $id = $this->data('id');

        if (empty($id)) {
            return array(false,'A player ID is required.');
        }

        $validation = $this->models->players('delete_check_permission', $id);
        if ($validation[0] == false) {
            return $validation;
        }

        if ($this->models->players('player_is_parent', $id)) {
            return array(false,'This player is a parent player, and cannot be deleted.');
        }

        $this->models->players('delete', $id);

        //T player deleted.
        return array(true,'player deleted.');
    }

    /**
     * Get data from a single player. Never returns the player password, and only
     * returns the ip_address field if the user has the 'manage_players' permission.
     *
     * @param id
     *
     * @return player_data
     *
     * @route GET /v2/players/(:id:)
     */
    public function get()
    {
        $this->user->require_authenticated();

        $id = $this->data('id');

        $player = $this->models->players('get_one', $id);

        if ($player) {
            unset($player['password']); // never need to share this.

            if (!$this->user->check_permission('manage_players')) { // hide sensitive info unless we are a player manager.
                unset($player['ip_address']);
            }

            return array(true,'player data',$player);
        } else {
            //T This player no longer exists.
            return array(false,'This player no longer exists.');
        }
    }

    /**
     * Estimate the average duration of station IDs. Not tied to a particular player.
     *
     * @return average
     *
     * @route GET /v2/players/avg-duration
     */
    public function station_id_avg_duration()
    {
        $this->user->require_authenticated();
        $average = $this->models->players('station_id_average_duration');
        return array(true,'Average duration for station IDs.',$average);
    }

    /**
     * Search the player monitor, which logs what a player has played so far.
     * Requires the 'view_player_monitor' permission.
     *
     * @param player_id
     * @param date_start
     * @param date_end
     * @param filters Filter by arbitrary values in the players table.
     * @param orderby Order players by column in database.
     * @param orderdesc Order descending (set/TRUE) or ascending (unset).
     * @param limit Limit number of players returned, useful for paging.
     * @param offset Get only the player after offset, useful for paging.
     *
     * @return [results, total_rows, csv_results]
     *
     * @route GET /v2/players/monitor
     */
    public function monitor_search()
    {
        $data['player_id'] = $this->data('player_id');
        $data['date_start'] = $this->data('date_start');
        $data['date_end'] = $this->data('date_end');

        $data['orderby'] = $this->data('orderby');
        $data['orderdesc'] = $this->data('orderdesc');

        $data['filters'] = $this->data('filters');

        $data['limit'] = $this->data('limit');
        $data['offset'] = $this->data('offset');

        if (!$data['orderby']) {
            $data['orderby'] = 'timestamp';
            $data['orderdesc'] = true;
        }

        // validate player_id, check permission.
        if (!preg_match('/^[0-9]+$/', $data['player_id'])) {
            return array(false,'Invalid player ID.');
        }
        $this->user->require_permission('view_player_monitor:' . $data['player_id']);

        $result = $this->models->players('monitor_search', $data);

        if ($result[0] === false) {
            return array(false,'An unknown error occurred while searching the playlog.');
        }

        return array(true,'Playlog search results.',array('results' => $result[0],'total_rows' => $result[1], 'csv' => $this->models->players('monitor_csv', $result[0])));
    }

    /**
     * Return data about what's now playing on a specific player.
     *
     * @param id
     *
     * @return [show_name, show_time_left, media_data]
     *
     * @route GET /v2/players/playing/(:id:)
     */
    public function now_playing()
    {
        $player_id = $this->data('id');
        $return = $this->models->players('now_playing', $player_id);
        return array(true,'Now playing.',$return);
    }
}
