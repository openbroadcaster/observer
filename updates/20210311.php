<?php

class OBUpdate20210311 extends OBUpdate
{
    public function items()
    {
        $updates   = [];
        $updates[] = 'Migrate shows to new database structure.';
        return $updates;
    }

    public function run()
    {
        // get player timezones
        $players = $this->models->players('get_all');
        if (!$players) {
            return true;
        } // no players, no shows.
        $timezones = [];
        foreach ($players as $player) {
            $timezones[$player['id']] = $player['timezone'];
        }

        // migrate shows
        $shows_single = $this->db->get('schedules');
        $shows_recurring = $this->db->get('schedules_recurring');
        $shows = array_merge($shows_single, $shows_recurring);

        foreach ($shows as $show) {
            // get local start time
            if (!$timezones[$show['player_id']]) {
                continue;
            } // invalid, no player timezone.
            $start = new DateTime('now', new DateTimeZone($timezones[$show['player_id']]));
            $start->setTimestamp($show['start']);

            // handle stop
            $mode = $show['mode'] ?? 'once';
            if ($mode != 'once') {
                $stop = new DateTime('now', new DateTimeZone($timezones[$show['player_id']]));
                $stop->setTimestamp($show['stop']);
                $stop_formatted = $stop->format('Y-m-d');
            } else {
                $stop_formatted = null;
            }

            $data = [
            'user_id' => $show['user_id'],
            'player_id' => $show['player_id'],
            'mode' => $mode,
            'x_data' => $show['x_data'] ?? 0,
            'item_id' => $show['item_id'],
            'item_type' => $show['item_type'],
            'start' => $start->format('Y-m-d H:i:s'),
            'duration' => $show['duration'],
            'stop' => $stop_formatted
            ];

            $validate = $this->models->shows('validate_show', $data, false, true);
            if (!$validate[0]) {
                continue;
            } // invalid show, skipping

            $collision = $this->models->shows('collision_timeslot_check', $data, false, false, true);
            if (!$collision[0]) {
                continue;
            } // conflict, skipping

            $this->models->shows('save_show', $data);
        }

        return true;
    }
}
