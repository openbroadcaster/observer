<?php

/*
    Copyright 2012-2021 OpenBroadcaster, Inc.

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
 * Shows controller manages single scheduled timeslot items.
 *
 * @package Controller
 */
class Shows extends OBFController
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Get an individual scheduled show.
     *
     * @param id
     *
     * @return show
     *
     * @route GET /v2/shows/(:id:)
     */
    public function get()
    {
        $this->user->require_authenticated();

        $id = $this->data('id');

        $show = $this->models->shows('get_show_by_id', $id);

        //T Show not found.
        if (!$show) {
            return array(false, 'Show not found.');
        }
        //T Scheduled show.
        return array(true, 'Scheduled show.', $show);
    }

    /**
     * Get shows between two given dates/times. This function is used for the
     * UI/API, but also to detect potential scheduling collisions.
     *
     * @param start
     * @param end
     * @param player
     *
     * @return shows
     *
     * @route GET /v2/shows
     */
    public function search()
    {
        $this->user->require_authenticated();

        $start  = $this->data('start');
        $end    = $this->data('end');
        $player = $this->data('player');

        //T Player ID is invalid.
        if (!preg_match('/^[0-9]+$/', $player)) {
            return array(false, 'Player ID is invalid.');
        }
        //T Start or end date invalid.
        if (!preg_match('/^[0-9]+$/', $start) || !preg_match('/^[0-9]+$/', $end)) {
            return array(false, 'Start or end date invalid.');
        }
        if ($start >= $end) {
            return array(false, 'Start or end date invalid.');
        }

        // check if player is valid.
        $this->db->where('id', $player);
        $player_data = $this->db->get_one('players');

        //T Player ID is invalid.
        if (!$player_data) {
            return array(false, 'Player ID is invalid.');
        }

        $data = $this->models->shows('get_shows', $start, $end, $player);

        //T Shows data.
        return array(true, 'Shows data.', $data);
    }

    /**
     * Set the last selected player so we can view schedules for that player
     * immediately when loading the page again some other time. This will have
     * to be generalized for other UI elements at some point. This is a
     * user-specific setting, so no special permissions are necessary.
     *
     * @param player
     */
    public function set_last_player()
    {
        $player_id = $this->data('player');

        $this->db->where('id', $player_id);
        $player_data = $this->db->get_one('players');

        if ($player_data) {
            $this->user->set_setting('last_schedule_player', $player_id);
            //T Set last schedule player.
            return array(true, 'Set last schedule player.');
        }
        //T Player not found.
        return array(false,'Player not found.');
    }

    /**
     * Get the last selected player on the schedules page for the current user.
     *
     * @return player
     */
    public function get_last_player()
    {
        $player_id = $this->user->get_setting('last_schedule_player');
        //T Last schedule player.
        if ($player_id) {
            return array(true, 'Last schedule player.', $player_id);
        }
        //T Last schedule player not found.
        return array(false, 'Last schedule player not found.');
    }

    /**
     * Delete a scheduled show. Requires 'manage_timeslots' unless the
     * show is owned by the user.
     *
     * @param id
     *
     * @route DELETE /v2/shows/(:id:)
     */
    public function delete()
    {
        $this->user->require_authenticated();

        $id        = trim($this->data('id'));

        // check timeslot.  we can delete a show that we have scheduled, regardless of whether we own that timeslot anymore.
        $show = $this->models->shows('get_show_by_id', $id);
        if ($show['user_id'] != $this->user->param('id')) {
            $this->user->require_permission('manage_timeslots');
        }

        $this->models->shows('delete_show', $id);

        //T Show deleted.
        return array(true,'Show deleted.');
    }

    /**
     * Edit or save a new show.
     *
     * @param id Optional when saving a new show.
     * @param player_id
     * @param mode One time, or every X interval.
     * @param x_data When mode is set to an interval, x_data specifies every X.
     * @param start
     * @param duration
     * @param stop
     * @param item_type
     * @param item_id
     *
     * @route POST /v2/shows
     * @route PUT /v2/shows/(:id:)
     */
    public function save()
    {
        $this->user->require_authenticated();

        $id             = trim($this->data('id'));

        if ($this->api_version() === 2) {
            if ($this->api_request_method() === 'POST') {
                $id = null;
            }
        }

        $data['player_id'] = trim($this->data('player_id'));
        $data['mode']      = trim($this->data('mode'));
        $data['x_data']    = trim($this->data('x_data'));

        $data['start']     = trim($this->data('start'));
        $data['duration']  = trim($this->data('duration'));
        $data['stop']      = trim($this->data('stop'));

        $data['item_type'] = trim($this->data('item_type'));
        $data['item_id']   = trim($this->data('item_id'));

        // if we are editing, make sure ID is valid.
        if (!empty($id)) {
            $original_show_data = $this->models->shows('get_show_by_id', $id);
            //T Show not found.
            if (!$original_show_data) {
                return array(false, 'Show not found.');
            }

            // if we're editing someone elses show, we need to be a schedule admin.
            if ($original_show_data['user_id'] != $this->user->param('id')) {
                $this->user->require_permission('manage_timeslots');
            }
        }

        // validate show
        $validate = $this->models->shows('validate_show', $data, $id);
        if ($validate[0] == false) {
            return array(false, $validate[1]);
        }

        // generate our duration in seconds.
        $duration = (int) $data['duration'];

        //T The duration is not valid.
        if (empty($duration)) {
            return array(false, 'The duration is not valid.');
        }
        $data['duration'] = $duration;

        // collision check!
        $collision_timeslot_check = $this->models->shows('collision_timeslot_check', $data, $id);
        if ($collision_timeslot_check[0] == false) {
            return array(false, $collision_timeslot_check[1]);
        }

        // FINALLY CREATE/EDIT SHOW
        $this->models->shows('save_show', $data, $id);

        //T Show added.
        return array(true,'Show added.');
    }
}
