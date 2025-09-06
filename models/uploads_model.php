<?php

// Copyright 2012-2024 OpenBroadcaster, Inc.
// SPDX-License-Identifier: AGPL-3.0-or-later

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
        if ($type === 'media') {
            $this->db->where('id', $id);
            $file_location = $this->db->get('media')[0]['file_location'] ?? null;
        } elseif ($type === 'playlist') {
            $this->db->where('id', $id);
            $file_location = $this->db->get('playlists')[0]['file_location'] ?? null;
        } else {
            return [false, 'Only media and playlists can have thumbnails.'];
        }

        if (! $file_location || strlen($file_location) !== 2) {
            return [false, 'Could not get file location from ' . $type . ' table.'];
        }

        $dir_path = OB_THUMBNAILS . '/' . $type . '/' . $file_location[0] . '/' . $file_location[1];
        if (! file_exists($dir_path)) {
            $can_create = mkdir($dir_path, 0755, true);
            if (! $can_create) {
                return [false, 'Failed to create thumbnails directory on server.'];
            }
        }

        if (!$id) {
            return [false, 'No ID provided for thumbnail.'];
        }

        // If an empty string or no data is provided, assume that user has cleared the thumbnail
        // field and we can delete any thumbnails that have already been saved.
        if (! $data || $data === "") {
            if (! glob($dir_path . '/' . $id . '.*')) {
                return [true, 'No thumbnail provided and none already exists. Skipping.'];
            } else {
                foreach (glob($dir_path . '/' . $id . '.*') as $file) {
                    unlink($file);
                }
                return [true, 'Deleted thumbnail.'];
            }
        }

        // Check data to make sure it's at least valid image MIME and base64.
        if (substr($data, 0, 11) !== 'data:image/') {
            return [false, 'Invalid image MIME type provided. Uploaded thumbnail is not valid image.'];
        }

        [$header, $b64] = explode(',', $data, 2);

        if (base64_encode(base64_decode($b64, true)) !== $b64) {
            return [false, 'Invalid image base64 data. Uploaded thumbnail is not valid image.'];
        }

        // Only allow specific image formats in the header.
        $ext = strtolower(explode(';', explode('/', $header)[1])[0]);
        if (! in_array($ext, ['jpg', 'jpeg', 'png', 'webp'])) {
            return [false, 'Only JPEG, PNG, and WEBP images are allowed as thumbnails.'];
        }

        // Save thumbnail. Delete any previously saved thumbnail first.
        foreach (glob($dir_path . '/' . $id . '.*') as $file) {
            unlink($file);
        }

        file_put_contents($dir_path . '/' . $id . '.' . $ext, base64_decode($b64, true));

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
    public function thumbnail_get($id, $type)
    {
        if (!$id) {
            return [false, 'No ID provided for thumbnail.'];
        }

        if ($type === 'media') {
            $this->db->where('id', $id);
            $file_location = $this->db->get('media')[0]['file_location'] ?? null;
        } elseif ($type === 'playlist') {
            $this->db->where('id', $id);
            $file_location = $this->db->get('playlists')[0]['file_location'] ?? null;
        } else {
            return [false, 'Only media and playlists can have thumbnails.'];
        }

        $dir_path = OB_THUMBNAILS . '/' . $type . '/' . $file_location[0] . '/' . $file_location[1];
        $path = glob($dir_path . '/' . $id . '.*')[0] ?? null;

        if (! $path) {
            return [false, 'No thumbnail found.'];
        }

        $ext = explode('.', $path)[1];
        $data = file_get_contents($path);
        if ($data === false) {
            return [false, 'Unable to read thumbnail data.'];
        }

        $data = 'data:image/' . $ext . ';base64,' . base64_encode($data);

        return [true, $data];
    }
}
