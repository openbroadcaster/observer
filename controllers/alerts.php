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
 * Manages alerts, also commonly referred to as emergency broadcasts, priorities,
 * or priority broadcasts.
 *
 * @package Controller
 */
class Alerts extends OBFController
{
    public function __construct()
    {
        parent::__construct();

        $this->user->require_authenticated();
    }

    /**
     * Return data about a specific alert ID. 'manage_emergency_broadcasts' is
     * a required permission.
     *
     * @param id
     *
     * @return alert
     */
    public function get()
    {
        $id = trim($this->data('id'));

        if (!empty($id)) {
            $alert = $this->models->emergencies('get_one', ['id' => $id]);
            //T Priority
            //T Priority broadcast not found.
            if (!$alert) {
                return array(false, 'Priority broadcast not found.');
            }
            $this->user->require_permission('manage_emergency_broadcasts:' . $alert['player_id']);
            //T Priority Broadcast
            return array(true, 'Priority Broadcast', $alert);
        }
    }

    /**
     * Get all alerts for a specific player ID. 'manage_emergency_broadcasts'
     * is a required permission.
     *
     * @param player_id
     *
     * @return alerts
     */
    public function search()
    {
        $player_id = trim($this->data('player_id'));

        if (!empty($player_id)) {
            $this->user->require_permission('manage_emergency_broadcasts:' . $player_id);
            //T Priority Broadcasts
            return array(true, 'Priority Broadcasts', $this->models->emergencies('get_for_player', ['player_id' => $player_id]));
        }
    }

    /**
     * Set the last selected player so we can view alerts for that player
     * immediately when loading the page again some other time. This will
     * have to be generalized for other UI elements at some point. This is a
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
            $this->user->set_setting('last_emergencies_player', $player_id);
            return array(true,'Set last emergencies player.');
        }
        //T This player no longer exists.
        else {
            return array(false,'This player no longer exists.');
        }
    }

    /**
     * Get the last selected player on the priority broadcasts page for the
     * current user.
     *
     * @return player
     */
    public function get_last_player()
    {
        $player_id = $this->user->get_setting('last_emergencies_player');
        if ($player_id) {
            return array(true,'Last emergencies player.',$player_id);
        } else {
            return array(false,'Last emergencies player not found.');
        }
    }

    /**
     * Save a new alert. The 'user_id' is set to the currently logged
     * in user for the new broadcast. Requires the 'manage_emergency_broadcasts'
     * permission.
     *
     * @param id ID of alert. Update a pre-existing alert if set.
     * @param item_id ID of the media item linked to the alert.
     * @param player_id
     * @param name
     * @param frequency
     * @param duration
     * @param start
     * @param stop
     */
    public function save()
    {
        $id = trim($this->data('id'));
        $data['item_id'] = trim($this->data('item_id'));

        $data['player_id'] = trim($this->data('player_id'));

        $data['name'] = trim($this->data('name'));

        $data['frequency'] = trim($this->data('frequency'));
        $data['duration'] = trim($this->data('duration'));
        $data['start'] = trim($this->data('start'));
        $data['stop'] = trim($this->data('stop'));

        $data['user_id']=$this->user->param('id');

        $validation = $this->models->emergencies('validate', ['data' => $data, 'id' => $id]);
        //T Priority
        if ($validation[0]==false) {
            return array(false,$validation[1]);
        }

        // check permission on this player.
        $this->user->require_permission('manage_emergency_broadcasts:'.$data['player_id']);

        $this->models->emergencies('save', ['data' => $data, 'id' => $id]);

        return array(true,'Alert saved.');
    }

    /**
     * Delete an alert. Requries the 'manage_emergency_broadcasts'
     * permission.
     *
     * @param id
     */
    public function delete()
    {
        $id = trim($this->data('id'));

        $alert = $this->models->emergencies('get_one', ['id' => $id]);
        if (!$alert) {
            return array(false,'Alert not found.');
        }

        // check permission on appropriate player.
        $this->user->require_permission('manage_emergency_broadcasts:'.$alert['player_id']);

        $this->models->emergencies('delete', ['id' => $id]);

        return array(true,'Alert deleted.');
    }
}
