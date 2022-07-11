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
 * Stores client settings data to be used in such situations as reloading a page
 * and showing the same selections. DEPRECATED.
 *
 * @package Model
 */
class ClientStorageModel extends OBFModel
{
  /**
   * Validate data before storing. Make sure the data contains a client name,
   * actual data, and a valid user ID.
   *
   * @param user_id
   * @param client_name
   * @param data
   *
   * @return status
   */
  public function validate($args = [])
  {
    OBFHelpers::require_args($args, ['user_id', 'client_name', 'data']);

    if(empty($args['client_name']) || $args['data']===false || !preg_match('/^[0-9]+$/', $args['user_id']))
    {
      return array(false,'Invalid client name, data, or user.');
    }

    return array(true,'');

  }

  /**
   * Store data in client storage. This can update pre-existing data or insert
   * new values.
   *
   * @param user_id
   * @param client_name
   * @param data
   */
  public function store($args = [])
  {
    OBFHelpers::require_args($args, ['user_id', 'client_name', 'data']);

    // see if we already have a row for this client_name / user_id.
    $this->db->where('user_id', $args['user_id']);
    $this->db->where('client_name', $args['client_name']);
    $rows = $this->db->get('client_storage');

    if (!empty($rows) && count($rows) > 0) {
      $this->db->where('user_id', $args['user_id']);
      $this->db->where('client_name', $args['client_name']);
      $this->db->update('client_storage', array('data' => $args['data']));
    } else
      $this->db->insert('client_storage', array('user_id' => $args['user_id'], 'client_name' => $args['client_name'], 'data' => $args['data']));
  }

  /**
   * Retrieve client storage data.
   *
   * @param client_name
   * @param user_id
   *
   * @return data
   */
  public function get($args = [])
  {
    OBFHelpers::require_args($args, ['client_name', 'user_id']);

    if (empty($args['client_name']) || !preg_match('/^[0-9]+$/', $args['user_id']))
    {
      return array(false,'Invalid client name or user.');
    }

    // see if we already have a row for this client_name / user_id.
    $this->db->where('user_id', $args['user_id']);
    $this->db->where('client_name', $args['client_name']);
    $data = $this->db->get_one('client_storage');

    if($data==false) return array(true,'No data found.','');
    else return array(true,'Stored data found.',$data['data']);

  }

}
