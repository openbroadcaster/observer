<?php

/*
    Copyright 2012-2024 OpenBroadcaster, Inc.

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
     * Return data about a specific alert ID. 'manage_alerts' is
     * a required permission.
     *
     * @param id
     *
     * @return alert
     *
     * @route GET /v2/alerts/(:id:)
     */
    public function get()
    {
        $id = trim($this->data('id'));

        if (!empty($id)) {
            $alert = $this->models->alerts('get_one', ['id' => $id]);
            //T Alert not found.
            if (!$alert) {
                return array(false, 'Alert not found.');
            }
            $this->user->require_permission('manage_alerts:' . $alert['player_id']);
            //T Alert
            return array(true, 'Alert', $alert);
        }
    }

    /**
     * Get all alerts for a specific player ID. 'manage_alerts'
     * is a required permission.
     *
     * @param player_id
     *
     * @return alerts
     *
     * @route GET /v2/alerts/search/(:player_id:)
     */
    public function search()
    {
        $player_id = trim($this->data('player_id'));

        if (!empty($player_id)) {
            $this->user->require_permission('manage_alerts:' . $player_id);
            //T Alerts
            return array(true, 'Alerts', $this->models->alerts('get_for_player', ['player_id' => $player_id]));
        }
    }

    /**
     * Set the last selected player so we can view alerts for that player
     * immediately when loading the page again some other time. This will
     * have to be generalized for other UI elements at some point. This is a
     * user-specific setting, so no special permissions are necessary.
     *
     * @param player
     *
     * @hidden Deprecated method to be removed in later update.
     */
    public function set_last_player()
    {
        $player_id = $this->data('player');

        $this->db->where('id', $player_id);
        $player_data = $this->db->get_one('players');

        if ($player_data) {
            $this->user->set_setting('last_alerts_player', $player_id);
            return array(true,'Set last alerts player.');
        } else {
            //T This player no longer exists.
            return array(false,'This player no longer exists.');
        }
    }

    /**
     * Get the last selected player on the alerts page for the
     * current user.
     *
     * @return player
     *
     * @hidden Deprecated method to be removed in later update.
     */
    public function get_last_player()
    {
        $player_id = $this->user->get_setting('last_alerts_player');
        if ($player_id) {
            return array(true,'Last alerts player.',$player_id);
        } else {
            return array(false,'Last alerts player not found.');
        }
    }

    /**
     * Save a new alert. The 'user_id' is set to the currently logged
     * in user for the new broadcast. Requires the 'manage_alerts'
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
     *
     * @route POST /v2/alerts
     * @route PUT /v2/alerts/(:id:)
     */
    public function save()
    {
        $id = trim($this->data('id'));

        if ($this->api_version() === 2) {
            if ($this->api_request_method() === 'POST') {
                $id = null;
            }
        }

        $data['item_id'] = trim($this->data('item_id'));
        $data['player_id'] = trim($this->data('player_id'));
        $data['name'] = trim($this->data('name'));

        $data['frequency'] = trim($this->data('frequency'));
        $data['duration'] = trim($this->data('duration'));
        $data['start'] = trim($this->data('start'));
        $data['stop'] = trim($this->data('stop'));

        $data['user_id'] = $this->user->param('id');

        $validation = $this->models->alerts('validate', ['data' => $data, 'id' => $id]);
        if ($validation[0] == false) {
            return array(false,$validation[1]);
        }

        // check permission on this player.
        $this->user->require_permission('manage_alerts:' . $data['player_id']);

        $this->models->alerts('save', ['data' => $data, 'id' => $id]);

        //T Alert saved.
        return array(true,'Alert saved.');
    }

    /**
     * Delete an alert. Requries the 'manage_alerts'
     * permission.
     *
     * @param id
     *
     * @route DELETE /v2/alerts/(:id:)
     */
    public function delete()
    {
        $id = trim($this->data('id'));

        $alert = $this->models->alerts('get_one', ['id' => $id]);
        if (!$alert) {
            return array(false,'Alert not found.');
        }

        // check permission on appropriate player.
        $this->user->require_permission('manage_alerts:' . $alert['player_id']);

        $this->models->alerts('delete', ['id' => $id]);

        return array(true,'Alert deleted.');
    }
}
