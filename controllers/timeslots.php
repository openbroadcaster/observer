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
 * Timeslots controller schedules player timeslots for shows.
 *
 * @package Controller
 */
class Timeslots extends OBFController
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Get timeslot by ID. Requires 'manage_timeslots'.
     *
     * @param id
     *
     * @return timeslot
     *
     * @route GET /v2/timeslots/(:id:)
     */
    public function get()
    {
        $id = $this->data('id');

        $timeslot = $this->models->timeslots('get_timeslot_by_id', $id);

        $this->user->require_permission('manage_timeslots:' . $timeslot['player_id']);

        //T Timeslot not found.
        if (!$timeslot) {
            return array(false, 'Timeslot not found.');
        }
        //T Timeslot.
        return array(true, 'Timeslot.', $timeslot);
    }

    /**
     * Get timeslot data between two given date/times. Also used to
     * get timeslot data for provided user. This function is used for the UI/API,
     * but also to detect potential scheduling collisions. Requires
     * 'manage_timeslots' unless requesting information about logged
     * in (i.e. current) user.
     *
     * @param start
     * @param end
     * @param player
     * @param user_id
     *
     * @return timeslots
     *
     * @route GET /v2/timeslots
     */
    public function search()
    {
        $this->user->require_authenticated();

        $start   = $this->data('start');
        $end     = $this->data('end');
        $player  = $this->data('player');
        $user_id = $this->data('user_id');

        //T Player ID is invalid.
        if (!preg_match('/^[0-9]+$/', $player)) {
            return array(false, 'Player ID is invalid.');
        }

        // require "manage timeslots" permission unless we are getting the timeslots for our own user.
        if ($user_id != $this->user->param('id')) {
            $this->user->require_permission('manage_timeslots:' . $player);
        }

        //T Player ID is invalid.
        if (!preg_match('/^[0-9]+$/', $player)) {
            return array(false, 'Player ID is invalid.');
        }
        //T Start or end date is invalid.
        if (!preg_match('/^[0-9]+$/', $start) || !preg_match('/^[0-9]+$/', $end)) {
            return array(false, 'Start or end date is invalid.');
        }
        if ($start >= $end) {
            return array(false, 'Start or end date is invalid.');
        }
        //T User ID is invalid.
        if (!empty($user_id) && !preg_match('/^[0-9]+$/', $user_id)) {
            return array(false, 'User ID is invalid.');
        }

        // check if player is valid.
        $this->db->where('id', $player);
        $player_data = $this->db->get_one('players');

        //T Player ID is invalid.
        if (!$player_data) {
            return array(false, 'Player ID is invalid.');
        }

        $data = $this->models->timeslots('get_timeslots', $start, $end, $player, false, $user_id);

        //T Schedule timeslots.
        return array(true, 'Schedule timeslots.', $data);
    }

    /**
     * Set the last selected player so we can view timeslots for that player
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
            $this->user->set_setting('last_timeslots_player', $player_id);
            //T Set last timeslots player.
            return array(true, 'Set last timeslots player.');
        }
        //T Player not found.
        return array(false, 'Player not found.');
    }

    /**
     * Get the last selected player on the timeslots page for the current user.
     *
     * @return player
     */
    public function get_last_player()
    {
        $player_id = $this->user->get_setting('last_timeslots_player');
        //T Last timeslots player.
        if ($player_id) {
            return array(true, 'Last timeslots player.', $player_id);
        }
        //T Last timeslots player not found.
        return array(false,'Last timeslots player not found.');
    }

    /**
     * Delete a timeslot by ID. Requires 'manage_timeslots' for the player ID
     * linked to the timeslot.
     *
     * @param id
     *
     * @route DELETE /v2/timeslots/(:id:)
     */
    public function delete()
    {
        $id        = trim($this->data('id'));

        // make sure timeslot exists, check user permissions against player ID.
        $timeslot = $this->models->timeslots('get_timeslot_by_id', $id);

        //T Timeslot not found.
        if (!$timeslot) {
            return array(false, 'Timeslot not found.');
        }
        $this->user->require_permission('manage_timeslots:' . $timeslot['player_id']);

        $this->models->timeslots('delete_timeslot', $id);

        //T Timeslot deleted.
        return array(true, 'Timeslot deleted.');
    }

    /**
     * Edit or save a timeslot.
     *
     * @param id Optional when saving a new timeslot.
     * @param user_id
     * @param player_id
     * @param mode One time, or every X interval.
     * @param x_data When mode is set to an interval, x_data specifies every X.
     * @param description
     * @param start
     * @param duration
     * @param stop
     *
     * @route POST /v2/timeslots
     */
    public function save()
    {
        $id             = trim($this->data('id'));

        $data['user_id']     = trim($this->data('user_id'));
        $data['player_id']   = trim($this->data('player_id'));
        $data['mode']        = trim($this->data('mode'));
        $data['x_data']      = trim($this->data('x_data'));

        $data['description'] = trim($this->data('description'));

        $data['start']       = trim($this->data('start'));
        $data['duration']    = trim($this->data('duration'));
        $data['stop']        = trim($this->data('stop'));

        // if we are editing, make sure ID is valid.
        if (!empty($id)) {
            $original_timeslot = $this->models->timeslots('get_timeslot_by_id', $id);
            //T Timeslot not found.
            if (!$original_timeslot) {
                return array(false, 'Timeslot not found.');
            }
        }

        $validate = $this->models->timeslots('validate_timeslot', $data, $id);
        if ($validate[0] == false) {
            return array(false, $validate[1]);
        }

        // make sure we have permission to save for this player.
        $this->user->require_permission('manage_timeslots:' . $data['player_id']);

        // generate our duration in seconds.
        $duration = (int) $data['duration'];

        //T The duration is not valid.
        if (empty($duration)) {
            return array(false, 'The duration is not valid.');
        }
        $data['duration'] = $duration;

        // collision check!
        $collision_check = $this->models->timeslots('collision_check', $data, $id);
        if ($collision_check[0] == false) {
            return array(false, $collision_check[1]);
        }

        // FINALLY CREATE/EDIT TIMESLOT
        $this->models->timeslots('save_timeslot', $data, $id);

        //T Timeslot added.
        return array(true, 'Timeslot added.');
    }
}
