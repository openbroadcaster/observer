<?php

class OBUpdate20210310 extends OBUpdate
{
  public function items()
  {
    $updates   = array();
    $updates[] = 'Migrate timeslots to new database structure.';
    return $updates;
  }

  public function run()
  {
    // get player timezones
    $players = $this->models->players('get_all');
    if(!$players) return true; // no players, no timeslots.
    $timezones = [];
    foreach($players as $player) $timezones[$player['id']] = $player['timezone'];

    // migrate timeslots
    $timeslots_single = $this->db->get('timeslots_old');
    $timeslots_recurring = $this->db->get('timeslots_recurring_old');
    $timeslots = array_merge($timeslots_single, $timeslots_recurring);

    foreach($timeslots as $timeslot)
    {
      // get local start time
      if(!$timezones[$timeslot['player_id']]) continue; // invalid, no player timezone.
      $start = new DateTime('now', new DateTimeZone($timezones[$timeslot['player_id']]));
      $start->setTimestamp($timeslot['start']);

      // handle stop
      $mode = $timeslot['mode'] ?? 'once';
      if($mode!='once')
      {
        $stop = new DateTime('now', new DateTimeZone($timezones[$timeslot['player_id']]));
        $stop->setTimestamp($timeslot['stop']);
        $stop_formatted = $stop->format('Y-m-d');
      }
      else $stop_formatted = null;

      $data = [
        'user_id' => $timeslot['user_id'],
        'player_id' => $timeslot['player_id'],
        'mode' => $mode,
        'x_data' => $timeslot['x_data'] ?? 0,
        'description' => $timeslot['description'],
        'start' => $start->format('Y-m-d H:i:s'),
        'duration' => $timeslot['duration'],
        'stop' => $stop_formatted
      ];

      $validate = $this->models->timeslots('validate_timeslot', $data);
      if(!$validate[0]) continue; // invalid timeslot, skipping

      $collision = $this->models->timeslots('collision_check', $data);
      if(!$collision[0]) continue; // conflict, skipping

      $this->models->timeslots('save_timeslot', $data);
    }

    return true;
  }
}
