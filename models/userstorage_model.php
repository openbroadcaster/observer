<?php

/*
    Copyright 2012-2022 OpenBroadcaster, Inc.

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
 * Store name/value pairs users.
 *
 * @package Model
 */
class UserStorageModel extends OBFModel
{
  /**
   * Insert or update a name/value pair.
   *
   * @param data
   *
   * @return id
   */
  public function save($args)
  {
    if(!$this('validate_required', $args, ['user_id', 'name', 'value'])) return [false, $this('error')];
    if(!preg_match('/^[a-z0-9-]{1,255}$/i', $args['name'])) { return [false, 'Invalid key (allowed characters alphanumeric plus hyphen).']; }
  
    if($this('get', $args))
    {
      $this->db->where('user_id', $args['user_id']);
      $this->db->where('name', $args['name']);
      $this->db->update('users_storage', $this->filter_keys($args, ['value']));
    }

    else
    {
      $this->db->insert('users_storage', $this->filter_keys($args, ['user_id', 'name', 'value']));
    }

    return [true, 'Saved.'];
  }
  
  /**
   * Get user storage data.
   *
   * @param data
   * 
   * @return string value
   */
  public function get($args)
  {
    if(!$this->validate_required($args, ['user_id', 'name'])) return false;
    $this->db->where('user_id', $args['user_id']);
    $this->db->where('name', $args['name']);
    $value = $this->db->get('users_storage');
    
    if(!$value) return [false, 'Not found.'];
    else return [true, 'Success.', $value['name']];
  }
}
