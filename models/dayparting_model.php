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
 * Restricts what media is selected during show generation based on days / times
 * / day of the week.
 *
 * @package Model
 */
class DaypartingModel extends OBFModel
{
    public function search($args = [])
    {
        $rows = $this->db->get('dayparting');
        foreach ($rows as &$row) {
            $this->format_row($row);
        }

        return [true,'Dayparting rows.', $rows];
    }

    public function save($args = [])
    {
        OBFHelpers::default_args($args, ['type' => false, 'filters' => '', 'description' => '', 'id' => false, ]);

        $data = [];
        $type = $args['type'];

        //T A valid type is required.
        if ($type != 'time' && $type != 'date' && $type != 'dow') {
            return [false,'A valid type is required.'];
        }

        //T A valid filter must be provided.
        if (json_decode($args['filters']) === null) {
            return [false,'A valid filter must be provided.'];
        }
        $data['filters'] = $args['filters'];

        // we accept either doy (non-leap year) or Y-m-d.
        if ($type == 'date') {
            if (is_numeric($args['start'])) {
                $start = (int) $args['start'];
                if ($start < 0 || $start > 364) {
                    return [false, 'A valid start date is required.'];
                }
                $data['start_doy'] = $start;
            } else {
                $start = DateTime::createFromFormat('Y-m-d', $args['start']);

                //T A valid start date is required.
                if (!$start) {
                    return [false, 'A valid start date is required.'];
                }

                $start->setDate(2021, $start->format('m'), $start->format('d'));
                $data['start_doy'] = $start->format('z');
            }

            if (is_numeric($args['end'])) {
                $end = (int) $args['end'];
                if ($end < 0 || $end > 364) {
                    return [false, 'A valid end date is required.'];
                }
                $data['end_doy'] = $end;
            } else {
                $end = DateTime::createFromFormat('Y-m-d', $args['end']);

                //T A valid end date is required.
                if (!$end) {
                    return [false, 'A valid end date is required.'];
                }

                $end->setDate(2021, $end->format('m'), $end->format('d'));
                $data['end_doy'] = $end->format('z');
            }
        }

        if ($type == 'time') {
            $start = DateTime::createFromFormat('H:i:s', $args['start']);
            $end = DateTime::createFromFormat('H:i:s', $args['end']);

            //T A valid start and end time are required.
            if (!$start || !$end) {
                return [false,'A valid start and end time are required.'];
            }

            $data['start_time'] = $start->format('H:i:s');
            $data['end_time'] = $end->format('H:i:s');
        }

        if ($type == 'dow') {
            $data['dow'] = implode(',', $args['dow'] ? $args['dow'] : []);
            //T At least one day of week is required.
            if ($args['dow'] == '') {
                return [false, 'At least one day of week is required.'];
            }
        }

        $data['description'] = trim($args['description']);

        //T A description is required.
        if ($data['description'] == '') {
            return [false,'A description is required.'];
        }

        if ($args['id']) {
            // specify null values
            foreach (['start_doy','end_doy','start_time','end_time','dow'] as $column) {
                if (!isset($data[$column])) {
                    $data[$column] = null;
                }
            }

            $this->db->where('id', $args['id']);
            $this->db->update('dayparting', $data);
        } else {
            $this->db->insert('dayparting', $data);
        }

        return [true, 'Saved.'];
    }

    public function get($args)
    {
        OBFHelpers::default_args($args, ['id' => 0]);

        $this->db->where('id', $args['id']);
        $row = $this->db->get_one('dayparting');

        if ($row) {
            $this->format_row($row);
            return [true,'Dayparting row.',$row];
        } else {
            return [false,'Row not found.'];
        }
    }

    public function delete($args)
    {
        OBFHelpers::default_args($args, ['id' => 0]);

        $this->db->where('id', $args['id']);
        $this->db->delete('dayparting');
        return [true,'Dayparting row deleted.'];
    }

    private function format_row(&$row)
    {
        if ($row['start_doy'] !== null) {
            $row['type'] = 'date';
            $row['start'] = $row['start_doy'];
            $row['end'] = $row['end_doy'];
        } elseif ($row['start_time'] !== null) {
            $row['type'] = 'time';
            $row['start'] = $row['start_time'];
            $row['end'] = $row['end_time'];
        } else {
            $row['type'] = 'dow';
        }

        unset($row['start_doy']);
        unset($row['end_doy']);
        unset($row['start_time']);
        unset($row['end_time']);
        if ($row['type'] != 'dow') {
            unset($row['dow']);
        }
    }

    // get excluded media ids by datetime
    public function excluded_media_ids($args = [])
    {
        OBFHelpers::default_args($args, ['start_time' => null]);

        $dayparting_exclude_ids = [];
        $start_time = $args['start_time'];
        if ($start_time) {
            // set to 2021 to avoid leap year messing up day of year value
            // consider feb 29 to be feb 28 for dayparting purposes
            $month = $start_time->format('n');
            $day = $start_time->format('j');
            if ($month == 2 && $day == 29) {
                $day = 28;
            }
            $start_time->setDate(2021, $month, $day);

            // get our sql search values
            $dayparting_time = $start_time->format('H:i:s');
            $dayparting_dow = $start_time->format('D');
            $dayparting_doy = $start_time->format('z');

            // get our filters
            $dayparting_filters = [];
            $query = $this->db->query('SELECT * FROM `dayparting` where
        (
          `start_time` IS NOT NULL &&
          `start_time` < `end_time` &&
          "' . $this->db->escape($dayparting_time) . '" >= `start_time` &&
          "' . $this->db->escape($dayparting_time) . '" < `end_time`
        ) ||
        (
          `start_time` IS NOT NULL &&
          `start_time` > `end_time` &&
          (
            "' . $this->db->escape($dayparting_time) . '" >= `start_time` ||
            "' . $this->db->escape($dayparting_time) . '" < `end_time`
          )
        ) ||
        (
          `start_doy` IS NOT NULL &&
          `start_doy` <= `end_doy` &&
          ' . (int) $this->db->escape($dayparting_doy) . ' >= `start_doy` &&
          ' . (int) $this->db->escape($dayparting_doy) . ' <= `end_doy`
        ) ||
        (
          `start_doy` IS NOT NULL &&
          `start_doy` > `end_doy` &&
          (
            ' . (int) $this->db->escape($dayparting_doy) . ' >= `start_doy` ||
            ' . (int) $this->db->escape($dayparting_doy) . ' <= `end_doy`
          )
        ) ||
        (
          `dow` IS NOT NULL &&
          FIND_IN_SET("' . $this->db->escape($dayparting_dow) . '", `dow`)
        )');

            $dayparting_rows = $this->db->assoc_list();

            foreach ($dayparting_rows as $dayparting_row) {
                $media_search = $this->models->media('search', ['params' => ['query' => json_decode($dayparting_row['filters'], true)], 'player_id' => false, 'random_order' => false, 'include_private' => true]);
                if (!empty($media_search[0])) {
                    foreach ($media_search[0] as $media) {
                        $dayparting_exclude_ids[] = $media['id'];
                    }
                }
            }
        }

        return $dayparting_exclude_ids;
    }
}
