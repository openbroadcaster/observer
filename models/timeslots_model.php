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
 * Model for timeslots.
 *
 * @package Model
 */
class TimeslotsModel extends OBFModel
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Get timeslots associated with a player.
     *
     * @param start
     * @param end
     * @param player
     * @param not_entry Exclude a specific entry. Default FALSE.
     * @param user_id Limit timeslots to specific user ID. Default FALSE.
     *
     * @return timeslots
     */
    public function get_timeslots($start, $end, $player, $not_entry = false, $user_id = false)
    {

    // get player (for timezone)
        $this->db->where('id', $player);
        $player_data = $this->db->get_one('players');

        // set our timezone based on player settings.  this makes sure 'strtotime' advancing by days, weeks, months will account for DST propertly.
        date_default_timezone_set($player_data['timezone']);

        // init
        $data = array();

        $query =  ("SELECT timeslots.*,users.display_name AS user,timeslots_expanded.start AS exp_start,timeslots_expanded.end AS exp_end,timeslots_expanded.id AS exp_id FROM timeslots LEFT JOIN users ON users.id = timeslots.user_id LEFT JOIN timeslots_expanded ON timeslots_expanded.timeslot_id = timeslots.id ");
        $query .= ("WHERE timeslots.player_id = '" . $this->db->escape($player) . "' ");
        $query .= ("AND timeslots_expanded.end > '" . $this->db->escape(date('Y-m-d H:i:s', $start)) . "' ");
        $query .= ("AND timeslots_expanded.start < '" . $this->db->escape(date('Y-m-d H:i:s', $end)) . "' ");
        if ($not_entry) {
            $query .= ("AND timeslots.id != '" . $this->db->escape($not_entry['id']) . "' ");
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

        return $data;
    }

    /**
     * Get timeslot by ID.
     *
     * @param id
     *
     * @return timeslot
     */
    public function get_timeslot_by_id($id)
    {
        $this->db->where('id', $id);
        $row = $this->db->get_one('timeslots');

        if (!$row) {
            return false;
        }

        $row['duration'] = strtotime($row['timeslot_end']) - strtotime($row['start']);

        return $row;
    }

    /**
     * Delete a timeslot.
     *
     * @param id
     * @param recurring Boolean for recurring item. Default FALSE.
     */
    public function delete_timeslot($id, $recurring = false)
    {
        $this->db->where('id', $id);
        $this->db->delete('timeslots');

        return true;
    }

    /**
     * Validate a timeslot
     *
     * @param data
     * @param id Updating an existing timeslot. Default FALSE.
     *
     * @return [is_valid, msg]
     */
    public function validate_timeslot($data, $id = false)
    {

    // make sure data is valid.
        if (
            $data['description'] == '' || empty($data['player_id']) || empty($data['user_id']) || empty($data['mode']) || empty($data['start']) ||
            (empty($data['x_data']) && ($data['mode'] == 'xdays' || $data['mode'] == 'xweeks' || $data['mode'] == 'xmonths')) || ($data['mode'] != 'once' && empty($data['stop']))
        ) {
            //T One or more required fields were not filled.
            return array(false, 'One or more required fields were not filled.');
        }

        // check if user is valid.
        //T The user you have selected does not exist.
        if (!$this->db->id_exists('users', $data['user_id'])) {
            return array(false,'The user you have selected does not exist.');
        }

        // check if player is valid.
        $this->db->where('id', $data['player_id']);
        $player_data = $this->db->get_one('players');

        //T The player you have selected does not exist.
        if (!$player_data) {
            return array(false,'The player you have selected does not exist.');
        }

        // set our timezone based on player settings.  this makes sure 'strtotime' advancing by days, weeks, months will account for DST propertly.
        date_default_timezone_set($player_data['timezone']);

        // check valid scheduling mode
        if (array_search($data['mode'], array('once','daily','weekly','monthly','xdays','xweeks','xmonths')) === false) {
            //T The selected scheduling mode is not valid.
            return array(false,'The selected scheduling mode is not valid.');
        }

        // check if start date is valid.
        //T The start date/time is not valid.
        //if(!preg_match('/^[0-9]+$/',$data['start'])) return array(false,'The start date/time is not valid.');
        $dt_start = DateTime::createFromFormat('Y-m-d H:i:s', $data['start'], new DateTimeZone('UTC'));
        if (!$dt_start) {
            return [false, 'The start date/time is not valid.'];
        }

        // check if the stop date is valid.
        //T The stop (last) date is not valid and must come after the start date/time.
        //if($data['mode']!='once' && !preg_match('/^[0-9]+$/',$data['stop'])) return array(false,'The stop (last) date is not valid and must come after the start date/time.');
        $dt_stop = DateTime::createFromFormat('Y-m-d H:i:s', $data['stop'] . ' 00:00:00', new DateTimeZone('UTC'));
        if ($dt_stop) {
            $dt_stop->add(new DateInterval('P1D'));
        } // include stop date as last date
        if (($data['mode'] != 'once') && (!$dt_stop || ($dt_start >= $dt_stop))) {
            return [false, 'The stop (last) date is not valid and must come after the start date/time.'];
        }

        //T The stop (last) date is not valid and must come after the start date/time.
        //if($data['mode']!='once' && $data['start']>=$data['stop']) return array(false,'The stop (last) date is not valid and must come after the start date/time.');

        // check if x data is valid.
        if (!empty($data['x_data']) && (!preg_match('/^[0-9]+$/', $data['x_data']) || $data['x_data'] > 65535)) {
            //T The recurring frequency is not valid.
            return array(false,'The recurring frequency is not valid.');
        }

        return array(true,'Valid');
    }

    /**
     * Check for collisions on a timeslot.
     *
     * @param data
     * @param id Existing show ID. FALSE by default.
     *
     * @return [is_colliding, msg]
     */
    public function collision_check($data, $id = false)
    {
        $duration = $data['duration'];

        // does this collide with another timeslot?
        if (!empty($id)) {
            $not_entry = array('id' => $id);
        } else {
            $not_entry = false;
        }

        $collision_check = array();

        if ($data['mode'] == 'once') {
            $collision_check[] = $data['start'];
        } else {
      //T Recurring timeslots cannot be longer than 28 days.
            if ($duration > 2419200) {
                return array(false,'Recurring timeslots cannot be longer than 28 days.');
            }

            // this is a recurring item.  make sure we don't collide with ourselves.
            //T A timeslot scheduled daily cannot be longer than a day.
            if ($data['mode'] == 'daily' && $duration > 86400) {
                return array(false,'A timeslot scheduled daily cannot be longer than a day.');
            }
            //T A timeslot scheduled weekly cannot be longer than a week.
            if ($data['mode'] == 'weekly' && $duration > 604800) {
                return array(false,'A timeslot scheduled weekly cannot be longer than a week.');
            }
            //T A scheduled timeslot cannot be longer than its frequency.
            if ($data['mode'] == 'xdays' && $duration > 86400 * $data['x_data']) {
                return array(false,'A scheduled timeslot cannot be longer than its frequency.');
            }
            //T A scheduled timeslot cannot be longer than its frequency.
            if ($data['mode'] == 'xweeks' && $duration > 604800 * $data['x_data']) {
                return array(false,'A scheduled timeslot cannot be longer than its frequency.');
            }

            // this is a recurring item.  determine the times to use for collision checks.
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

            /*$tmp_time = $data['start'];

            while($tmp_time < $data['stop'])
            {

              $collision_check[]=$tmp_time;
              $tmp_time = strtotime($interval,$tmp_time);

            }*/

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

            $query  = "SELECT timeslots_expanded.*,timeslots.player_id AS player FROM timeslots_expanded LEFT JOIN timeslots ON timeslots.id = timeslots_expanded.timeslot_id WHERE ";
            $query .= "timeslots_expanded.end > '" . $this->db->escape($start) . "' AND ";
            $query .= "timeslots_expanded.start < '" . $this->db->escape($end) . "' AND ";
            if ($not_entry) {
                $query .= "timeslots_expanded.timeslot_id != '" . $this->db->escape($not_entry['id']) . "' AND ";
            }
            $query .= "timeslots.player_id = " . $this->db->escape($data['player_id']) . ";";
            $this->db->query($query);
            $result = $this->db->assoc_list();

            if (count($result) > 0) {
                return [false, 'This timeslot conflicts with another on the schedule.'];
            }
        }

        return array(true,'No collision found.');
    }

    /**
     * Save a timeslot.
     *
     * @param data
     * @param id Updating an existing timeslot. Default FALSE.
     */
    public function save_timeslot($data, $id = false)
    {
        $duration = $data['duration'];

        // if editing, we delete our existing timeslot then add a new one.  (might be another type).
        if (!empty($id)) {
            $this->db->where('id', $id);
            $this->db->delete('timeslots');
        }

        $dbdata = array();

        $dbdata['player_id'] = $data['player_id'];
        $dbdata['user_id'] = $data['user_id'];
        $dbdata['start'] = $data['start'];
        // $dbdata['duration']=$duration;
        $timeslot_end = new DateTime($data['start'], new DateTimeZone('UTC'));
        $timeslot_end->add(new DateInterval('PT' . $duration . 'S'));
        $dbdata['timeslot_end'] = $timeslot_end->format('Y-m-d H:i:s');

        $dbdata['description'] = $data['description'];

        if ($data['mode'] != 'once') {
            $dbdata['mode'] = $data['mode'];
            /*$dbdata['x_data']=$data['x_data'];
            $dbdata['stop']=$data['stop'];*/
            $dbdata['recurring_interval'] = $data['x_data'];
            $dbdata['recurring_end'] = $data['stop'];

            $recurring_id = $this->db->insert('timeslots', $dbdata);

            $tmp_time = new DateTime($dbdata['start'], new DateTimeZone('UTC'));
            $stop_time = new DateTime($data['stop'] . ' 00:00:00', new DateTimeZone('UTC'));
            $stop_time->add(new DateInterval('P1D'));
            // $stop_time->sub(new DateInterval('PT' . $data['duration'] . 'S'));

            $expanded_data = array();
            //$expanded_data['recurring_id']=$recurring_id;
            $expanded_data['timeslot_id'] = $recurring_id;
            while ($tmp_time < $stop_time) {
                $expanded_data['start'] = $tmp_time->format('Y-m-d H:i:s');
                $end = new DateTime($expanded_data['start']);
                $end->add(new DateInterval('PT' . $data['duration'] . 'S'));
                $expanded_data['end'] = $end->format('Y-m-d H:i:s');

                $this->db->insert('timeslots_expanded', $expanded_data);

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
                        trigger_error('Invalid mode provided. Aborting to avoid infinite timeslots added.', E_USER_ERROR);
                }
            }
        } else {
            //$dbdata['end']=$dbdata['start']+$dbdata['duration']; // we also add 'end' data, which is used to speed up timeslot searching
            $dbdata['mode'] = 'once';
            $dbdata['recurring_interval'] = 0;
            $dbdata['recurring_end'] = $timeslot_end->format('Y-m-d');
            $timeslot_id = $this->db->insert('timeslots', $dbdata);

            $expanded_data = [];
            $expanded_data['timeslot_id'] = $timeslot_id;
            $expanded_data['start'] = $dbdata['start'];
            $expanded_data['end'] = $dbdata['timeslot_end'];
            $this->db->insert('timeslots_expanded', $expanded_data);
        }

        return true;
    }
}
