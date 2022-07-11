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
 * Manages players that play the content managed on the server.
 *
 * @package Model
 */
class PlayersModel extends OBFModel
{
  /**
   * Retrieve data from a single player. ID passed as parameter, rather than in
   * a data array. Also includes all station IDs for that player.
   *
   * @param id
   *
   * @return player
   */
  public function get_one($id)
  {

    $this->db->where('id', $id);
    $player = $this->db->get_one('players');

    if($player) {
      $player['station_ids']=$this('get_station_ids', $id);
    }

    return $player;

  }

  /**
   * Retrieve all players.
   *
   * @return players
   */
  public function get_all()
  {
    return $this->db->get('players');
  }

  /**
   * Retrieve players filtered by parameters.
   *
   * @param params Filters used when selecting players. See controller for specifics.
   *
   * @return players
   */
  public function get($params)
  {

    foreach($params as $name=>$value) $$name=$value;

    if($filters) foreach($filters as $filter)
    {
      $column = $filter['column'];
      $value = $filter['value'];
      $operator = (empty($filter['operator']) ? '=' : $filter['operator']);

      $this->db->where($column, $value, $operator);
    }

    if($orderby) $this->db->orderby($orderby, (!empty($orderdesc) ? 'desc' : 'asc'));

    if($limit) $this->db->limit($limit);

    if($offset) $this->db->offset($offset);

    $result = $this->db->get('players');

    if($result === false) return false;

    foreach($result as $index=>$row)
    {

      // get our default playlist name.
      if(!empty($row['default_playlist_id']))
      {

        $this->db->what('name');
        $this->db->where('id', $row['default_playlist_id']);
        $default_playlist = $this->db->get_one('playlists');

        $result[$index]['default_playlist_name']=$default_playlist['name'];

      }

      else
      {
        $result[$index]['default_playlist_name']=null;
        $result[$index]['default_playlist_id']=null;
      }

      // get our station ids
      $result[$index]['media_ids']=array();

      $station_ids = $this('get_station_ids', $row['id']);
      foreach($station_ids as $station_id)
      {
        $this->db->where('id', $station_id);
        $media=$this->db->get_one('media');

        if($media) $result[$index]['media_ids'][]=$media;
      }

    }

    return $result;

  }

  /**
   * Get station IDs for a player. ID passed as single parameter, not in data
   * array.
   *
   * @param id
   *
   * @return media_ids
   */
  public function get_station_ids($id)
  {

    $this->db->where('player_id', $id);
    $station_ids = $this->db->get('players_station_ids');

    $media_ids = array();

    foreach($station_ids as $station_id) $media_ids[]=$station_id['media_id'];

    return $media_ids;

  }

  /**
   * Very unintelligently guess at a station ID duration. Since this is used in
   * playlists which are not tied to a player, and since station ID durations
   * can vary considerably, this is probably going to be a pretty terrible
   * estimate.
   *
   * @return duration
   */
  public function station_id_average_duration()
  {
    $this->db->query('select sum(media.duration) as sum, count(*) as count from players_station_ids left join media on players_station_ids.media_id = media.id where media.type!="image"');
    $data = $this->db->assoc_list();
    $sum = $data[0]['sum'];
    $sum_count = $data[0]['count'];

    $players = $this->get_all();

    foreach($players as $player)
    {
      $this->db->query('select count(*) as count from players_station_ids left join media on players_station_ids.media_id = media.id where media.type="image" and players_station_ids.player_id="'.$this->db->escape($player['id']).'"');
      $data = $this->db->assoc_list();

      $sum += $data['0']['count'] * $player['station_id_image_duration'];
      $sum_count += $data[0]['count'];
    }

    if($sum_count==0) return 0; // no station IDs? then duration is zero.
    return $sum/$sum_count;
  }

  /**
   * Validate data for updating/insert a player.
   *
   * @param data Data array. See controller for details.
   * @param id player ID. FALSE when inserting a new player.
   *
   * @return [status, msg]
   */
  public function validate($data, $id=false)
  {

    $error = false;

    if(empty($data['name'])) $error = 'A player name is required.';

    elseif(isset($data['stream_url']) && $data['stream_url']!='' && !preg_match('|^http(s)?://[a-z0-9-]+(.[a-z0-9-]+)*(:[0-9]+)?(/.*)?$|i', $data['stream_url'])) $error = 'The stream URL is not valid.  Only HTTP(s) is supported.';

    elseif(empty($data['password']) && !$id) $error = 'A player password is required.'; // only required for new players. if password not specified for existing players, no password change will occur.

    elseif(!empty($data['password']) && strlen($data['password'])<6) $error = 'The password must be at least 6 characters long.';

    elseif($id && !$this->db->id_exists('players', $id)) $error = 'The player you are attempted to edit does not exist.';

    elseif(!preg_match('/^[0-9]+$/', $data['station_id_image_duration']) || $data['station_id_image_duration']==0) $error = 'Station ID image duration is not valid.  Enter a number to specify duration in seconds.';

    // verify timezone
    elseif(empty($data['timezone'])) $error = 'You must set a timezone for each player.';

    // make sure player name is unique
    if(empty($error))
    {
      if($id) $this->db->where('id', $id, '!=');
      $this->db->where('name', $data['name']);
      if($this->db->get_one('players')) $error = 'player name must be unique.';
    }

    if(empty($error))
    {

      try
      {
        $tz_test = new DateTimeZone($data['timezone']);
      }
      catch(Exception $e)
      {
        $tz_test = false;
      }

      if(!$tz_test) $error = 'There was an error setting the timezone.';

    }

    // make sure parent player is valid.
    if(empty($error) && $data['parent_player_id'])
    {
      $this->db->where('id', $data['parent_player_id']);
      $parent_player = $this->db->get_one('players');

      if(!$parent_player) $error = 'The specified parent player no longer exists.';
      elseif($parent_player['parent_player_id']!=0) $error = 'This parent player cannot be used.  players that act as child players cannot be used as parents.';
    }

    // verify station IDs.
    if(is_array($data['station_ids']) && !$error)
    {
      foreach($data['station_ids'] as $station_id)
      {
        $this->db->where('id', $station_id);
        $media_info = $this->db->get_one('media');
        if(!$media_info) { $error = 'A station ID you have selected no longer exists.'; break; }
        if($media_info['is_archived']==1 || $media_info['is_approved']==0) { $error = 'Station IDs may be approved media only.'; break; }
      }
    }

    // verify playlist ID
    if(!$error && !empty($data['default_playlist_id'])) if(!$this->db->id_exists('playlists', $data['default_playlist_id'])) $error = 'The playlist you have selected no longer exists.';

    if($error) return array(false,$error);

    return array(true,'');
  }

  /**
   * Insert or update a player.
   *
   * @param data
   * @param id
   *
   * @return id
   */
  public function save($data, $id=false)
  {

    $station_ids = $data['station_ids'];
    unset($data['station_ids']);

    if(!$data['use_parent_schedule']) $data['use_parent_dynamic']=0;

    if(!$id)
    {
      $data['password'] = password_hash($data['password'].OB_HASH_SALT, PASSWORD_DEFAULT);
      $data['owner_id'] = $this->user->param('id');
      $id = $this->db->insert('players', $data);
      if(!$id) return false;
    }

    else
    {

      // get original player, see if we're updating default playlist.
      $this->db->where('id', $id);
      $original_player = $this->db->get_one('players');

      // do we need to clear out all the cache? (child/parent setting change)
      if($original_player['use_parent_dynamic']!=$data['use_parent_dynamic']
          || $original_player['use_parent_schedule']!=$data['use_parent_schedule']
          || $original_player['use_parent_ids']!=$data['use_parent_ids']
          || $original_player['use_parent_playlist']!=$data['use_parent_playlist'])
      {
        $this->db->where('player_id', $id);
        $this->db->delete('shows_cache');
      }

      // if we are changing the default playlist, clear the default playlist schedule cache for this player
      elseif($original_player['default_playlist_id']!=$data['default_playlist_id'])
      {
        $this->db->where('player_id', $id);
        $this->db->where('mode', 'default_playlist');
        $this->db->delete('shows_cache');
      }

      // unset the password if empty - we don't want to change. otherwise, set as hash.
      if($data['password']=='') unset($data['password']);
      else $data['password'] = password_hash($data['password'].OB_HASH_SALT, PASSWORD_DEFAULT);

      $this->db->where('id', $id);
      $update = $this->db->update('players', $data);

      if(!$update) return false;

    }

    $station_id_data['player_id']=$id;
    if($station_ids!==false)
    {

      // delete all station IDs for this player.
      $this->db->where('player_id', $id);
      $this->db->delete('players_station_ids');

      // add all the station IDs we have.
      if(is_array($station_ids)) foreach($station_ids as $station_id)
      {
        $station_id_data['media_id']=$station_id;
        $this->db->insert('players_station_ids', $station_id_data);
      }

    }

    return $id;

  }

  /**
   * Update player version.
   *
   * @param id
   * @param version
   */
  public function update_version($id, $version)
  {
    $this->db->where('id', $id);
    $this->db->update('players', array('version'=>$version));
  }

  /**
   * Update player location.
   *
   * @param id
   * @param longitude
   * @param latitude
   */
  public function update_location($id, $longitude, $latitude)
  {
    $this->db->where('id', $id);
    $this->db->update('players', array('longitude'=>$longitude,'latitude'=>$latitude));
  }


  /**
   * Validate whether it is possible to delete a player. Return FALSE in cases
   * where a player has emergency broadcast content associated with it, or
   * schedule data that the current user has no permission to delete.
   *
   * @param id
   *
   * @return is_deletable
   */
  public function delete_check_permission($id)
  {

    // see if there are emergency broadcasts associated with this player.
    $this->db->where('player_id', $id);
    if($this->db->get_one('emergencies') && !$this->user->check_permission('manage_emergency_broadcasts'))
      return array(false,'Unable to remove this player.  It has emergency broadcast content that you do not have permission to delete.');

    // this doesn't check 'able to delete own show' ability... not sure it's practically necessary..
    $schedule_fail = false;

    $this->db->where('player_id', $id);
    if($this->db->get_one('schedules') && !$this->user->check_permission('manage_timeslots')) $schedule_fail = true;
    $this->db->where('player_id', $id);
    if($this->db->get_one('schedules_recurring') && !$this->user->check_permission('manage_timeslots')) $schedule_fail = true;
    $this->db->where('player_id', $id);
    if($this->db->get_one('timeslots') && !$this->user->check_permission('manage_timeslots')) $schedule_fail = true;
    $this->db->where('player_id', $id);
    if($this->db->get_one('timeslots_recurring') && !$this->user->check_permission('manage_timeslots')) $schedule_fail = true;

    if($schedule_fail) return array(false,'Unable to remove this player.  It has schedule data that you do not have permission to delete.');

    return array(true,'');

  }

  /**
   * Check whether the player is a parent of any other players.
   *
   * @param id
   *
   * @return is_parent
   */
  public function player_is_parent($id)
  {
    $this->db->where('parent_player_id', $id);
    $test = $this->db->get_one('players');

    if($test) return true;
    else return false;
  }

  /**
   * Delete a player.
   *
   * @param id
   */
  public function delete($id)
  {
    $this->db->where('id', $id);
    $this->db->delete('players');

    $this->db->where('player_id', $id);
    $this->db->delete('schedules');
    $this->db->where('player_id', $id);
    $this->db->delete('schedules_recurring');
    $this->db->where('player_id', $id);
    $this->db->delete('timeslots');
    $this->db->where('player_id', $id);
    $this->db->delete('timeslots_recurring');
  }

  /**
   * Search the player monitor log.
   *
   * @param params
   *
   * @return [results, numrows]
   */
  public function monitor_search($params)
  {

    foreach($params as $name=>$value) $$name=$value;

    // get timestamps based on player timezone
    $player = $this('get_one', $player_id);
    if(!$player) return [false];

    $player_timezone = new DateTimeZone($player['timezone']);
    if(!$player_timezone) return [false];

    $start_datetime = new DateTime($date_start, $player_timezone);
    $end_datetime = new DateTime($date_end, $player_timezone);
    if(!$start_datetime || !$end_datetime) return [false];

    // db lookup
    $this->db->where('player_id', $player_id);
    $this->db->where('timestamp', $start_datetime->getTimestamp(), '>=');
    $this->db->where('timestamp', $end_datetime->getTimestamp(), '<');

    if($orderby) $this->db->orderby($orderby, (!empty($orderdesc) ? 'desc' : 'asc'));
    if($limit) $this->db->limit($limit);
    if($offset) $this->db->offset($offset);

    if($filters) foreach($filters as $filter)
    {
      $column = $filter['column'];
      $value = $filter['value'];
      $operator = $filter['operator'];

      if(array_search($column, array('media_id','artist','title'))===false) return array(false,null);
      if(array_search($operator, array('is','not','like','not_like'))===false) return array(false,null);

      if($operator=='like') $this->db->where_like($column, $value);
      elseif($operator=='not_like') $this->db->where_not_like($column, $value);
      else $this->db->where($column, $value, ($operator=='is' ? '=' : '!='));

    }

    $this->db->calc_found_rows();

    $results = $this->db->get('playlog');

    foreach($results as &$result)
    {
      $result['datetime'] = new DateTime('@'.round($result['timestamp']));
      $result['datetime']->setTimezone($player_timezone);
      $result['datetime'] = $result['datetime']->format('Y-m-d H:i:s');
    }

    $numrows = $this->db->found_rows();

    return array($results,$numrows);

  }

  /**
   * Convert monitor results into CSV format.
   *
   * @param results
   *
   * @return csv
   */
  public function monitor_csv($results)
  {
    if(empty($results)) return false;

    $fh = fopen('php://temp', 'w+');

    // get our timezone from the player id
    $player_id = $results[0]['player_id'];
    $player = $this('get_one', $player_id);

    // add our heading row
    fputcsv($fh, ['Media ID','Artist','Title','Date/Time','Context','Notes']);

    // add data rows
    foreach($results as $data)
    {
      fputcsv($fh, [
        $data['media_id'],
        $data['artist'],
        $data['title'],
        $data['datetime'],
        $data['context'],
        $data['notes']
      ]);
    }

    // get csv contents
    $csv = stream_get_contents($fh, -1, 0);

    // close
    fclose($fh);

    return $csv;
  }

  /**
   * Return what's currently playing on a player.
   *
   * @param id
   *
   * @return [show_name, show_time_left, media]
   */
  public function now_playing($player_id)
  {

    $this->db->what('current_playlist_id');
    $this->db->what('current_playlist_end');
    $this->db->what('current_media_id');
    $this->db->what('current_media_end');
    $this->db->what('current_show_name');

    $this->db->where('id', $player_id);
    $player = $this->db->get_one('players');

    if(!$player) return false;

    $return = array();
    $return['show_name']=$player['current_show_name'];
    $return['show_time_left']=$player['current_playlist_end'] - time();

    $this->models->media('get_init');

    $this->db->where('media.id', $player['current_media_id']);
    $media = $this->db->get_one('media');

    $media_data = array();
    $media_data['id']=$media['id'];
    $media_data['title']=$media['title'];
    $media_data['album']=$media['album'];
    $media_data['artist']=$media['artist'];
    $media_data['year']=$media['year'];
    $media_data['category_id']=$media['category_id'];
    $media_data['category_name']=$media['category_name'];
    $media_data['country_id']=$media['country_id'];
    $media_data['country_name']=$media['country_name'];
    $media_data['language_id']=$media['language_id'];
    $media_data['language_name']=$media['language_name'];
    $media_data['genre_id']=$media['genre_id'];
    $media_data['genre_name']=$media['genre_name'];
    $media_data['duration']=$media['duration'];
    $media_data['time_left']=$player['current_media_end']-time();

    $return['media']=$media_data;

    return $return;

  }

}
