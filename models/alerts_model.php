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
 * @package Model
 */
class AlertsModel extends OBFModel
{
    /**
     * Set up the initial parts of a database query, selecting media and
     * alert items from the tables, and joining media items on the item ID
     * in alerts.
     */
    public function get_init($args = [])
    {
        $this->db->what('media.title', 'title');
        $this->db->what('media.artist', 'artist');
        $this->db->what('media.type', 'item_type');
        $this->db->what('media.duration', 'item_duration');
        $this->db->what('alerts.id', 'id');
        $this->db->what('alerts.item_id', 'item_id');
        $this->db->what('alerts.duration', 'duration');
        $this->db->what('alerts.frequency', 'frequency');
        $this->db->what('alerts.name', 'name');
        $this->db->what('alerts.start', 'start');
        $this->db->what('alerts.stop', 'stop');
        $this->db->what('alerts.player_id', 'player_id');

        $this->db->leftjoin('media', 'alerts.item_id', 'media.id');
    }

    /**
     * Get an alert and its associated media item.
     *
     * @param id
     *
     * @return alert
     */
    public function get_one($args = [])
    {
        OBFHelpers::require_args($args, ['id']);

        $this('get_init');

        $this->db->where('alerts.id', $args['id']);

        $alert = $this->db->get_one('alerts');

        if (!$alert) {
            return false;
        }

        $alert['item_name'] = $alert['artist'] . ' - ' . $alert['title'];
        unset($alert['artist']);
        unset($alert['title']);

        if ($alert['item_type'] != 'image') {
            $alert['duration'] = $alert['item_duration'];
        }
        unset($alert['item_duration']);

        return $alert;
    }

    /**
     * Get the alerts associated with a player.
     *
     * @param player_id
     *
     * @return alerts
     */
    public function get_for_player($args = [])
    {
        OBFHelpers::require_args($args, ['player_id']);

        $this('get_init');

        $this->db->where('alerts.player_id', $args['player_id']);

        $alerts = $this->db->get('alerts');

        foreach ($alerts as $index => $alert) {
            $alerts[$index]['item_name'] = $alert['artist'] . ' - ' . $alert['title'];
            unset($alerts[$index]['artist']);
            unset($alerts[$index]['title']);

            if ($alerts[$index]['item_type'] != 'image') {
                $alerts[$index]['duration'] = $alerts[$index]['item_duration'];
            }
            unset($alerts[$index]['item_duration']);
        }

        return $alerts;
    }

    /**
     * Validate the data for updating or inserting an alert.
     *
     * @param data
     * @param id FALSE by default, set when updating existing alert.
     *
     * @return is_valid
     */
    public function validate($args = [])
    {
        OBFHelpers::require_args($args, ['data']);
        OBFHelpers::default_args($args, ['id' => false]);

        foreach ($args['data'] as $key => $value) {
            $$key = $value;
        }

        // required fields?
        if (empty($name) || empty($player_id) || empty($item_id) || empty($frequency) || empty($start) || empty($stop)) {
            return array(false,'Required Field Missing');
        }

        // check if ID is valid (if editing)

        if (!empty($args['id'])) {
            //T The item you are attempting to edit does not appear to exist.
            if (!$this->db->id_exists('alerts', $args['id'])) {
                return array(false,'The item you are attempting to edit does not appear to exist.');
            }
        }

        // check if player ID is valid
        //T This player does not appear to exist.
        if (!$this->db->id_exists('players', $player_id)) {
            return array(false,'This player does not appear to exist.');
        }

        // check if media ID is valid
        if (empty($item_id)) {
            return array(false,'Media Invalid');
        }
        $this->db->where('id', $item_id);
        $media = $this->db->get_one('media');
        if (!$media) {
            return array(false,'Media Invalid');
        }
        //T Media must be approved.
        if ($media['is_approved'] == 0) {
            return array(false,'Media must be approved.');
        }
        //T Media must not be archived.
        if ($media['is_archived'] == 1) {
            return array(false,'Media must not be archived.');
        }

        // is frequency valid?
        //T The frequency is invalid.
        if (!preg_match('/^[0-9]+$/', $frequency) || $frequency < 1) {
            return array(false,'The frequency is invalid.');
        }

        // is duration valid? only needed for images...
        if ($media['type'] == 'image' && (!preg_match('/^[0-9]+$/', $duration) || $duration < 1)) {
            return array(false,'Duration Invalid');
        }

        // is start/stop valid?
        //T The start date/time is invalid.
        if (!preg_match('/^[0-9]+$/', $start)) {
            return array(false,'The start date/time is invalid.');
        }
        //T The stop date/time is invalid.
        if (!preg_match('/^[0-9]+$/', $stop)) {
            return array(false,'The stop date/time is invalid.');
        }
        //T The stop date/time must occur after the start date/time.
        if ($start >= $stop) {
            return array(false,'The stop date/time must occur after the start date/time.');
        }

        return array(true,'');
    }

    /**
     * Update or insert an alert.
     *
     * @param data
     * @param id FALSE by default, set when updating existing alert.
     */
    public function save($args = [])
    {
        OBFHelpers::require_args($args, ['data']);
        OBFHelpers::default_args($args, ['id' => false]);

        $this->db->where('id', $args['data']['item_id']);
        $media = $this->db->get_one('media');
        if ($media['type'] != 'image') {
            unset($args['data']['duration']);
        } // duration not needed unless this is an image.

        if (empty($args['id'])) {
            $this->db->insert('alerts', $args['data']);
        } else {
            $this->db->where('id', $args['id']);
            $this->db->update('alerts', $args['data']);
        }
    }

    /**
     * Delete an alert.
     *
     * @param id
     */
    public function delete($args = [])
    {
        OBFHelpers::require_args($args, ['id']);

        $this->db->where('id', $args['id']);
        $this->db->delete('alerts');
    }
}
