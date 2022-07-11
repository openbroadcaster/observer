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
 * Manages media uploads to the server, checking for validity and returning
 * relevant file info.
 *
 * @package Model
 */
class UploadsModel extends OBFModel
{
  /**
  * Return whether an uploaded file ID and associated key is valid. Returns
  * FALSE if no ID or key is provided, or if no associated row can be found in
  * the uploads database.
  *
  * @return is_valid
  */
  public function is_valid($id, $key)
  {

    $id = trim($id);
    $key = trim($key);

    if(empty($id) || empty($key)) return false;

    $this->db->where('id', $id);
    $this->db->where('key', $key);

    if($this->db->get_one('uploads')) return true;
    else return false;

  }

  /**
   * Get relevant info about file upload.
   *
   * @param id Upload ID.
   * @param key Upload key.
   *
   * @return [type, format, duration]
   */
  public function file_info($id, $key)
  {

    $id = trim($id);
    $key = trim($key);

    if(empty($id) || empty($key)) return false;

    $this->db->where('id', $id);
    $this->db->where('key', $key);

    $this->db->what('type');
    $this->db->what('format');
    $this->db->what('duration');

    return $this->db->get_one('uploads');

  }

}
