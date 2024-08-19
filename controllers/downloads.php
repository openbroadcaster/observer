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
 * Endpoints where the output is raw/binary.
 *
 * @package Controller
 */
class Downloads extends OBFController
{
    private $io;

    public function __construct()
    {
        parent::__construct();
        $this->io = OBFIO::get_instance();
    }

    /**
     * Download media item.
     *
     * @param id Media ID
     *
     * @route GET /v2/downloads/media/(:id:)
     */
    public function media()
    {
        $id = $this->data('id');
        $media = $this->models->media('get_by_id', ['id' => $id]);

        if (!$media) {
            $this->error(OB_ERROR_NOTFOUND);
        }

        // check permissions
        if ($media['status'] != 'public') {
            $this->user->require_authenticated();
            $is_media_owner = $media['owner_id'] == $this->user->param('id');

            // download requires download_media if this is not the media owner
            if (!$is_media_owner) {
                $user->require_permission('download_media');
            }

            // private media requires manage_media if this is not the media owner
            if ($media['status'] == 'private' && !$is_media_owner) {
                $this->user->require_permission('manage_media');
            }
        }

        if ($media['is_archived'] == 1) {
            $filedir = OB_MEDIA_ARCHIVE;
        } elseif ($media['is_approved'] == 0) {
            $filedir = OB_MEDIA_UPLOADS;
        } else {
            $filedir = OB_MEDIA;
        }

        $filedir .= '/' . $media['file_location'][0] . '/' . $media['file_location'][1];

        $fullpath = $filedir . '/' . $media['filename'];

        if (!file_exists($fullpath)) {
            $this->error(OB_ERROR_NOTFOUND);
        }

        header("Access-Control-Allow-Origin: *");
        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header("Content-Transfer-Encoding: binary");
        header("Content-Length: " . filesize($fullpath));
        header('Content-Disposition: attachment; filename="' . $media['filename'] . '"');

        readfile($fullpath);
    }

    /**
     * Get all restrictions.
     *
     * @param id Media ID
     *
     * @route GET /v2/downloads/stream/(:id:)
     */
    public function stream()
    {
    }

    /**
     * Get all restrictions.
     *
     * @param id Media ID
     *
     * @route GET /v2/downloads/thumbnail/(:id:)
     */
    public function thumbnail()
    {
        $id = $this->data('id');
        $media = $this->models->media('get_by_id', ['id' => $id]);

        if (!$media) {
            $this->error(OB_ERROR_NOTFOUND);
        }

        // check permissions
        if ($media['status'] != 'public') {
            $this->user->require_authenticated();
            $is_media_owner = $media['owner_id'] == $this->user->param('id');
            if ($media['status'] == 'private' && !$is_media_owner) {
                $this->user->require_permission('manage_media');
            }
        }

        // get thumbnail
        $file = $this->models->media('thumbnail_file', ['media' => $id]);

        if (!$file || !file_exists($file)) {
            $this->error(OB_ERROR_NOTFOUND);
        } else {
            $mime = mime_content_type($file);
            $contents = file_get_contents($file);
            header('Content-Type: ' . $mime);
            echo $contents;
        }
    }

    private function error($code)
    {
        $this->io->error($code);
        die();
    }
}
