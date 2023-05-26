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
 * relevant file info. Also manages thumbnails for both media and playlists.
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

        if (empty($id) || empty($key)) {
            return false;
        }

        $this->db->where('id', $id);
        $this->db->where('key', $key);

        if ($this->db->get_one('uploads')) {
            return true;
        } else {
            return false;
        }
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

        if (empty($id) || empty($key)) {
            return false;
        }

        $this->db->where('id', $id);
        $this->db->where('key', $key);

        $this->db->what('type');
        $this->db->what('format');
        $this->db->what('duration');

        return $this->db->get_one('uploads');
    }

    /**
     * Upload a thumbnail for media or a playlist.
     * 
     * @param id Media or playlist ID.
     * @param type Which thumbnail subdirectory to put things in (media or playlists, to make sure IDs don't overwrite each other).
     * @param data Thumbnail data in base64 format as provided by JS FileReader.
     * 
     * @return [success, msg]
     */
    public function thumbnail_save($id, $type, $data)
    {
        if (! file_exists(OB_THUMBNAILS . '/' . $type)) {
            $can_create = mkdir(OB_THUMBNAILS . '/' . $type, 0755, true);
            if (! $can_create) {
                return [false, 'Failed to create thumbnails directory on server.'];
            }
        }

        if (!$id) {
            return [false, 'No ID provided for thumbnail.'];
        }

        if ($type !== 'media' && $type !== 'playlist') {
            return [false, 'Only media and playlists can have thumbnails'];
        }

        // If an empty string or no data is provided, assume that user has cleared the thumbnail 
        // field and we can delete any thumbnails that have already been saved.
        if (! $data || $data === "") {
            if (! file_exists(OB_THUMBNAILS . '/' . $type . '/' . $id . '.ext' )) {
                return [true, 'No thumbnail provided and none already exists. Skipping.'];
            } else {
                return [unlink(OB_THUMBNAILS . '/' . $type . '/' . $id . '.ext'), 'Deleted thumbnail.'];
            }
        }

        // Check data to make sure it's at least valid image MIME and base64.
        if (substr($data, 0, 11) !== 'data:image/') {
            return [false, 'Invalid image MIME type provided. Uploaded thumbnail is not valid image.'];
        }

        $b64 = explode(',', $data, 2)[1];
        if (base64_encode(base64_decode($b64, true)) !== $b64) {
            return [false, 'Invalid image base64 data. Uploaded thumbnail is not valid image.'];
        }

        // Save thumbnail. Note that file_put_contents overwrites any existing thumbnail that
        // may already exist for this ID by default.
        file_put_contents(OB_THUMBNAILS . '/' . $type . '/' . $id . '.ext', $data);

        return [true, 'Successfully saved thumbnail'];
    }

    /**
     * Retrieve a thumbnail for media or a playlist.
     * 
     * @param id Media or playlist ID.
     * @param type Which thumbnail subdirectory to retrieve from (see thumbnail_save).
     * 
     * @return [success, thumbnail_base64, msg]
     */
    public function thumbnail_get($id, $type) {
        if (!$id) {
            return [false, 'No ID provided for thumbnail.'];
        }

        if ($type !== 'media' && $type !== 'playlist') {
            return [false, 'Only media and playlists can have thumbnails'];
        }

        if (! file_exists(OB_THUMBNAILS . '/' . $type . '/' . $id . '.ext')) {
            return [false, 'No thumbnail found.'];
        }

        $data = file_get_contents(OB_THUMBNAILS . '/' . $type . '/' . $id . '.ext');
        if ($data === false) {
            return [false, 'Unable to read thumbnail data.'];
        }

        return [true, $data];
    }
}
