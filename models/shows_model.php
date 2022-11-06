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
 * Manages schedules and shows.
 *
 * @package Model
 */
class ShowsModel extends OBFModel
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Get shows scheduled on a player.
     *
     * @param start
     * @param end
     * @param player
     * @param not_entry Exclude a specific entry from search. Default FALSE.
     *
     * @return shows
     */
    public function get_shows($start, $end, $player, $not_entry = false)
    {

    // get player (for timezone)
        $this->db->where('id', $player);
        $player_data = $this->db->get_one('players');

        // set our timezone based on player settings.  this makes sure 'strtotime' advancing by days, weeks, months will account for DST propertly.
        date_default_timezone_set($player_data['timezone']);

        // init
        $data = array();

        $query =  ("SELECT shows.*,users.display_name AS user,shows_expanded.start AS exp_start,shows_expanded.end AS exp_end,shows_expanded.id AS exp_id FROM shows LEFT JOIN users ON users.id = shows.user_id LEFT JOIN shows_expanded ON shows_expanded.show_id = shows.id ");
        $query .= ("WHERE shows.player_id = '" . $this->db->escape($player) . "' ");
        $query .= ("AND shows_expanded.end > '" . $this->db->escape(date('Y-m-d H:i:s', $start)) . "' ");
        $query .= ("AND shows_expanded.start < '" . $this->db->escape(date('Y-m-d H:i:s', $end)) . "' ");
        if ($not_entry) {
            $query .= ("AND shows.id != '" . $this->db->escape($not_entry['id']) . "' ");
        }
        $query .= ";";
        $this->db->query($query);

        $rows = $this->db->assoc_list();
        foreach ($rows as $row) {
            $row['start'] = strtotime($row['exp_start']);
            $row['recurring_start'] = strtotime($row['start']);
            $row['recurring_stop'] = strtotime($row['recurring_end']);
            $row['x_data'] = $row['recurring_interval'];
            $row['duration'] = strtotime($row['exp_end']) - $row['start'];

            // JS expects mode to not be set in non-recurring items. So we'll just unset
            // the row instead of fiddling with the view's side of things.
            if ($row['mode'] == 'once') {
                unset($row['mode']);
            }

            $data[] = $row;
        }


        // get media/playlist name for each item.
        foreach ($data as $index => $item) {
      // $this->db->where('id',$item['item_id']);

            if ($item['item_type'] == 'playlist') {
                $playlist = $this->models->playlists('get_by_id', $item['item_id']);
                $data[$index]['name'] = $playlist['name'];
                $data[$index]['description'] = $playlist['description'];
                $data[$index]['owner'] = $playlist['owner_name'];
                $data[$index]['type'] = $playlist['type'];
            } elseif ($item['item_type'] == 'media') {
                $media = $this->models->media('get_by_id', ['id' => $item['item_id']]);
                $data[$index]['name'] = $media['title'];
                $data[$index]['owner'] = $media['owner_name'];
                $data[$index]['type'] = 'standard';
            } elseif ($item['item_type'] == 'linein') {
                $data[$index]['name'] = 'Line-In';
                $data[$index]['type'] = 'standard';
                $data[$index]['owner'] = 'n/a';
            }
        }

        return $data;
    }

    /**
     * Get a show by ID.
     *
     * @param id
     *
     * @return show
     */
    public function get_show_by_id($id)
    {
        $this->db->where('id', $id);
        $row = $this->db->get_one('shows');

        if (!$row) {
            return false;
        }

        // if linein, we don't need item data so return early.
        if ($row['item_type'] == 'linein') {
            return $row;
        }

        // get item data
        $this->db->where('id', $row['item_id']);
        if ($row['item_type'] == 'media') {
            $media = $this->db->get_one('media');
            $row['item_name'] = $media['artist'] . ' - ' . $media['title'];
        } elseif ($row['item_type'] == 'playlist') {
            $playlist = $this->db->get_one('playlists');
            $row['item_name'] = $playlist['name'];
        }

        $row['duration'] = strtotime($row['show_end']) - strtotime($row['start']);
        $row['x_data'] = $row['recurring_interval'];

        return $row;
    }

    /**
     * Delete a show by ID.
     *
     * @param id
     */
    public function delete_show($id)
    {

        // get show information, start time and player id needed for liveassist cache delete
        $this->db->where('id', $id);
        $show = $this->db->get_one('shows');

        if (!$show) {
            return false;
        }

        // proceed with delete.
        $this->db->where('id', $id);
        $this->db->delete('shows');

        return true;
    }

    /**
     * Validate a show
     *
     * @param data
     * @param id Set when updating existing show. Default FALSE.
     *
     * @return [is_valid, msg]
     */
    public function validate_show($data, $id = false, $skip_permission_check = false)
    {
        // return [true, 'Temporary valid TODO'];

        // make sure data is valid.
        if (
            empty($data['player_id']) || empty($data['mode']) || empty($data['start'])
            || (empty($data['x_data']) && ($data['mode'] == 'xdays' || $data['mode'] == 'xweeks' || $data['mode'] == 'xmonths'))
            || ($data['mode'] != 'once' && empty($data['stop']))
            || (empty($id) && (empty($data['item_type']) || ($data['item_type'] != 'linein' && empty($data['item_id']))))
        ) {
    //T One or more required fields were not filled.
            return array(false,'One or more required fields were not filled.');
        }

        // check if player is valid.
        $this->db->where('id', $data['player_id']);
        $player_data = $this->db->get_one('players');

        //T This player no longer exists.
        if (!$player_data) {
            return array(false,'This player no longer exists.');
        }

        // set our timezone based on player settings.  this makes sure 'strtotime' advancing by days, weeks, months will account for DST propertly.
        date_default_timezone_set($player_data['timezone']);

        // make sure item type/id is valid (if not editing)
        if (empty($id)) {
            if ($data['item_type'] != 'playlist' && $data['item_type'] != 'media' && $data['item_type'] != 'linein') {
                return array(false,'Item Invalid');
            }

            //T The item you are attempting to schedule is  .
            if (empty($data['item_id']) && $data['item_type'] != 'linein') {
                return array(false,'The item you are attempting to schedule is not valid.');
            }

            if ($data['item_type'] == 'playlist') {
                $this->db->where('id', $data['item_id']);
                $playlist = $this->db->get_one('playlists');

                //T The item you are attempting to schedule does not exist.
                if (!$playlist) {
                    return array(false,'The item you are attempting to schedule does not exist.');
                }

                // don't allow use of private playlist unless playlist manager or owner of this playlist.
                if (!$skip_permission_check && $playlist['status'] == 'private' && $playlist['owner_id'] != $this->user->param('id')) {
                    $this->user->require_permission('manage_playlists');
                }
            } elseif ($data['item_type'] == 'media') {
                $this->db->where('id', $data['item_id']);
                $media = $this->db->get_one('media');

                //T The item you are attempting to schedule does not exist.
                if (!$media) {
                    return array(false,'The item you are attempting to schedule does not exist.');
                }

                // don't allow use of private media unless media manage or owner of this media.
                if (!$skip_permission_check && $media['status'] == 'private' && $media['owner_id'] != $this->user->param('id')) {
                    $this->user->require_premission('manage_media');
                }

                //T The media must be approved.
                if ($media['is_approved'] == 0) {
                    return array(false,'The media must be approved.');
                }
                //T The media must not be archived.
                if ($media['is_archived'] == 1) {
                    return array(false,'The media must not be archived.');
                }
            } elseif ($data['item_type'] == 'linein') {
                // make sure linein scheduling is supported by this player.
                //T The item you are attempting to schedule is not valid.
                if (empty($player_data['support_linein'])) {
                    return array(false,'The item you are attempting to schedule is not valid.');
                }
            }
        }

        // check valid scheduling mode
        //T The selected scheduling mode is not valid.
        if (array_search($data['mode'], array('once','daily','weekly','monthly','xdays','xweeks','xmonths')) === false) {
            return array(false,'The selected scheduling mode is not valid.');
        }

        // check if start date is valid.
        //T The start date/time is not valid.
        $dt_start = DateTime::createFromFormat('Y-m-d H:i:s', $data['start']);
        if (!$dt_start) {
            return [false, 'The start date/time is not valid.'];
        }

        // check if the stop date is valid.
        //T The stop (last) date is not valid and must come after the start date/time.
        $dt_stop = DateTime::createFromFormat('Y-m-d', $data['stop']);
        if (($data['mode'] != 'once') && (!$dt_stop || ($dt_start >= $dt_stop))) {
            return [false, 'The stop (last) date is not valid and must come after the start date/time.'];
        }

        // check if x data is valid.
        //T The recurring frequency is not valid.
        if (!empty($data['x_data']) && (!preg_match('/^[0-9]+$/', $data['x_data']) || $data['x_data'] > 65535)) {
            return array(false,'The recurring frequency is not valid.');
        }

        return array(true,'Valid.');
    }

    /**
     * Check if this collides with another scheduled item (excluding item with id = $id).
     *
     * @param data
     * @param id Item to exclude. Default FALSE.
     *
     * @return [is_colliding, msg]
     */
    public function collision_timeslot_check($data, $id = false, $skip_timeslot_check = false)
    {
        if (!empty($id)) {
            $not_entry = array('id' => $id);
        } else {
            $not_entry = false;
        }

        $collision_check = array();

        $duration = $data['duration'];

        if ($data['mode'] == 'once') {
            $collision_check[] = $data['start'];
        } else {
      //T Recurring shows cannot be longer than 28 days.
            if ($duration > 2419200) {
                return array(false,'Recurring shows cannot be longer than 28 days.');
            }

            // this is a recurring item.  make sure we don't collide with ourselves.
            //T A show scheduled daily cannot be longer than a day.
            if ($data['mode'] == 'daily' && $duration > 86400) {
                return array(false,'A show scheduled daily cannot be longer than a day.');
            }
            //T A show scheduled weekly cannot be longer than a week.
            if ($data['mode'] == 'weekly' && $duration > 604800) {
                return array(false,'A show scheduled weekly cannot be longer than a week.');
            }
            //T A show cannot be longer than its frequency.
            if ($data['mode'] == 'xdays' && $duration > 86400 * $data['x_data']) {
                return array(false,'A show cannot be longer than its frequency.');
            }
            //T A show cannot be longer than its frequency.
            if ($data['mode'] == 'xweeks' && $duration > 604800 * $data['x_data']) {
                return array(false,'A show cannot be longer than its frequency.');
            }


            // this is a recurring item.  set up times to check for collisions
            if ($data['mode'] == 'daily' || $data['mode'] == 'weekly' || $data['mode'] == 'monthly') {
                $interval = '+1';
            } else {
                $interval = '+' . $data['x_data'];
            }

            if ($data['mode'] == 'daily' || $data['mode'] == 'xdays') {
                $interval .= ' days';
            } elseif ($data['mode'] == 'weekly' || $data['mode'] == 'xweeks') {
                $interval .= ' weeks';
            } else {
                $interval .= ' months';
            }

            $tmp_time = new DateTime($data['start'], new DateTimeZone('UTC'));
            $stop_time = new DateTime($data['stop'], new DateTimeZone('UTC'));
            $stop_time->add(new DateInterval('P1D'));

            while ($tmp_time < $stop_time) {
                $collision_check[] = $tmp_time->format('Y-m-d H:i:s');

                switch ($data['mode']) {
                    case 'daily':
                        $tmp_time->add(new DateInterval('P1D'));
                        break;
                    case 'weekly':
                        $tmp_time->add(new DateInterval('P7D'));
                        break;
                    case 'monthly':
                        $tmp_time->add(new DateInterval('P1M'));
                        break;
                    case 'xdays':
                        $tmp_time->add(new DateInterval('P' . $data['x_data'] . 'D'));
                        break;
                    case 'xweeks':
                        $tmp_time->add(new DateInterval('P' . ($data['x_data'] * 7) . 'D'));
                        break;
                    case 'xmonths':
                        $tmp_time->add(new DateInterval('P' . $data['x_data'] . 'M'));
                        break;
                    default:
                        trigger_error('Invalid mode provided. Aborting to avoid infinite shows added.', E_USER_ERROR);
                }
            }
        }

        foreach ($collision_check as $check) {
            $start = $check;
            $end = new DateTime($start, new DateTimeZone('UTC'));
            $end->add(new DateInterval('PT' . $duration . 'S'));
            $end = $end->format('Y-m-d H:i:s');

            $this->db->where('shows_expanded.end', $start, '>');
            $this->db->where('shows_expanded.start', $end, '<');
            $this->db->where('shows.player_id', $data['player_id']);
            $this->db->leftjoin('shows', 'shows_expanded.show_id', 'shows.id');
            if ($not_entry) {
                $this->db->where('show_id', $not_entry['id'], '!=');
            }
            $result = $this->db->get('shows_expanded');
            if (count($result) > 0) {
                return [false, 'This show conflicts with another on the schedule.'];
            }
        }

        // check timeslot unless we're a schedule admin or have advanced scheduling permission
        if (!$this->user->check_permission('manage_timeslots or advanced_show_scheduling') && !$skip_timeslot_check) {
            foreach ($collision_check as $check) {
                $check = strtotime($check); // TODO: temporarily using strtotime since timeslots table still uses timestamps.

                $timeslots = $this->models->timeslots('get_timeslots', $check, $check + $duration, $data['player_id'], false, $this->user->param('id'));

                // put our timeslots in order so we can make sure they are adequate.
                usort($timeslots, array($this,'order_show'));

                // make sure there are no gaps in the timeslot between this start and end timestamp.
                $timeslot_check_failed = false;

                // the first timeslot must start at or be equal to the check start.
                if ($timeslots[0]['start'] > $check) {
                    $timeslot_check_failed = true;
                }

                // the last timeslot must end at the end of our timeslot or later.
                if (($timeslots[count($timeslots) - 1]['start'] + $timeslots[count($timeslots) - 1]['duration']) < ($check + $duration)) {
                    $timeslot_check_failed = true;
                }

                // make sure there are no gaps...
                foreach ($timeslots as $index => $timeslot) {
                    if ($index == 0) {
                        $timeslot_last_end = $timeslot['start'] + $timeslot['duration'];
                        continue;
                    }

                    if ($timeslot['start'] > $timeslot_last_end) {
                        $timeslot_check_failed = true;
                        break;
                    }
                }

                //T You do not have permission to schedule an item at the specified time(s).
                if ($timeslot_check_failed) {
                    return array(false,'You do not have permission to schedule an item at the specified time(s).');
                }
            }
        }

        //T No collision, timeslot okay.
        return array(true,'No collision, timeslot okay.');
    }

    /**
     * Save a show.
     *
     * @param data
     * @param id Set when updating an existing show. Unset by default.
     */
    public function save_show($data, $id = false)
    {

    // if editing, we delete our existing show then add a new one.  (might be another type).
        // if editing, we also delete our expanded show data in schedules_recurring_expanded (if recurring)

        if (!empty($id)) {
            $this->db->where('id', $id);
            $show_data = $this->db->get_one('shows');

            $this->db->where('id', $id);
            $this->db->delete('shows');

            // delete from cache
            $this->db->where('schedule_id', $id);
            $this->db->delete('shows_cache');

            // delete from expanded
            $this->db->where('show_id', $id);
            $this->db->delete('shows_expanded');
        }

        $dbdata = array();

        $dbdata['player_id'] = $data['player_id'];
        $dbdata['start'] = $data['start'];
        // $dbdata['duration']=$data['duration'];
        $show_end = new DateTime($data['start'], new DateTimeZone('UTC'));
        $show_end->add(new DateInterval('PT' . $data['duration'] . 'S'));
        $dbdata['show_end'] = $show_end->format('Y-m-d H:i:s');

        // default current logged in user if not set.
        $dbdata['user_id'] = $data['user_id'] ?? $this->user->param('id');
        if (!$dbdata['user_id']) {
            unset($dbdata['user_id']);
        } // zero should be null (default)

        if (!empty($id)) {
            $dbdata['item_id'] = $show_data['item_id'];
            $dbdata['item_type'] = $show_data['item_type'];
        } else {
            $dbdata['item_id'] = $data['item_id'];
            $dbdata['item_type'] = $data['item_type'];
        }

        if ($data['mode'] != 'once') {
            $dbdata['mode'] = $data['mode'];
            //$dbdata['x_data']=$data['x_data'];
            $dbdata['recurring_interval'] = $data['x_data'];
            //$dbdata['stop']=$data['stop'];
            $dbdata['recurring_end'] = $data['stop'];

            $recurring_id = $this->db->insert('shows', $dbdata);

            $tmp_time = new DateTime($dbdata['start'], new DateTimeZone('UTC'));
            $stop_time = new DateTime($data['stop'], new DateTimeZone('UTC'));
            $stop_time->add(new DateInterval('P1D'));
            // $stop_time->sub(new DateInterval('PT' . $data['duration'] . 'S'));

            $expanded_data = array();
            //$expanded_data['recurring_id']=$recurring_id;
            $expanded_data['show_id'] = $recurring_id;
            while ($tmp_time < $stop_time) {
                $expanded_data['start'] = $tmp_time->format('Y-m-d H:i:s');
                $end = new DateTime($expanded_data['start']);
                $end->add(new DateInterval('PT' . $data['duration'] . 'S'));
                $expanded_data['end'] = $end->format('Y-m-d H:i:s');

                $this->db->insert('shows_expanded', $expanded_data);

                switch ($dbdata['mode']) {
                    case 'daily':
                        $tmp_time->add(new DateInterval('P1D'));
                        break;
                    case 'weekly':
                        $tmp_time->add(new DateInterval('P7D'));
                        break;
                    case 'monthly':
                        $tmp_time->add(new DateInterval('P1M'));
                        break;
                    case 'xdays':
                        $tmp_time->add(new DateInterval('P' . $dbdata['recurring_interval'] . 'D'));
                        break;
                    case 'xweeks':
                        $tmp_time->add(new DateInterval('P' . ($dbdata['recurring_interval'] * 7) . 'D'));
                        break;
                    case 'xmonths':
                        $tmp_time->add(new DateInterval('P' . $dbdata['recurring_interval'] . 'M'));
                        break;
                    default:
                        trigger_error('Invalid mode provided. Aborting to avoid infinite shows added.', E_USER_ERROR);
                }
            }
        } else {
            $dbdata['mode'] = 'once';
            $dbdata['recurring_interval'] = 0;
            $dbdata['recurring_end'] = $show_end->format('Y-m-d');

            $show_id = $this->db->insert('shows', $dbdata);

            $expanded_data = [];
            $expanded_data['show_id'] = $show_id;
            $expanded_data['start'] = $dbdata['start'];
            $expanded_data['end'] = $dbdata['show_end'];
            $this->db->insert('shows_expanded', $expanded_data);
        }

        return true;
    }

    /**
     * Private method to get the order of two schedules. Return 1 if A starts after
     * B, -1 otherwise.
     *
     * @param a
     * @param b
     *
     * @return 1|-1
     */
    private function order_show($a, $b)
    {
        if ($a['start'] > $b['start']) {
            return 1;
        } else {
            return -1;
        }
    }
}
